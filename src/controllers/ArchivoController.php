<?php
/**
 * Controlador de Archivos
 *
 * Gestiona subida, descarga, papelera y eliminación de archivos.
 *
 * @package RKMarketingDrive
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class ArchivoController {

    private $db;

    public function __construct() {
        $this->db = db();
        requireAuth();
    }

    public function handleRequest($action, $id) {
        switch ($action) {
            case 'upload':   return $this->upload();
            case 'download': return $this->download((int)$id);
            case 'trash':    return $this->moveToTrash((int)$id);
            case 'restore':  return $this->restore((int)$id);
            case 'delete':   return $this->delete((int)$id);
            default:         return $this->index();
        }
    }

    // =========================================================================
    // LISTADO DE TODOS LOS ARCHIVOS
    // =========================================================================

    public function index() {
        $userId   = getCurrentUserId();
        $archivos = $this->db->fetchAll(
            "SELECT a.*, c.nombre AS carpeta_nombre
             FROM archivos a
             INNER JOIN carpetas c ON a.carpeta_id = c.id
             WHERE a.usuario_id = :uid AND a.en_papelera = 0
             ORDER BY a.fecha_subida DESC",
            ['uid' => $userId]
        );

        $carpetas = $this->db->fetchAll(
            "SELECT id, nombre FROM carpetas WHERE usuario_id = :uid AND activa = 1 ORDER BY nombre",
            ['uid' => $userId]
        );

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
        $carpeta = $this->db->fetchOne(
            "SELECT id, nombre FROM carpetas WHERE id = :id AND usuario_id = :uid AND activa = 1 LIMIT 1",
            ['id' => $carpetaId, 'uid' => $userId]
        );

        if (!$carpeta) {
            setFlash('error', 'Carpeta no encontrada.');
            redirect('/?page=folders');
        }

        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] === UPLOAD_ERR_NO_FILE) {
            setFlash('error', 'No se seleccionó ningún archivo.');
            redirect("/?page=folders&action=show&id={$carpetaId}");
        }

        $file = $_FILES['archivo'];

        // Validar archivo
        $validation = validateUploadedFile($file);
        if (!$validation['valid']) {
            setFlash('error', implode(' ', $validation['errors']));
            redirect("/?page=folders&action=show&id={$carpetaId}");
        }

        // Verificar espacio disponible
        $storageRow = $this->db->fetchOne(
            "SELECT almacenamiento_usado, almacenamiento_maximo FROM usuarios WHERE id = :id LIMIT 1",
            ['id' => $userId]
        );
        if (((int)$storageRow['almacenamiento_usado'] + $file['size']) > (int)$storageRow['almacenamiento_maximo']) {
            setFlash('error', 'No tienes suficiente espacio de almacenamiento.');
            redirect("/?page=folders&action=show&id={$carpetaId}");
        }

        // Generar ruta física segura
        $extension    = $validation['extension'];
        $nombreOriginal = sanitizeFilename($file['name']);
        $nombreFisico = bin2hex(random_bytes(16)) . '.' . $extension;
        $rutaFisica   = generateSecurePath($userId, $nombreFisico);

        if (!move_uploaded_file($file['tmp_name'], $rutaFisica)) {
            setFlash('error', 'Error al mover el archivo al servidor.');
            redirect("/?page=folders&action=show&id={$carpetaId}");
        }

        try {
            $archivoId = $this->db->insert(
                "INSERT INTO archivos
                 (carpeta_id, usuario_id, nombre_original, nombre_fisico, tipo_mime,
                  extension, tamano_bytes, ruta_fisica, descripcion)
                 VALUES (:cid, :uid, :nom_orig, :nom_fis, :mime, :ext, :tam, :ruta, :desc)",
                [
                    'cid'      => $carpetaId,
                    'uid'      => $userId,
                    'nom_orig' => $nombreOriginal,
                    'nom_fis'  => $nombreFisico,
                    'mime'     => $validation['mime'],
                    'ext'      => $extension,
                    'tam'      => $file['size'],
                    'ruta'     => $rutaFisica,
                    'desc'     => !empty($descripcion) ? sanitizeString($descripcion) : null,
                ]
            );

            logActivity($userId, 'file_upload', "Archivo subido: {$nombreOriginal}", 'archivo', $archivoId);
            setFlash('success', "Archivo \"{$nombreOriginal}\" subido correctamente.");
        } catch (Exception $e) {
            if (file_exists($rutaFisica)) unlink($rutaFisica);
            setFlash('error', 'Error al registrar el archivo.');
        }

        redirect("/?page=folders&action=show&id={$carpetaId}");
    }

    // =========================================================================
    // DESCARGAR ARCHIVO
    // =========================================================================

    public function download($id) {
        $userId  = getCurrentUserId();
        $archivo = $this->findArchivoOrFail($id, $userId);

        if (!file_exists($archivo['ruta_fisica'])) {
            setFlash('error', 'El archivo físico no se encontró en el servidor.');
            redirect('/?page=folders');
        }

        logActivity($userId, 'file_download', "Archivo descargado: {$archivo['nombre_original']}", 'archivo', $id);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $archivo['tipo_mime']);
        header('Content-Disposition: attachment; filename="' . $archivo['nombre_original'] . '"');
        header('Content-Length: ' . $archivo['tamano_bytes']);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        ob_clean();
        flush();
        readfile($archivo['ruta_fisica']);
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

        $this->db->execute(
            "UPDATE archivos SET en_papelera = 1, fecha_eliminacion = NOW() WHERE id = :id",
            ['id' => $id]
        );

        logActivity($userId, 'file_trash', "Archivo a papelera: {$archivo['nombre_original']}", 'archivo', $id);
        setFlash('success', "Archivo movido a la papelera.");

        // Redirigir de vuelta a la carpeta
        $carpetaId = $archivo['carpeta_id'];
        redirect("/?page=folders&action=show&id={$carpetaId}");
    }

    // =========================================================================
    // PAPELERA (vista)
    // =========================================================================

    public function trash() {
        $userId   = getCurrentUserId();
        $archivos = $this->db->fetchAll(
            "SELECT a.*, c.nombre AS carpeta_nombre
             FROM archivos a
             INNER JOIN carpetas c ON a.carpeta_id = c.id
             WHERE a.usuario_id = :uid AND a.en_papelera = 1
             ORDER BY a.fecha_eliminacion DESC",
            ['uid' => $userId]
        );

        return [
            'view'     => 'trash/index',
            'archivos' => $archivos,
        ];
    }

    // =========================================================================
    // RESTAURAR DESDE PAPELERA
    // =========================================================================

    public function restore($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=trash');
        }

        requireCSRFToken();

        $userId  = getCurrentUserId();
        $archivo = $this->findArchivoOrFail($id, $userId, true);

        $this->db->execute(
            "UPDATE archivos SET en_papelera = 0, fecha_eliminacion = NULL WHERE id = :id",
            ['id' => $id]
        );

        logActivity($userId, 'file_restore', "Archivo restaurado: {$archivo['nombre_original']}", 'archivo', $id);
        setFlash('success', "Archivo \"{$archivo['nombre_original']}\" restaurado.");
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

        if (file_exists($archivo['ruta_fisica'])) {
            unlink($archivo['ruta_fisica']);
            $dir = dirname($archivo['ruta_fisica']);
            if (is_dir($dir) && count(scandir($dir)) == 2) @rmdir($dir);
        }

        $this->db->execute("DELETE FROM archivos WHERE id = :id", ['id' => $id]);

        logActivity($userId, 'file_delete', "Archivo eliminado: {$archivo['nombre_original']}", 'archivo', $id);
        setFlash('success', "Archivo eliminado permanentemente.");
        redirect('/?page=trash');
    }

    // =========================================================================
    // HELPER PRIVADO
    // =========================================================================

    private function findArchivoOrFail($id, $userId, $enPapelera = false) {
        $papeleraFiltro = $enPapelera ? "AND a.en_papelera = 1" : "AND a.en_papelera = 0";
        $archivo = $this->db->fetchOne(
            "SELECT a.* FROM archivos a
             WHERE a.id = :id AND a.usuario_id = :uid {$papeleraFiltro} LIMIT 1",
            ['id' => $id, 'uid' => $userId]
        );

        if (!$archivo) {
            setFlash('error', 'Archivo no encontrado.');
            redirect('/?page=folders');
        }

        return $archivo;
    }
}
