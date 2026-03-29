<?php
/**
 * Controlador de Carpetas
 *
 * Gestiona el listado, creación, edición y eliminación de carpetas.
 *
 * @package RKMarketingDrive
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class CarpetaController {

    private $db;

    public function __construct() {
        $this->db = db();
        requireAuth();
    }

    /**
     * Enruta la acción recibida desde el router principal.
     */
    public function handleRequest($action, $id) {
        switch ($action) {
            case 'show':   return $this->show((int)$id);
            case 'create': return $this->create();
            case 'edit':   return $this->edit((int)$id);
            case 'delete': return $this->delete((int)$id);
            default:       return $this->index();
        }
    }

    // =========================================================================
    // LISTADO DE CARPETAS
    // =========================================================================

    public function index() {
        $userId   = getCurrentUserId();
        $carpetas = $this->getCarpetasConStats($userId);
        $limite   = MAX_FOLDERS_PER_CLIENT;

        return [
            'view'     => 'folders/index',
            'carpetas' => $carpetas,
            'limite'   => $limite,
        ];
    }

    // =========================================================================
    // DETALLE DE CARPETA (archivos que contiene)
    // =========================================================================

    public function show($id) {
        $userId  = getCurrentUserId();
        $carpeta = $this->findCarpetaOrFail($id, $userId);

        $archivos = $this->db->fetchAll(
            "SELECT * FROM archivos
             WHERE carpeta_id = :cid AND en_papelera = 0
             ORDER BY fecha_subida DESC",
            ['cid' => $id]
        );

        return [
            'view'     => 'folders/show',
            'carpeta'  => $carpeta,
            'archivos' => $archivos,
        ];
    }

    // =========================================================================
    // CREAR CARPETA (solo POST)
    // =========================================================================

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=folders');
        }

        requireCSRFToken();

        $userId = getCurrentUserId();
        $nombre = trim($_POST['nombre'] ?? '');
        $desc   = trim($_POST['descripcion'] ?? '');

        if (empty($nombre)) {
            setFlash('error', 'El nombre de la carpeta es obligatorio.');
            redirect('/?page=folders');
        }

        if (strlen($nombre) > 255) {
            setFlash('error', 'El nombre no puede superar los 255 caracteres.');
            redirect('/?page=folders');
        }

        // Verificar límite de carpetas
        $total = $this->countCarpetas($userId);
        if ($total >= MAX_FOLDERS_PER_CLIENT) {
            setFlash('error', "Has alcanzado el límite de " . MAX_FOLDERS_PER_CLIENT . " carpetas.");
            redirect('/?page=folders');
        }

        $this->db->insert(
            "INSERT INTO carpetas (usuario_id, nombre, descripcion) VALUES (:uid, :nombre, :desc)",
            [
                'uid'    => $userId,
                'nombre' => sanitizeString($nombre),
                'desc'   => !empty($desc) ? sanitizeString($desc) : null,
            ]
        );

        logActivity($userId, 'folder_create', "Carpeta creada: {$nombre}", 'carpeta', null);
        setFlash('success', "Carpeta \"{$nombre}\" creada correctamente.");
        redirect('/?page=folders');
    }

    // =========================================================================
    // EDITAR CARPETA (solo POST)
    // =========================================================================

    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=folders');
        }

        requireCSRFToken();

        $userId  = getCurrentUserId();
        $carpeta = $this->findCarpetaOrFail($id, $userId);

        $nombre = trim($_POST['nombre'] ?? '');
        $desc   = trim($_POST['descripcion'] ?? '');

        if (empty($nombre)) {
            setFlash('error', 'El nombre de la carpeta es obligatorio.');
            redirect('/?page=folders');
        }

        $this->db->execute(
            "UPDATE carpetas SET nombre = :nombre, descripcion = :desc WHERE id = :id",
            [
                'nombre' => sanitizeString($nombre),
                'desc'   => !empty($desc) ? sanitizeString($desc) : null,
                'id'     => $id,
            ]
        );

        logActivity($userId, 'folder_edit', "Carpeta renombrada: {$carpeta['nombre']} → {$nombre}", 'carpeta', $id);
        setFlash('success', "Carpeta actualizada correctamente.");
        redirect('/?page=folders');
    }

    // =========================================================================
    // ELIMINAR CARPETA (soft delete — solo POST)
    // =========================================================================

    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=folders');
        }

        requireCSRFToken();

        $userId  = getCurrentUserId();
        $carpeta = $this->findCarpetaOrFail($id, $userId);

        // Contar archivos activos en la carpeta
        $countRow = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM archivos WHERE carpeta_id = :id AND en_papelera = 0",
            ['id' => $id]
        );

        if ((int)$countRow['total'] > 0) {
            setFlash('error', 'No puedes eliminar una carpeta que tiene archivos. Muévelos o elimínalos primero.');
            redirect('/?page=folders');
        }

        $this->db->execute("UPDATE carpetas SET activa = 0 WHERE id = :id", ['id' => $id]);

        logActivity($userId, 'folder_delete', "Carpeta eliminada: {$carpeta['nombre']}", 'carpeta', $id);
        setFlash('success', "Carpeta \"{$carpeta['nombre']}\" eliminada.");
        redirect('/?page=folders');
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    private function getCarpetasConStats($userId) {
        return $this->db->fetchAll(
            "SELECT c.id, c.nombre, c.descripcion, c.fecha_creacion,
                    COUNT(a.id) AS total_archivos,
                    COALESCE(SUM(a.tamano_bytes), 0) AS tamano_total
             FROM carpetas c
             LEFT JOIN archivos a ON c.id = a.carpeta_id AND a.en_papelera = 0
             WHERE c.usuario_id = :uid AND c.activa = 1
             GROUP BY c.id
             ORDER BY c.fecha_creacion DESC",
            ['uid' => $userId]
        );
    }

    private function countCarpetas($userId) {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM carpetas WHERE usuario_id = :uid AND activa = 1",
            ['uid' => $userId]
        );
        return (int)($row['total'] ?? 0);
    }

    /**
     * Busca una carpeta verificando propiedad; redirige si no existe o no pertenece al usuario.
     */
    private function findCarpetaOrFail($id, $userId) {
        $carpeta = $this->db->fetchOne(
            "SELECT * FROM carpetas WHERE id = :id AND usuario_id = :uid AND activa = 1 LIMIT 1",
            ['id' => $id, 'uid' => $userId]
        );

        if (!$carpeta) {
            setFlash('error', 'Carpeta no encontrada.');
            redirect('/?page=folders');
        }

        return $carpeta;
    }
}
