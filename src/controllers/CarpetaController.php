<?php
/**
 * Controlador de Carpetas
 *
 * @package RKMarketingDrive
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class CarpetaController {

    public function __construct() {
        requireAuth();
    }

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
    // LISTADO
    // =========================================================================

    public function index() {
        $userId   = getCurrentUserId();
        $carpetas = $this->getCarpetasConStats($userId);
        return [
            'view'     => 'folders/index',
            'carpetas' => $carpetas,
            'limite'   => MAX_FOLDERS_PER_CLIENT,
        ];
    }

    // =========================================================================
    // DETALLE
    // =========================================================================

    public function show($id) {
        $userId  = getCurrentUserId();
        $carpeta = $this->findCarpetaOrFail($id, $userId)->toArray();

        $archivos = em()->getConnection()->executeQuery(
            "SELECT * FROM archivos WHERE carpeta_id = :cid AND en_papelera = 0 ORDER BY fecha_subida DESC",
            ['cid' => $id]
        )->fetchAllAssociative();

        return [
            'view'     => 'folders/show',
            'carpeta'  => $carpeta,
            'archivos' => $archivos,
        ];
    }

    // =========================================================================
    // CREAR
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

        if ($this->countCarpetas($userId) >= MAX_FOLDERS_PER_CLIENT) {
            setFlash('error', "Has alcanzado el límite de " . MAX_FOLDERS_PER_CLIENT . " carpetas.");
            redirect('/?page=folders');
        }

        $carpeta = new \App\Entity\Carpeta();
        $carpeta->setUsuario(em()->getReference(\App\Entity\Usuario::class, $userId));
        $carpeta->setNombre(sanitizeString($nombre));
        $carpeta->setDescripcion(!empty($desc) ? sanitizeString($desc) : null);

        em()->persist($carpeta);
        em()->flush();

        logActivity($userId, 'folder_create', "Carpeta creada: {$nombre}", 'carpeta', $carpeta->getId());
        setFlash('success', "Carpeta \"{$nombre}\" creada correctamente.");
        redirect('/?page=folders');
    }

    // =========================================================================
    // EDITAR
    // =========================================================================

    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=folders');
        }

        requireCSRFToken();

        $userId  = getCurrentUserId();
        $carpeta = $this->findCarpetaOrFail($id, $userId);
        $nombreAnterior = $carpeta->getNombre();

        $nombre = trim($_POST['nombre'] ?? '');
        $desc   = trim($_POST['descripcion'] ?? '');

        if (empty($nombre)) {
            setFlash('error', 'El nombre de la carpeta es obligatorio.');
            redirect('/?page=folders');
        }

        $carpeta->setNombre(sanitizeString($nombre));
        $carpeta->setDescripcion(!empty($desc) ? sanitizeString($desc) : null);
        $carpeta->setFechaActualizacion(new \DateTimeImmutable());
        em()->flush();

        logActivity($userId, 'folder_edit', "Carpeta renombrada: {$nombreAnterior} → {$nombre}", 'carpeta', $id);
        setFlash('success', "Carpeta actualizada correctamente.");
        redirect('/?page=folders');
    }

    // =========================================================================
    // ELIMINAR (soft delete)
    // =========================================================================

    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=folders');
        }

        requireCSRFToken();

        $userId  = getCurrentUserId();
        $carpeta = $this->findCarpetaOrFail($id, $userId);

        $countRow = em()->getConnection()->executeQuery(
            "SELECT COUNT(*) AS total FROM archivos WHERE carpeta_id = :id AND en_papelera = 0",
            ['id' => $id]
        )->fetchAssociative();

        if ((int)$countRow['total'] > 0) {
            setFlash('error', 'No puedes eliminar una carpeta que tiene archivos. Muévelos o elimínalos primero.');
            redirect('/?page=folders');
        }

        $nombre = $carpeta->getNombre();
        $carpeta->setActiva(false);
        $carpeta->setFechaActualizacion(new \DateTimeImmutable());
        em()->flush();

        logActivity($userId, 'folder_delete', "Carpeta eliminada: {$nombre}", 'carpeta', $id);
        setFlash('success', "Carpeta \"{$nombre}\" eliminada.");
        redirect('/?page=folders');
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    private function getCarpetasConStats($userId) {
        return em()->getConnection()->executeQuery(
            "SELECT c.id, c.nombre, c.descripcion, c.fecha_creacion,
                    COUNT(a.id) AS total_archivos,
                    COALESCE(SUM(a.tamano_bytes), 0) AS tamano_total
             FROM carpetas c
             LEFT JOIN archivos a ON c.id = a.carpeta_id AND a.en_papelera = 0
             WHERE c.usuario_id = :uid AND c.activa = 1
             GROUP BY c.id
             ORDER BY c.fecha_creacion DESC",
            ['uid' => $userId]
        )->fetchAllAssociative();
    }

    private function countCarpetas($userId) {
        $row = em()->getConnection()->executeQuery(
            "SELECT COUNT(*) AS total FROM carpetas WHERE usuario_id = :uid AND activa = 1",
            ['uid' => $userId]
        )->fetchAssociative();
        return (int)($row['total'] ?? 0);
    }

    private function findCarpetaOrFail($id, $userId): \App\Entity\Carpeta {
        $carpeta = em()->find(\App\Entity\Carpeta::class, $id);

        if (!$carpeta || !$carpeta->isActiva() || $carpeta->getUsuario()->getId() !== $userId) {
            setFlash('error', 'Carpeta no encontrada.');
            redirect('/?page=folders');
        }

        return $carpeta;
    }
}
