<?php
/**
 * Controlador de Archivos
 *
 * @package RKMarketingDrive
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class ArchivoController {

    public function __construct() {
        requireAuth();
    }

    public function handleRequest($action, $id) {
        switch ($action) {
            case 'upload':   return $this->upload();
            case 'download': return $this->download((int)$id);
            case 'preview':  return $this->preview((int)$id);
            case 'trash':    return $this->moveToTrash((int)$id);
            case 'restore':  return $this->restore((int)$id);
            case 'delete':   return $this->delete((int)$id);
            case 'empty':    return $this->emptyTrash();
            case 'edit':     return $this->edit((int)$id);
            default:         return $this->index();
        }
    }

    // =========================================================================
    // LISTADO
    // =========================================================================

    public function index() {
        $userId = getCurrentUserId();

        $archivos = em()->getConnection()->executeQuery(
            "SELECT a.*, c.nombre AS carpeta_nombre
             FROM archivos a
             INNER JOIN carpetas c ON a.carpeta_id = c.id
             WHERE a.usuario_id = :uid AND a.en_papelera = 0
             ORDER BY a.fecha_subida DESC",
            ['uid' => $userId]
        )->fetchAllAssociative();

        $carpetas = em()->getConnection()->executeQuery(
            "SELECT id, nombre FROM carpetas WHERE usuario_id = :uid AND activa = 1 ORDER BY nombre",
            ['uid' => $userId]
        )->fetchAllAssociative();

        return [
            'view'     => 'files/index',
            'archivos' => $archivos,
            'carpetas' => $carpetas,
        ];
    }

    // =========================================================================
    // SUBIR ARCHIVO (soporta carpeta o archivo suelto)
    // =========================================================================

    public function upload() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=folders');
        }

        requireCSRFToken();

        $userId    = getCurrentUserId();
        $carpetaId = isset($_POST['carpeta_id']) && $_POST['carpeta_id'] !== '' ? (int)$_POST['carpeta_id'] : null;
        $descripcion = trim($_POST['descripcion'] ?? '');

        // Si se especificó carpeta, verificar que pertenece al usuario
        $carpeta = null;
        if ($carpetaId) {
            $carpetaCheck = em()->getConnection()->executeQuery(
                "SELECT id, activa FROM carpetas WHERE id = :cid AND usuario_id = :uid LIMIT 1",
                ['cid' => $carpetaId, 'uid' => $userId]
            )->fetchAssociative();

            if (!$carpetaCheck || !$carpetaCheck['activa']) {
                setFlash('error', 'Carpeta no encontrada.');
                redirect('/?page=folders');
            }

            $carpeta = em()->find(\App\Entity\Carpeta::class, $carpetaId);
        }

        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] === UPLOAD_ERR_NO_FILE) {
            setFlash('error', 'No se seleccionó ningún archivo.');
            redirect($carpetaId ? "/?page=folders&action=show&id={$carpetaId}" : '/?page=folders');
        }

        $file = $_FILES['archivo'];

        $validation = validateUploadedFile($file);
        if (!$validation['valid']) {
            setFlash('error', implode(' ', $validation['errors']));
            redirect($carpetaId ? "/?page=folders&action=show&id={$carpetaId}" : '/?page=folders');
        }

        // Verificar espacio disponible
        $usuario = em()->find(\App\Entity\Usuario::class, $userId);
        if (((int)$usuario->getAlmacenamientoUsado() + $file['size']) > (int)$usuario->getAlmacenamientoMaximo()) {
            setFlash('error', 'No tienes suficiente espacio de almacenamiento.');
            redirect($carpetaId ? "/?page=folders&action=show&id={$carpetaId}" : '/?page=folders');
        }

        $extension      = $validation['extension'];
        $nombreOriginal = $this->resolveFilename(sanitizeFilename($file['name']), $carpetaId, $userId);
        $nombreFisico   = bin2hex(random_bytes(16)) . '.' . $extension;
        $rutaFisica     = generateSecurePath($userId, $nombreFisico);

        if (!move_uploaded_file($file['tmp_name'], $rutaFisica)) {
            setFlash('error', 'Error al mover el archivo al servidor.');
            redirect($carpetaId ? "/?page=folders&action=show&id={$carpetaId}" : '/?page=folders');
        }

        try {
            $archivo = new \App\Entity\Archivo();
            $archivo->setUsuario($usuario);
            if ($carpeta) {
                $archivo->setCarpeta($carpeta);
            } else {
                $archivo->setCarpeta(null); // Archivo suelto
            }
            $archivo->setNombreOriginal($nombreOriginal);
            $archivo->setNombreFisico($nombreFisico);
            $archivo->setTipoMime($validation['mime']);
            $archivo->setExtension($extension);
            $archivo->setTamanoBytes((string)$file['size']);
            $archivo->setRutaFisica($rutaFisica);
            $archivo->setDescripcion(!empty($descripcion) ? sanitizeString($descripcion) : null);

            em()->persist($archivo);
            em()->flush();

            logActivity($userId, 'file_upload', "Archivo subido: {$nombreOriginal}", 'archivo', $archivo->getId());
            setFlash('success', "Archivo \"{$nombreOriginal}\" subido correctamente.");
        } catch (\Throwable $e) {
            if (file_exists($rutaFisica)) unlink($rutaFisica);
            setFlash('error', 'Error al registrar el archivo.');
        }

        redirect($carpetaId ? "/?page=folders&action=show&id={$carpetaId}" : '/?page=folders');
    }

    // =========================================================================
    // DESCARGAR
    // =========================================================================

    public function download($id) {
        $userId  = getCurrentUserId();
        $archivo = $this->findArchivoOrFail($id, $userId);

        if (!file_exists($archivo->getRutaFisica())) {
            setFlash('error', 'El archivo físico no se encontró en el servidor.');
            redirect('/?page=folders');
        }

        logActivity($userId, 'file_download', "Archivo descargado: {$archivo->getNombreOriginal()}", 'archivo', $id);

        $mimePermitidos = json_decode(ALLOWED_MIME_TYPES, true);
        $mimeSeguro = in_array($archivo->getTipoMime(), $mimePermitidos)
            ? $archivo->getTipoMime()
            : 'application/octet-stream';

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeSeguro);
        header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($archivo->getNombreOriginal()));
        header('Content-Length: ' . $archivo->getTamanoBytes());
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        ob_clean();
        flush();
        readfile($archivo->getRutaFisica());
        exit;
    }

    // =========================================================================
    // PREVISUALIZAR (inline, sin descarga)
    // =========================================================================

    public function preview($id) {
        $userId  = getCurrentUserId();
        $archivo = em()->find(\App\Entity\Archivo::class, $id);

        if (!$archivo) {
            http_response_code(404);
            exit;
        }

        $propietario = $archivo->getUsuario();

        if (isAdmin()) {
            // acceso total
        } elseif (isWorker()) {
            if ($propietario->getRol() !== 'cliente') {
                http_response_code(403);
                exit;
            }
        } elseif ($propietario->getId() !== $userId) {
            http_response_code(403);
            exit;
        }

        if ($archivo->getTipoMime() === 'image/svg+xml') {
            http_response_code(403);
            exit;
        }

        $ext = strtolower($archivo->getExtension());
        $previewable = [
            'jpg','jpeg','png','gif','webp','svg','bmp',
            'mp4','webm',
            'mp3','wav','ogg','aac','m4a',
            'pdf',
            'txt','csv',
        ];

        if (!in_array($ext, $previewable)) {
            http_response_code(415);
            exit;
        }

        $rutaFisica = $archivo->getRutaFisica();
        if (!file_exists($rutaFisica)) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . $archivo->getTipoMime());
        header('Content-Disposition: inline; filename="' . rawurlencode($archivo->getNombreOriginal()) . '"');
        header('Content-Length: ' . $archivo->getTamanoBytes());
        header('Cache-Control: private, max-age=3600');
        ob_clean();
        flush();
        readfile($rutaFisica);
        exit;
    }

    // =========================================================================
    // MOVER A PAPELERA
    // =========================================================================

    public function moveToTrash($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=folders');
        }

        requireCSRFToken();

        $userId  = getCurrentUserId();
        $archivo = $this->findArchivoOrFail($id, $userId);
        $carpeta   = $archivo->getCarpeta();
        $carpetaId = $carpeta ? $carpeta->getId() : null;

        // Sincronizar almacenamiento_usado antes del flush para que el trigger
        // de la BD nunca reste de un valor incorrecto (evita underflow BIGINT UNSIGNED)
        em()->getConnection()->executeStatement(
            "UPDATE usuarios
             SET almacenamiento_usado = (
                 SELECT COALESCE(SUM(tamano_bytes), 0)
                 FROM archivos
                 WHERE usuario_id = :uid AND en_papelera = 0
             )
             WHERE id = :uid",
            ['uid' => $userId]
        );

        $archivo->setEnPapelera(true);
        $archivo->setFechaEliminacion(new \DateTimeImmutable());
        $archivo->setFechaActualizacion(new \DateTimeImmutable());
        em()->flush();

        logActivity($userId, 'file_trash', "Archivo a papelera: {$archivo->getNombreOriginal()}", 'archivo', $id);
        setFlash('success', "Archivo movido a la papelera.");
        redirect($carpetaId ? "/?page=folders&action=show&id={$carpetaId}" : '/?page=folders');
    }

    // =========================================================================
    // PAPELERA (vista)
    // =========================================================================

    public function trash() {
        $userId   = getCurrentUserId();
        $archivos = em()->getConnection()->executeQuery(
            "SELECT a.*, c.nombre AS carpeta_nombre
             FROM archivos a
             LEFT JOIN carpetas c ON a.carpeta_id = c.id
             WHERE a.usuario_id = :uid AND a.en_papelera = 1
             ORDER BY a.fecha_eliminacion DESC",
            ['uid' => $userId]
        )->fetchAllAssociative();

        return [
            'view'     => 'trash/index',
            'archivos' => $archivos,
        ];
    }

    // =========================================================================
    // RESTAURAR
    // =========================================================================

    public function restore($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=trash');
        }

        requireCSRFToken();

        $userId  = getCurrentUserId();
        $archivo = $this->findArchivoOrFail($id, $userId, true);

        $archivo->setEnPapelera(false);
        $archivo->setFechaEliminacion(null);
        $archivo->setFechaActualizacion(new \DateTimeImmutable());
        em()->flush();

        logActivity($userId, 'file_restore', "Archivo restaurado: {$archivo->getNombreOriginal()}", 'archivo', $id);
        setFlash('success', "Archivo \"{$archivo->getNombreOriginal()}\" restaurado.");
        redirect('/?page=trash');
    }

    // =========================================================================
    // ELIMINAR PERMANENTEMENTE
    // =========================================================================

    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=trash');
        }

        requireCSRFToken();

        $userId  = getCurrentUserId();
        $archivo = $this->findArchivoOrFail($id, $userId, true);

        $rutaFisica     = $archivo->getRutaFisica();
        $nombreOriginal = $archivo->getNombreOriginal();

        if (file_exists($rutaFisica)) {
            unlink($rutaFisica);
            $dir = dirname($rutaFisica);
            if (is_dir($dir) && count(scandir($dir)) == 2) @rmdir($dir);
        }

        em()->remove($archivo);
        em()->flush();

        logActivity($userId, 'file_delete', "Archivo eliminado: {$nombreOriginal}", 'archivo', $id);
        setFlash('success', "Archivo eliminado permanentemente.");
        redirect('/?page=trash');
    }

    // =========================================================================
    // VACIAR PAPELERA
    // =========================================================================

    public function emptyTrash() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=trash');
        }

        requireCSRFToken();

        $userId = getCurrentUserId();

        $archivos = em()->getConnection()->executeQuery(
            "SELECT id, ruta_fisica FROM archivos WHERE usuario_id = :uid AND en_papelera = 1",
            ['uid' => $userId]
        )->fetchAllAssociative();

        if (empty($archivos)) {
            setFlash('info', 'La papelera ya estaba vacía.');
            redirect('/?page=trash');
        }

        $eliminados = 0;
        foreach ($archivos as $row) {
            if (file_exists($row['ruta_fisica'])) {
                unlink($row['ruta_fisica']);
                $dir = dirname($row['ruta_fisica']);
                if (is_dir($dir) && count(scandir($dir)) == 2) @rmdir($dir);
            }
            $eliminados++;
        }

        em()->getConnection()->executeStatement(
            "DELETE FROM archivos WHERE usuario_id = :uid AND en_papelera = 1",
            ['uid' => $userId]
        );

        logActivity($userId, 'trash_empty', "Papelera vaciada: {$eliminados} archivo(s) eliminados");
        setFlash('success', "Papelera vaciada. {$eliminados} archivo(s) eliminados permanentemente.");
        redirect('/?page=trash');
    }

    // =========================================================================
    // EDITAR ARCHIVO (nombre y descripción)
    // =========================================================================

    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=folders');
        }

        requireCSRFToken();

        $userId  = getCurrentUserId();
        $archivo = $this->findArchivoOrFail($id, $userId, false);

        $nombreAnterior = $archivo->getNombreOriginal();
        $nombre = trim($_POST['nombre'] ?? '');
        $desc   = trim($_POST['descripcion'] ?? '');

        if (empty($nombre)) {
            setFlash('error', 'El nombre del archivo es obligatorio.');
            redirect('/?page=folders');
        }

        if (strlen($nombre) > 255) {
            setFlash('error', 'El nombre no puede superar los 255 caracteres.');
            redirect('/?page=folders');
        }

        $archivo->setNombreOriginal(sanitizeString($nombre));
        $archivo->setDescripcion(!empty($desc) ? sanitizeString($desc) : null);
        $archivo->setFechaActualizacion(new \DateTimeImmutable());
        em()->flush();

        logActivity($userId, 'file_edit', "Archivo renombrado: {$nombreAnterior} → {$nombre}", 'archivo', $id);
        setFlash('success', "Archivo actualizado correctamente.");

        // Redirigir a la carpeta si tiene, sino a folders
        $carpetaId = $archivo->getCarpeta()?->getId();
        if ($carpetaId) {
            redirect("/?page=folders&action=show&id={$carpetaId}");
        } else {
            redirect('/?page=folders');
        }
    }

    // =========================================================================
    // HELPER PRIVADO
    // =========================================================================

    private function resolveFilename(string $nombre, ?int $carpetaId, int $userId): string {
        $ext  = pathinfo($nombre, PATHINFO_EXTENSION);
        $base = $ext !== '' ? substr($nombre, 0, -(strlen($ext) + 1)) : $nombre;

        $conn  = em()->getConnection();
        $where = $carpetaId ? 'carpeta_id = :cid' : 'carpeta_id IS NULL';

        $existsQuery = "SELECT COUNT(*) FROM archivos
                        WHERE usuario_id = :uid AND nombre_original = :nombre
                          AND en_papelera = 0 AND {$where}";

        $params = ['uid' => $userId, 'nombre' => $nombre];
        if ($carpetaId) $params['cid'] = $carpetaId;

        if (!(int)$conn->executeQuery($existsQuery, $params)->fetchOne()) {
            return $nombre;
        }

        $counter = 1;
        do {
            $candidato = $base . ' (' . str_pad($counter, 2, '0', STR_PAD_LEFT) . ')' . ($ext !== '' ? '.' . $ext : '');
            $params['nombre'] = $candidato;
            $counter++;
        } while ((int)$conn->executeQuery($existsQuery, $params)->fetchOne() && $counter <= 99);

        return $candidato;
    }

    private function findArchivoOrFail($id, $userId, $enPapelera = false): \App\Entity\Archivo {
        // Verificar pertenencia con query directa (evita problemas con Doctrine proxies)
        $check = em()->getConnection()->executeQuery(
            "SELECT id FROM archivos WHERE id = :id AND usuario_id = :uid AND en_papelera = :ep LIMIT 1",
            ['id' => $id, 'uid' => $userId, 'ep' => $enPapelera ? 1 : 0]
        )->fetchAssociative();

        if (!$check) {
            setFlash('error', 'Archivo no encontrado.');
            redirect('/?page=folders');
        }

        $archivo = em()->find(\App\Entity\Archivo::class, $id);
        if (!$archivo) {
            setFlash('error', 'Archivo no encontrado.');
            redirect('/?page=folders');
        }

        return $archivo;
    }
}
