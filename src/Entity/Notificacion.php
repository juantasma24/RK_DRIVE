<?php
/**
 * RK Marketing Drive - Clase Notificacion
 *
 * Modelo para la gestion de notificaciones del sistema.
 *
 * @package RKMarketingDrive
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class Notificacion {
    private $db;
    private $id;
    private $usuario_id;
    private $tipo;
    private $titulo;
    private $mensaje;
    private $leida;
    private $fecha_creacion;
    private $fecha_lectura;

    // Tipos de notificacion permitidos
    const TIPOS = ['subida', 'eliminacion', 'error', 'limpieza', 'sistema', 'alerta'];

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = db();
    }

    //=============================================================================
    // METODOS CRUD
    //=============================================================================

    /**
     * Busca una notificacion por su ID
     *
     * @param int $id ID de la notificacion
     * @return Notificacion|null
     */
    public function findById($id) {
        $sql = "SELECT * FROM notificaciones WHERE id = :id LIMIT 1";
        $data = $this->db->fetchOne($sql, ['id' => $id]);

        if ($data) {
            return $this->hydrate($data);
        }

        return null;
    }

    /**
     * Crea una nueva notificacion
     *
     * @param array $data Datos de la notificacion
     * @return int|false ID de la notificacion creada o false si falla
     */
    public function create($data) {
        // Validar datos obligatorios
        $required = ['usuario_id', 'tipo', 'titulo', 'mensaje'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }

        // Validar tipo
        if (!in_array($data['tipo'], self::TIPOS)) {
            return false;
        }

        $sql = "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje)
                VALUES (:usuario_id, :tipo, :titulo, :mensaje)";

        return $this->db->insert($sql, [
            'usuario_id' => (int)$data['usuario_id'],
            'tipo' => $data['tipo'],
            'titulo' => sanitizeString($data['titulo']),
            'mensaje' => sanitizeString($data['mensaje'])
        ]);
    }

    /**
     * Marca una notificacion como leida
     *
     * @param int $id ID de la notificacion
     * @return bool
     */
    public function markAsRead($id) {
        $sql = "UPDATE notificaciones
                SET leida = 1, fecha_lectura = NOW()
                WHERE id = :id";

        return $this->db->execute($sql, ['id' => $id]) > 0;
    }

    /**
     * Marca todas las notificaciones de un usuario como leidas
     *
     * @param int $usuarioId ID del usuario
     * @return int Numero de notificaciones actualizadas
     */
    public function markAllAsRead($usuarioId) {
        $sql = "UPDATE notificaciones
                SET leida = 1, fecha_lectura = NOW()
                WHERE usuario_id = :usuario_id AND leida = 0";

        return $this->db->execute($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * Elimina una notificacion
     *
     * @param int $id ID de la notificacion
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM notificaciones WHERE id = :id";
        return $this->db->execute($sql, ['id' => $id]) > 0;
    }

    /**
     * Elimina todas las notificaciones de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @return int Numero de notificaciones eliminadas
     */
    public function deleteAllByUsuario($usuarioId) {
        $sql = "DELETE FROM notificaciones WHERE usuario_id = :usuario_id";
        return $this->db->execute($sql, ['usuario_id' => $usuarioId]);
    }

    //=============================================================================
    // METODOS DE CONSULTA
    //=============================================================================

    /**
     * Obtiene las notificaciones de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @param bool $soloNoLeidas Solo no leidas
     * @param int $limit Limite de resultados
     * @return array
     */
    public function getByUsuario($usuarioId, $soloNoLeidas = false, $limit = 50) {
        $sql = "SELECT * FROM notificaciones WHERE usuario_id = :usuario_id";

        if ($soloNoLeidas) {
            $sql .= " AND leida = 0";
        }

        $sql .= " ORDER BY fecha_creacion DESC LIMIT :limit";

        return $this->db->fetchAll($sql, [
            'usuario_id' => $usuarioId,
            'limit' => $limit
        ]);
    }

    /**
     * Obtiene las notificaciones no leidas de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @return array
     */
    public function getUnread($usuarioId) {
        return $this->getByUsuario($usuarioId, true);
    }

    /**
     * Cuenta las notificaciones no leidas de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @return int
     */
    public function countUnread($usuarioId) {
        $sql = "SELECT COUNT(*) as total FROM notificaciones
                WHERE usuario_id = :usuario_id AND leida = 0";

        $result = $this->db->fetchOne($sql, ['usuario_id' => $usuarioId]);
        return (int)$result['total'];
    }

    /**
     * Cuenta las notificaciones por tipo de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @return array
     */
    public function countByTipo($usuarioId) {
        $sql = "SELECT tipo, COUNT(*) as total
                FROM notificaciones
                WHERE usuario_id = :usuario_id
                GROUP BY tipo";

        return $this->db->fetchAll($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * Obtiene notificaciones por tipo
     *
     * @param string $tipo Tipo de notificacion
     * @param int $limit Limite de resultados
     * @return array
     */
    public function getByTipo($tipo, $limit = 50) {
        if (!in_array($tipo, self::TIPOS)) {
            return [];
        }

        $sql = "SELECT n.*, u.nombre as usuario_nombre, u.email as usuario_email
                FROM notificaciones n
                INNER JOIN usuarios u ON n.usuario_id = u.id
                WHERE n.tipo = :tipo
                ORDER BY n.fecha_creacion DESC
                LIMIT :limit";

        return $this->db->fetchAll($sql, ['tipo' => $tipo, 'limit' => $limit]);
    }

    /**
     * Obtiene notificaciones recientes del sistema
     *
     * @param int $limit Limite de resultados
     * @return array
     */
    public function getRecent($limit = 100) {
        $sql = "SELECT n.*, u.nombre as usuario_nombre, u.email as usuario_email
                FROM notificaciones n
                INNER JOIN usuarios u ON n.usuario_id = u.id
                ORDER BY n.fecha_creacion DESC
                LIMIT :limit";

        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }

    //=============================================================================
    // METODOS DE ENVIO
    //=============================================================================

    /**
     * Crea y envia una notificacion de subida de archivo
     *
     * @param int $usuarioId ID del usuario
     * @param string $nombreArchivo Nombre del archivo
     * @return int|false
     */
    public function notifyUpload($usuarioId, $nombreArchivo) {
        return $this->create([
            'usuario_id' => $usuarioId,
            'tipo' => 'subida',
            'titulo' => 'Archivo subido',
            'mensaje' => "El archivo '{$nombreArchivo}' se ha subido correctamente."
        ]);
    }

    /**
     * Crea y envia una notificacion de eliminacion
     *
     * @param int $usuarioId ID del usuario
     * @param string $nombreArchivo Nombre del archivo
     * @return int|false
     */
    public function notifyDelete($usuarioId, $nombreArchivo) {
        return $this->create([
            'usuario_id' => $usuarioId,
            'tipo' => 'eliminacion',
            'titulo' => 'Archivo eliminado',
            'mensaje' => "El archivo '{$nombreArchivo}' ha sido movido a la papelera."
        ]);
    }

    /**
     * Crea y envia una notificacion de error
     *
     * @param int $usuarioId ID del usuario
     * @param string $mensaje Mensaje de error
     * @return int|false
     */
    public function notifyError($usuarioId, $mensaje) {
        return $this->create([
            'usuario_id' => $usuarioId,
            'tipo' => 'error',
            'titulo' => 'Error',
            'mensaje' => $mensaje
        ]);
    }

    /**
     * Crea y envia una notificacion de limpieza
     *
     * @param int $usuarioId ID del usuario
     * @param string $mensaje Mensaje
     * @return int|false
     */
    public function notifyCleanup($usuarioId, $mensaje) {
        return $this->create([
            'usuario_id' => $usuarioId,
            'tipo' => 'limpieza',
            'titulo' => 'Limpieza automatica',
            'mensaje' => $mensaje
        ]);
    }

    /**
     * Crea y envia una notificacion de alerta de almacenamiento
     *
     * @param int $usuarioId ID del usuario
     * @param float $porcentaje Porcentaje usado
     * @return int|false
     */
    public function notifyStorageAlert($usuarioId, $porcentaje) {
        return $this->create([
            'usuario_id' => $usuarioId,
            'tipo' => 'alerta',
            'titulo' => 'Alerta de almacenamiento',
            'mensaje' => "Has alcanzado el {$porcentaje}% de tu almacenamiento disponible."
        ]);
    }

    /**
     * Notifica a los administradores por email
     *
     * @param string $tipo Tipo de notificacion
     * @param string $mensaje Mensaje
     * @return bool
     */
    public function notifyAdmins($tipo, $mensaje) {
        // Obtener emails de administradores activos
        $sql = "SELECT email FROM administradores_email WHERE activo = 1 AND recibe_alertas = 1";
        $admins = $this->db->fetchAll($sql);

        if (empty($admins)) {
            return false;
        }

        // Enviar notificacion por email (usando la clase Mailer)
        foreach ($admins as $admin) {
            $mailer = mailer();
            $mailer->to($admin['email'])
                   ->subject('[RK Marketing Drive] ' . ucfirst($tipo))
                   ->body("<p>{$mensaje}</p>");

            // En desarrollo, el email se guarda en log
            $mailer->send();
        }

        return true;
    }

    //=============================================================================
    // LIMPIEZA
    //=============================================================================

    /**
     * Elimina notificaciones antiguas
     *
     * @param int $days Dias de antiguedad
     * @return int Numero de notificaciones eliminadas
     */
    public function cleanOld($days = 30) {
        $sql = "DELETE FROM notificaciones
                WHERE leida = 1
                AND fecha_creacion < DATE_SUB(NOW(), INTERVAL :days DAY)";

        return $this->db->execute($sql, ['days' => $days]);
    }

    //=============================================================================
    // METODOS AUXILIARES
    //=============================================================================

    /**
     * Hidrata el objeto con datos de la base de datos
     *
     * @param array $data Datos
     * @return Notificacion
     */
    private function hydrate($data) {
        $notificacion = new self();
        $notificacion->id = (int)$data['id'];
        $notificacion->usuario_id = (int)$data['usuario_id'];
        $notificacion->tipo = $data['tipo'];
        $notificacion->titulo = $data['titulo'];
        $notificacion->mensaje = $data['mensaje'];
        $notificacion->leida = (bool)$data['leida'];
        $notificacion->fecha_creacion = $data['fecha_creacion'];
        $notificacion->fecha_lectura = $data['fecha_lectura'] ?? null;

        return $notificacion;
    }

    //=============================================================================
    // GETTERS
    //=============================================================================

    public function getId() { return $this->id; }
    public function getUsuarioId() { return $this->usuario_id; }
    public function getTipo() { return $this->tipo; }
    public function getTitulo() { return $this->titulo; }
    public function getMensaje() { return $this->mensaje; }
    public function getLeida() { return $this->leida; }
    public function getFechaCreacion() { return $this->fecha_creacion; }
    public function getFechaLectura() { return $this->fecha_lectura; }

    /**
     * Convierte el objeto a array
     *
     * @return array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'leida' => $this->leida,
            'fecha_creacion' => $this->fecha_creacion,
            'fecha_lectura' => $this->fecha_lectura
        ];
    }
}