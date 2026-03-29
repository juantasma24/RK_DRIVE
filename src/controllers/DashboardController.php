<?php
/**
 * Controlador del Panel Principal
 *
 * Muestra estadísticas y actividad reciente según el rol del usuario.
 *
 * @package RKMarketingDrive
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class DashboardController {

    private $db;

    public function __construct() {
        $this->db = db();
        requireAuth();
    }

    public function index() {
        $userId = getCurrentUserId();
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
        $storage    = $this->getStorageInfo($userId);
        $carpetas   = $this->countCarpetas($userId);
        $archivos   = $this->countArchivos($userId);
        $papelera   = $this->countPapelera($userId);
        $recientes  = $this->getArchivosRecientes($userId, 6);
        $actividad  = $this->getActividadReciente($userId, 8);

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
        $sql  = "SELECT almacenamiento_usado, almacenamiento_maximo FROM usuarios WHERE id = :id LIMIT 1";
        $row  = $this->db->fetchOne($sql, ['id' => $userId]);
        $usado  = (int)($row['almacenamiento_usado'] ?? 0);
        $maximo = (int)($row['almacenamiento_maximo'] ?? MAX_STORAGE_PER_CLIENT);
        return [
            'usado'      => $usado,
            'maximo'     => $maximo,
            'porcentaje' => $maximo > 0 ? round(($usado / $maximo) * 100) : 0,
        ];
    }

    private function countCarpetas($userId) {
        $sql = "SELECT COUNT(*) as total FROM carpetas WHERE usuario_id = :id AND activa = 1";
        $row = $this->db->fetchOne($sql, ['id' => $userId]);
        return (int)($row['total'] ?? 0);
    }

    private function countArchivos($userId) {
        $sql = "SELECT COUNT(*) as total FROM archivos WHERE usuario_id = :id AND en_papelera = 0";
        $row = $this->db->fetchOne($sql, ['id' => $userId]);
        return (int)($row['total'] ?? 0);
    }

    private function countPapelera($userId) {
        $sql = "SELECT COUNT(*) as total FROM archivos WHERE usuario_id = :id AND en_papelera = 1";
        $row = $this->db->fetchOne($sql, ['id' => $userId]);
        return (int)($row['total'] ?? 0);
    }

    private function getArchivosRecientes($userId, $limit = 6) {
        $sql = "SELECT a.id, a.nombre_original, a.extension, a.tamano_bytes,
                       a.fecha_subida, c.nombre AS carpeta_nombre
                FROM archivos a
                INNER JOIN carpetas c ON a.carpeta_id = c.id
                WHERE a.usuario_id = :id AND a.en_papelera = 0
                ORDER BY a.fecha_subida DESC
                LIMIT :limit";
        return $this->db->fetchAll($sql, ['id' => $userId, 'limit' => $limit]);
    }

    private function getActividadReciente($userId, $limit = 8) {
        if ($userId) {
            $sql = "SELECT accion, descripcion, fecha FROM logs_actividad
                    WHERE usuario_id = :id ORDER BY fecha DESC LIMIT :limit";
            return $this->db->fetchAll($sql, ['id' => $userId, 'limit' => $limit]);
        }

        // Admin: actividad global con nombre de usuario
        $sql = "SELECT l.accion, l.descripcion, l.fecha, u.nombre AS usuario_nombre
                FROM logs_actividad l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                ORDER BY l.fecha DESC LIMIT :limit";
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }

    // =========================================================================
    // CONSULTAS EXCLUSIVAS DE ADMIN
    // =========================================================================

    private function countUsuarios($rol = null) {
        $sql    = "SELECT COUNT(*) as total FROM usuarios WHERE activo = 1";
        $params = [];
        if ($rol) {
            $sql .= " AND rol = :rol";
            $params['rol'] = $rol;
        }
        $row = $this->db->fetchOne($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    private function countTodosArchivos() {
        $sql = "SELECT COUNT(*) as total FROM archivos WHERE en_papelera = 0";
        $row = $this->db->fetchOne($sql);
        return (int)($row['total'] ?? 0);
    }

    private function getAlmacenamientoTotal() {
        $sql = "SELECT SUM(almacenamiento_usado) as usado, SUM(almacenamiento_maximo) as maximo
                FROM usuarios WHERE activo = 1";
        $row = $this->db->fetchOne($sql);
        $usado  = (int)($row['usado'] ?? 0);
        $maximo = (int)($row['maximo'] ?? 1);
        return [
            'usado'      => $usado,
            'maximo'     => $maximo,
            'porcentaje' => $maximo > 0 ? round(($usado / $maximo) * 100) : 0,
        ];
    }

    private function getArchivosRecientesAdmin($limit = 6) {
        $sql = "SELECT a.id, a.nombre_original, a.extension, a.tamano_bytes,
                       a.fecha_subida, u.nombre AS usuario_nombre
                FROM archivos a
                INNER JOIN usuarios u ON a.usuario_id = u.id
                WHERE a.en_papelera = 0
                ORDER BY a.fecha_subida DESC
                LIMIT :limit";
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }

    private function getClientesResumen() {
        $sql = "SELECT u.id, u.nombre, u.email, u.ultimo_acceso,
                       u.almacenamiento_usado, u.almacenamiento_maximo,
                       COUNT(DISTINCT c.id) AS carpetas,
                       COUNT(a.id) AS archivos
                FROM usuarios u
                LEFT JOIN carpetas c ON u.id = c.usuario_id AND c.activa = 1
                LEFT JOIN archivos a ON u.id = a.usuario_id AND a.en_papelera = 0
                WHERE u.rol = 'cliente' AND u.activo = 1
                GROUP BY u.id
                ORDER BY u.ultimo_acceso DESC
                LIMIT 5";
        return $this->db->fetchAll($sql);
    }
}
