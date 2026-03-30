<?php
/**
 * Controlador del Panel Principal
 *
 * @package RKMarketingDrive
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class DashboardController {

    public function __construct() {
        requireAuth();
    }

    public function index() {
        $userId  = getCurrentUserId();
        $isAdmin = isAdmin();

        $data = $isAdmin
            ? $this->getAdminData()
            : $this->getClientData($userId);

        return array_merge(['view' => 'dashboard/index'], $data);
    }

    // =========================================================================
    // DATOS PARA CLIENTE
    // =========================================================================

    private function getClientData($userId) {
        $storage   = $this->getStorageInfo($userId);
        $carpetas  = $this->countCarpetas($userId);
        $archivos  = $this->countArchivos($userId);
        $papelera  = $this->countPapelera($userId);
        $recientes = $this->getArchivosRecientes($userId, 6);
        $actividad = $this->getActividadReciente($userId, 8);

        return compact('storage', 'carpetas', 'archivos', 'papelera', 'recientes', 'actividad');
    }

    // =========================================================================
    // DATOS PARA ADMIN
    // =========================================================================

    private function getAdminData() {
        $totalUsuarios  = $this->countUsuarios();
        $totalClientes  = $this->countUsuarios('cliente');
        $totalArchivos  = $this->countTodosArchivos();
        $almacenamiento = $this->getAlmacenamientoTotal();
        $recientes      = $this->getArchivosRecientesAdmin(6);
        $actividad      = $this->getActividadReciente(null, 10);
        $clientesStats  = $this->getClientesResumen();

        return compact(
            'totalUsuarios', 'totalClientes', 'totalArchivos',
            'almacenamiento', 'recientes', 'actividad', 'clientesStats'
        );
    }

    // =========================================================================
    // CONSULTAS COMPARTIDAS
    // =========================================================================

    private function getStorageInfo($userId) {
        $row    = em()->getConnection()->executeQuery(
            "SELECT almacenamiento_usado, almacenamiento_maximo FROM usuarios WHERE id = :id LIMIT 1",
            ['id' => $userId]
        )->fetchAssociative();
        $usado  = (int)($row['almacenamiento_usado'] ?? 0);
        $maximo = (int)($row['almacenamiento_maximo'] ?? MAX_STORAGE_PER_CLIENT);
        return [
            'usado'      => $usado,
            'maximo'     => $maximo,
            'porcentaje' => $maximo > 0 ? round(($usado / $maximo) * 100) : 0,
        ];
    }

    private function countCarpetas($userId) {
        $row = em()->getConnection()->executeQuery(
            "SELECT COUNT(*) AS total FROM carpetas WHERE usuario_id = :id AND activa = 1",
            ['id' => $userId]
        )->fetchAssociative();
        return (int)($row['total'] ?? 0);
    }

    private function countArchivos($userId) {
        $row = em()->getConnection()->executeQuery(
            "SELECT COUNT(*) AS total FROM archivos WHERE usuario_id = :id AND en_papelera = 0",
            ['id' => $userId]
        )->fetchAssociative();
        return (int)($row['total'] ?? 0);
    }

    private function countPapelera($userId) {
        $row = em()->getConnection()->executeQuery(
            "SELECT COUNT(*) AS total FROM archivos WHERE usuario_id = :id AND en_papelera = 1",
            ['id' => $userId]
        )->fetchAssociative();
        return (int)($row['total'] ?? 0);
    }

    private function getArchivosRecientes($userId, $limit = 6) {
        return em()->getConnection()->executeQuery(
            "SELECT a.id, a.nombre_original, a.extension, a.tamano_bytes,
                    a.fecha_subida, c.nombre AS carpeta_nombre
             FROM archivos a
             INNER JOIN carpetas c ON a.carpeta_id = c.id
             WHERE a.usuario_id = :id AND a.en_papelera = 0
             ORDER BY a.fecha_subida DESC
             LIMIT :limit",
            ['id' => $userId, 'limit' => $limit]
        )->fetchAllAssociative();
    }

    private function getActividadReciente($userId, $limit = 8) {
        if ($userId) {
            return em()->getConnection()->executeQuery(
                "SELECT accion, descripcion, fecha FROM logs_actividad
                 WHERE usuario_id = :id ORDER BY fecha DESC LIMIT :limit",
                ['id' => $userId, 'limit' => $limit]
            )->fetchAllAssociative();
        }

        return em()->getConnection()->executeQuery(
            "SELECT l.accion, l.descripcion, l.fecha, u.nombre AS usuario_nombre
             FROM logs_actividad l
             LEFT JOIN usuarios u ON l.usuario_id = u.id
             ORDER BY l.fecha DESC LIMIT :limit",
            ['limit' => $limit]
        )->fetchAllAssociative();
    }

    // =========================================================================
    // CONSULTAS EXCLUSIVAS DE ADMIN
    // =========================================================================

    private function countUsuarios($rol = null) {
        $sql    = "SELECT COUNT(*) AS total FROM usuarios WHERE activo = 1";
        $params = [];
        if ($rol) {
            $sql .= " AND rol = :rol";
            $params['rol'] = $rol;
        }
        $row = em()->getConnection()->executeQuery($sql, $params)->fetchAssociative();
        return (int)($row['total'] ?? 0);
    }

    private function countTodosArchivos() {
        $row = em()->getConnection()->executeQuery(
            "SELECT COUNT(*) AS total FROM archivos WHERE en_papelera = 0"
        )->fetchAssociative();
        return (int)($row['total'] ?? 0);
    }

    private function getAlmacenamientoTotal() {
        $row    = em()->getConnection()->executeQuery(
            "SELECT SUM(almacenamiento_usado) AS usado, SUM(almacenamiento_maximo) AS maximo
             FROM usuarios WHERE activo = 1"
        )->fetchAssociative();
        $usado  = (int)($row['usado'] ?? 0);
        $maximo = (int)($row['maximo'] ?? 1);
        return [
            'usado'      => $usado,
            'maximo'     => $maximo,
            'porcentaje' => $maximo > 0 ? round(($usado / $maximo) * 100) : 0,
        ];
    }

    private function getArchivosRecientesAdmin($limit = 6) {
        return em()->getConnection()->executeQuery(
            "SELECT a.id, a.nombre_original, a.extension, a.tamano_bytes,
                    a.fecha_subida, u.nombre AS usuario_nombre
             FROM archivos a
             INNER JOIN usuarios u ON a.usuario_id = u.id
             WHERE a.en_papelera = 0
             ORDER BY a.fecha_subida DESC
             LIMIT :limit",
            ['limit' => $limit]
        )->fetchAllAssociative();
    }

    private function getClientesResumen() {
        return em()->getConnection()->executeQuery(
            "SELECT u.id, u.nombre, u.email, u.ultimo_acceso,
                    u.almacenamiento_usado, u.almacenamiento_maximo,
                    COUNT(DISTINCT c.id) AS carpetas,
                    COUNT(a.id) AS archivos
             FROM usuarios u
             LEFT JOIN carpetas c ON u.id = c.usuario_id AND c.activa = 1
             LEFT JOIN archivos a ON u.id = a.usuario_id AND a.en_papelera = 0
             WHERE u.rol = 'cliente' AND u.activo = 1
             GROUP BY u.id
             ORDER BY u.ultimo_acceso DESC
             LIMIT 5"
        )->fetchAllAssociative();
    }
}
