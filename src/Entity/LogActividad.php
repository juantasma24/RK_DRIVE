<?php
/**
 * RK Marketing Drive - Clase LogActividad
 *
 * Modelo para la gestion de logs de actividad del sistema.
 *
 * @package RKMarketingDrive
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class LogActividad {
    private $db;
    private $id;
    private $usuario_id;
    private $accion;
    private $descripcion;
    private $entidad_tipo;
    private $entidad_id;
    private $ip_address;
    private $user_agent;
    private $datos_adicionales;
    private $fecha;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = db();
    }

    //=============================================================================
    // METODOS DE REGISTRO
    //=============================================================================

    /**
     * Registra una actividad en el log
     *
     * @param int|null $usuarioId ID del usuario (null para sistema)
     * @param string $accion Codigo de accion
     * @param string $descripcion Descripcion
     * @param string|null $entidadTipo Tipo de entidad
     * @param int|null $entidadId ID de entidad
     * @param array|null $datosAdicionales Datos extra
     * @return int|false ID del log o false si falla
     */
    public function registrar($usuarioId, $accion, $descripcion, $entidadTipo = null, $entidadId = null, $datosAdicionales = null) {
        $sql = "INSERT INTO logs_actividad
                (usuario_id, accion, descripcion, entidad_tipo, entidad_id, ip_address, user_agent, datos_adicionales)
                VALUES
                (:usuario_id, :accion, :descripcion, :entidad_tipo, :entidad_id, :ip_address, :user_agent, :datos_adicionales)";

        return $this->db->insert($sql, [
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'entidad_tipo' => $entidadTipo,
            'entidad_id' => $entidadId,
            'ip_address' => getClientIP(),
            'user_agent' => getUserAgent(),
            'datos_adicionales' => $datosAdicionales ? json_encode($datosAdicionales) : null
        ]);
    }

    /**
     * Registra un login
     *
     * @param int $usuarioId ID del usuario
     * @param bool $exitoso Si fue exitoso
     * @return int|false
     */
    public function logLogin($usuarioId, $exitoso = true) {
        return $this->registrar(
            $exitoso ? $usuarioId : null,
            'login',
            $exitoso ? 'Inicio de sesion exitoso' : 'Intento de login fallido',
            'usuario',
            $exitoso ? $usuarioId : null,
            ['exitoso' => $exitoso]
        );
    }

    /**
     * Registra un logout
     *
     * @param int $usuarioId ID del usuario
     * @return int|false
     */
    public function logLogout($usuarioId) {
        return $this->registrar($usuarioId, 'logout', 'Cierre de sesion', 'usuario', $usuarioId);
    }

    /**
     * Registra creacion de carpeta
     *
     * @param int $usuarioId ID del usuario
     * @param int $carpetaId ID de la carpeta
     * @param string $nombre Nombre de la carpeta
     * @return int|false
     */
    public function logCarpetaCreate($usuarioId, $carpetaId, $nombre) {
        return $this->registrar(
            $usuarioId,
            'carpeta_create',
            "Carpeta creada: {$nombre}",
            'carpeta',
            $carpetaId
        );
    }

    /**
     * Registra eliminacion de carpeta
     *
     * @param int $usuarioId ID del usuario
     * @param int $carpetaId ID de la carpeta
     * @param string $nombre Nombre de la carpeta
     * @return int|false
     */
    public function logCarpetaDelete($usuarioId, $carpetaId, $nombre) {
        return $this->registrar(
            $usuarioId,
            'carpeta_delete',
            "Carpeta eliminada: {$nombre}",
            'carpeta',
            $carpetaId
        );
    }

    /**
     * Registra subida de archivo
     *
     * @param int $usuarioId ID del usuario
     * @param int $archivoId ID del archivo
     * @param string $nombre Nombre del archivo
     * @param int $tamano Tamano en bytes
     * @return int|false
     */
    public function logArchivoUpload($usuarioId, $archivoId, $nombre, $tamano) {
        return $this->registrar(
            $usuarioId,
            'archivo_upload',
            "Archivo subido: {$nombre}",
            'archivo',
            $archivoId,
            ['tamano' => $tamano]
        );
    }

    /**
     * Registra descarga de archivo
     *
     * @param int $usuarioId ID del usuario
     * @param int $archivoId ID del archivo
     * @param string $nombre Nombre del archivo
     * @return int|false
     */
    public function logArchivoDownload($usuarioId, $archivoId, $nombre) {
        return $this->registrar(
            $usuarioId,
            'archivo_download',
            "Archivo descargado: {$nombre}",
            'archivo',
            $archivoId
        );
    }

    /**
     * Registra eliminacion de archivo
     *
     * @param int $usuarioId ID del usuario
     * @param int $archivoId ID del archivo
     * @param string $nombre Nombre del archivo
     * @param bool $permanente Si es eliminacion permanente
     * @return int|false
     */
    public function logArchivoDelete($usuarioId, $archivoId, $nombre, $permanente = false) {
        $accion = $permanente ? 'archivo_delete_permanent' : 'archivo_delete';
        $descripcion = $permanente ? "Archivo eliminado permanentemente: {$nombre}" : "Archivo movido a papelera: {$nombre}";

        return $this->registrar($usuarioId, $accion, $descripcion, 'archivo', $archivoId);
    }

    /**
     * Registra restauracion de archivo
     *
     * @param int $usuarioId ID del usuario
     * @param int $archivoId ID del archivo
     * @param string $nombre Nombre del archivo
     * @return int|false
     */
    public function logArchivoRestore($usuarioId, $archivoId, $nombre) {
        return $this->registrar(
            $usuarioId,
            'archivo_restore',
            "Archivo restaurado: {$nombre}",
            'archivo',
            $archivoId
        );
    }

    /**
     * Registra limpieza automatica
     *
     * @param int $archivosEliminados Numero de archivos eliminados
     * @param int $espacioLiberado Espacio liberado en bytes
     * @return int|false
     */
    public function logLimpieza($archivosEliminados, $espacioLiberado) {
        return $this->registrar(
            null,
            'limpieza_automatica',
            "Limpieza automatica ejecutada",
            null,
            null,
            [
                'archivos_eliminados' => $archivosEliminados,
                'espacio_liberado' => $espacioLiberado
            ]
        );
    }

    //=============================================================================
    // METODOS DE CONSULTA
    //=============================================================================

    /**
     * Obtiene logs por usuario
     *
     * @param int $usuarioId ID del usuario
     * @param int $limit Limite de resultados
     * @return array
     */
    public function getByUsuario($usuarioId, $limit = 100) {
        $sql = "SELECT * FROM logs_actividad
                WHERE usuario_id = :usuario_id
                ORDER BY fecha DESC
                LIMIT :limit";

        return $this->db->fetchAll($sql, [
            'usuario_id' => $usuarioId,
            'limit' => $limit
        ]);
    }

    /**
     * Obtiene logs por tipo de accion
     *
     * @param string $accion Codigo de accion
     * @param int $limit Limite de resultados
     * @return array
     */
    public function getByAccion($accion, $limit = 100) {
        $sql = "SELECT l.*, u.nombre as usuario_nombre, u.email as usuario_email
                FROM logs_actividad l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                WHERE l.accion = :accion
                ORDER BY l.fecha DESC
                LIMIT :limit";

        return $this->db->fetchAll($sql, ['accion' => $accion, 'limit' => $limit]);
    }

    /**
     * Obtiene logs por entidad
     *
     * @param string $entidadTipo Tipo de entidad
     * @param int $entidadId ID de entidad
     * @param int $limit Limite de resultados
     * @return array
     */
    public function getByEntidad($entidadTipo, $entidadId, $limit = 50) {
        $sql = "SELECT l.*, u.nombre as usuario_nombre, u.email as usuario_email
                FROM logs_actividad l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                WHERE l.entidad_tipo = :entidad_tipo AND l.entidad_id = :entidad_id
                ORDER BY l.fecha DESC
                LIMIT :limit";

        return $this->db->fetchAll($sql, [
            'entidad_tipo' => $entidadTipo,
            'entidad_id' => $entidadId,
            'limit' => $limit
        ]);
    }

    /**
     * Obtiene logs recientes
     *
     * @param int $limit Limite de resultados
     * @param string|null $accion Filtrar por accion
     * @return array
     */
    public function getRecent($limit = 100, $accion = null) {
        $sql = "SELECT l.*, u.nombre as usuario_nombre, u.email as usuario_email
                FROM logs_actividad l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                WHERE 1=1";

        $params = ['limit' => $limit];

        if ($accion) {
            $sql .= " AND l.accion = :accion";
            $params['accion'] = $accion;
        }

        $sql .= " ORDER BY l.fecha DESC LIMIT :limit";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtiene logs por rango de fechas
     *
     * @param string $fechaInicio Fecha inicio (Y-m-d)
     * @param string $fechaFin Fecha fin (Y-m-d)
     * @param int $limit Limite de resultados
     * @return array
     */
    public function getByFecha($fechaInicio, $fechaFin, $limit = 1000) {
        $sql = "SELECT l.*, u.nombre as usuario_nombre, u.email as usuario_email
                FROM logs_actividad l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                WHERE DATE(l.fecha) BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY l.fecha DESC
                LIMIT :limit";

        return $this->db->fetchAll($sql, [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'limit' => $limit
        ]);
    }

    /**
     * Cuenta logs por tipo de accion
     *
     * @param string $accion Codigo de accion
     * @param string|null $fechaInicio Fecha inicio opcional
     * @param string|null $fechaFin Fecha fin opcional
     * @return int
     */
    public function countByAccion($accion, $fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT COUNT(*) as total FROM logs_actividad WHERE accion = :accion";
        $params = ['accion' => $accion];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND DATE(fecha) BETWEEN :fecha_inicio AND :fecha_fin";
            $params['fecha_inicio'] = $fechaInicio;
            $params['fecha_fin'] = $fechaFin;
        }

        $result = $this->db->fetchOne($sql, $params);
        return (int)$result['total'];
    }

    /**
     * Obtiene estadisticas de actividad
     *
     * @param string|null $fechaInicio Fecha inicio opcional
     * @param string|null $fechaFin Fecha fin opcional
     * @return array
     */
    public function getEstadisticas($fechaInicio = null, $fechaFin = null) {
        $where = "";
        $params = [];

        if ($fechaInicio && $fechaFin) {
            $where = "WHERE DATE(fecha) BETWEEN :fecha_inicio AND :fecha_fin";
            $params = [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ];
        }

        // Total de logs
        $sql = "SELECT COUNT(*) as total FROM logs_actividad {$where}";
        $total = $this->db->fetchOne($sql, $params);

        // Logs por tipo
        $sql = "SELECT accion, COUNT(*) as total
                FROM logs_actividad {$where}
                GROUP BY accion
                ORDER BY total DESC";
        $porAccion = $this->db->fetchAll($sql, $params);

        // Logs por usuario (top 10)
        $sql = "SELECT usuario_id, u.nombre, u.email, COUNT(*) as total
                FROM logs_actividad l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                {$where}
                GROUP BY usuario_id
                ORDER BY total DESC
                LIMIT 10";
        $porUsuario = $this->db->fetchAll($sql, $params);

        // Logs por dia
        $sql = "SELECT DATE(fecha) as dia, COUNT(*) as total
                FROM logs_actividad
                {$where}
                GROUP BY DATE(fecha)
                ORDER BY dia DESC
                LIMIT 30";
        $porDia = $this->db->fetchAll($sql, $params);

        return [
            'total' => (int)$total['total'],
            'por_accion' => $porAccion,
            'por_usuario' => $porUsuario,
            'por_dia' => $porDia
        ];
    }

    //=============================================================================
    // LIMPIEZA
    //=============================================================================

    /**
     * Elimina logs antiguos
     *
     * @param int $days Dias de antiguedad
     * @return int Numero de logs eliminados
     */
    public function cleanOld($days = 90) {
        $sql = "DELETE FROM logs_actividad
                WHERE fecha < DATE_SUB(NOW(), INTERVAL :days DAY)";

        return $this->db->execute($sql, ['days' => $days]);
    }

    //=============================================================================
    // METODOS AUXILIARES
    //=============================================================================

    /**
     * Hidrata el objeto con datos de la base de datos
     *
     * @param array $data Datos
     * @return LogActividad
     */
    private function hydrate($data) {
        $log = new self();
        $log->id = (int)$data['id'];
        $log->usuario_id = $data['usuario_id'] ? (int)$data['usuario_id'] : null;
        $log->accion = $data['accion'];
        $log->descripcion = $data['descripcion'];
        $log->entidad_tipo = $data['entidad_tipo'];
        $log->entidad_id = $data['entidad_id'] ? (int)$data['entidad_id'] : null;
        $log->ip_address = $data['ip_address'];
        $log->user_agent = $data['user_agent'];
        $log->datos_adicionales = $data['datos_adicionales'] ? json_decode($data['datos_adicionales'], true) : null;
        $log->fecha = $data['fecha'];

        return $log;
    }

    //=============================================================================
    // GETTERS
    //=============================================================================

    public function getId() { return $this->id; }
    public function getUsuarioId() { return $this->usuario_id; }
    public function getAccion() { return $this->accion; }
    public function getDescripcion() { return $this->descripcion; }
    public function getEntidadTipo() { return $this->entidad_tipo; }
    public function getEntidadId() { return $this->entidad_id; }
    public function getIpAddress() { return $this->ip_address; }
    public function getUserAgent() { return $this->user_agent; }
    public function getDatosAdicionales() { return $this->datos_adicionales; }
    public function getFecha() { return $this->fecha; }

    /**
     * Convierte el objeto a array
     *
     * @return array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'accion' => $this->accion,
            'descripcion' => $this->descripcion,
            'entidad_tipo' => $this->entidad_tipo,
            'entidad_id' => $this->entidad_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'datos_adicionales' => $this->datos_adicionales,
            'fecha' => $this->fecha
        ];
    }
}