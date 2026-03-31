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
    // SUBIR ARCHIVO
    // =========================================================================

    public function upload() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=folders');
        }

        requireCSRFToken();

        $userId    = getCurrentUserId();
        $carpetaId = (int)($_POST['carpeta_id'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (!$carpetaId) {
            setFlash('error', 'Carpeta no especificada.');
            redirect('/?page=folders');
        }

        // Verificar que la carpeta pertenece al usuario
        $carpeta = em()->find(\App\Entity\Carpeta::class, $carpetaId);
        if (!$carpeta || !$carpeta->isActiva() || $carpeta->getUsuario()->getId() !== $userId) {
            setFlash('error', 'Carpeta no encontrada.');
            redirect('/?page=folders');
        }

        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] === UPLOAD_ERR_NO_FILE) {
            setFlash('error', 'No se seleccionó ningún archivo.');
            redirect("/?page=folders&action=show&id={$carpetaId}");
        }

        $file = $_FILES['archivo'];

        $validation = validateUploadedFile($file);
        if (!$validation['valid']) {
            setFlash('error', implode(' ', $validation['errors']));
            redirect("/?page=folders&action=show&id={$carpetaId}");
        }

        // Verificar espacio disponible
        $usuario = em()->find(\App\Entity\Usuario::class, $userId);
        if (((int)$usuario->getAlmacenamientoUsado() + $file['size']) > (int)$usuario->getAlmacenamientoMaximo()) {
            setFlash('error', 'No tienes suficiente espacio de almacenamiento.');
            redirect("/?page=folders&action=show&id={$carpetaId}");
        }

        $extension      = $validation['extension'];
        $nombreOriginal = sanitizeFilename($file['name']);
        $nombreFisico   = bin2hex(random_bytes(16)) . '.' . $extension;
        $rutaFisica     = generateSecurePath($userId, $nombreFisico);

        if (!move_uploaded_file($file['tmp_name'], $rutaFisica)) {
            setFlash('error', 'Error al mover el archivo al servidor.');
            redirect("/?page=folders&action=show&id={$carpetaId}");
        }

        try {
            $archivo = new \App\Entity\Archivo();
            $archivo->setCarpeta($carpeta);
            $archivo->setUsuario($usuario);
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

        redirect("/?page=folders&action=show&id={$carpetaId}");
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

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $archivo->getTipoMime());
        header('Content-Disposition: attachment; filename="' . $archivo->getNombreOriginal() . '"');
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

        // Debe existir y pertenecer al usuario (o ser admin)
        if (!$archivo || (!isAdmin() && $archivo->getUsuario()->getId() !== $userId)) {
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
        $carpetaId = $archivo->getCarpeta()->getId();

        $archivo->setEnPapelera(true);
        $archivo->setFechaEliminacion(new \DateTimeImmutable());
        $archivo->setFechaActualizacion(new \DateTimeImmutable());
        em()->flush();

        logActivity($userId, 'file_trash', "Archivo a papelera: {$archivo->getNombreOriginal()}", 'archivo', $id);
        setFlash('success', "Archivo movido a la papelera.");
        redirect("/?page=folders&action=show&id={$carpetaId}");
    }

    // =========================================================================
    // PAPELERA (vista)
    // =========================================================================

    public function trash() {
        $userId   = getCurrentUserId();
        $archivos = em()->getConnection()->executeQuery(
            "SELECT a.*, c.nombre AS carpeta_nombre
             FROM archivos a
             INNER JOIN carpetas c ON a.carpeta_id = c.id
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
    // HELPER PRIVADO
    // =========================================================================

    private function findArchivoOrFail($id, $userId, $enPapelera = false): \App\Entity\Archivo {
        $archivo = em()->find(\App\Entity\Archivo::class, $id);

        if (!$archivo
            || $archivo->getUsuario()->getId() !== $userId
            || $archivo->isEnPapelera() !== $enPapelera
        ) {
            setFlash('error', 'Archivo no encontrado.');
            redirect('/?page=folders');
        }

        return $archivo;
    }
}
