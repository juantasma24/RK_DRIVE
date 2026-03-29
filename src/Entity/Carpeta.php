<?php
/**
 * RK Marketing Drive - Clase Carpeta
 *
 * Modelo para la gestion de carpetas.
 *
 * @package RKMarketingDrive
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class Carpeta {
    private $db;
    private $id;
    private $usuario_id;
    private $nombre;
    private $descripcion;
    private $activa;
    private $fecha_creacion;
    private $fecha_actualizacion;

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
     * Busca una carpeta por su ID
     *
     * @param int $id ID de la carpeta
     * @return Carpeta|null
     */
    public function findById($id) {
        $sql = "SELECT * FROM carpetas WHERE id = :id LIMIT 1";
        $data = $this->db->fetchOne($sql, ['id' => $id]);

        if ($data) {
            return $this->hydrate($data);
        }

        return null;
    }

    /**
     * Crea una nueva carpeta
     *
     * @param array $data Datos de la carpeta
     * @return int|false ID de la carpeta creada o false si falla
     */
    public function create($data) {
        // Validar datos obligatorios
        if (empty($data['usuario_id']) || empty($data['nombre'])) {
            return false;
        }

        // Verificar limite de carpetas por usuario
        if (!$this->canCreateFolder($data['usuario_id'])) {
            return false;
        }

        $sql = "INSERT INTO carpetas (usuario_id, nombre, descripcion)
                VALUES (:usuario_id, :nombre, :descripcion)";

        $params = [
            'usuario_id' => (int)$data['usuario_id'],
            'nombre' => sanitizeString($data['nombre']),
            'descripcion' => isset($data['descripcion']) ? sanitizeString($data['descripcion']) : null
        ];

        return $this->db->insert($sql, $params);
    }

    /**
     * Actualiza una carpeta
     *
     * @param int $id ID de la carpeta
     * @param array $data Datos a actualizar
     * @return bool
     */
    public function update($id, $data) {
        $allowedFields = ['nombre', 'descripcion'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = :{$field}";
                $params[$field] = sanitizeString($data[$field]);
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE carpetas SET " . implode(', ', $updates) . " WHERE id = :id";
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Elimina una carpeta (soft delete)
     *
     * @param int $id ID de la carpeta
     * @return bool
     */
    public function delete($id) {
        $sql = "UPDATE carpetas SET activa = 0 WHERE id = :id";
        return $this->db->execute($sql, ['id' => $id]) > 0;
    }

    /**
     * Restaura una carpeta eliminada
     *
     * @param int $id ID de la carpeta
     * @return bool
     */
    public function restore($id) {
        $sql = "UPDATE carpetas SET activa = 1 WHERE id = :id";
        return $this->db->execute($sql, ['id' => $id]) > 0;
    }

    /**
     * Elimina una carpeta permanentemente junto con sus archivos
     *
     * @param int $id ID de la carpeta
     * @return bool
     */
    public function forceDelete($id) {
        // Iniciar transaccion
        $this->db->beginTransaction();

        try {
            // Obtener archivos de la carpeta
            $sql = "SELECT id, ruta_fisica FROM archivos WHERE carpeta_id = :id";
            $archivos = $this->db->fetchAll($sql, ['id' => $id]);

            // Eliminar archivos fisicos
            foreach ($archivos as $archivo) {
                if (file_exists($archivo['ruta_fisica'])) {
                    unlink($archivo['ruta_fisica']);
                }
            }

            // Eliminar archivos de la base de datos (cascade deberia hacerlo)
            // Eliminar carpeta
            $sql = "DELETE FROM carpetas WHERE id = :id";
            $this->db->execute($sql, ['id' => $id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            logMessage('error', 'Error al eliminar carpeta', ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    //=============================================================================
    // METODOS DE CONSULTA
    //=============================================================================

    /**
     * Obtiene las carpetas de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @param bool $includeInactive Incluir carpetas inactivas
     * @return array
     */
    public function getByUsuario($usuarioId, $includeInactive = false) {
        $sql = "SELECT c.*,
                       COUNT(a.id) as total_archivos,
                       COALESCE(SUM(a.tamano_bytes), 0) as tamano_total
                FROM carpetas c
                LEFT JOIN archivos a ON c.id = a.carpeta_id AND a.en_papelera = 0
                WHERE c.usuario_id = :usuario_id";

        if (!$includeInactive) {
            $sql .= " AND c.activa = 1";
        }

        $sql .= " GROUP BY c.id ORDER BY c.fecha_creacion DESC";

        return $this->db->fetchAll($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * Obtiene una carpeta con sus archivos
     *
     * @param int $id ID de la carpeta
     * @return array|null
     */
    public function getWithFiles($id) {
        $carpeta = $this->findById($id);

        if (!$carpeta) {
            return null;
        }

        $sql = "SELECT * FROM archivos
                WHERE carpeta_id = :id AND en_papelera = 0
                ORDER BY fecha_subida DESC";

        $archivos = $this->db->fetchAll($sql, ['id' => $id]);

        return [
            'carpeta' => $carpeta->toArray(),
            'archivos' => $archivos
        ];
    }

    /**
     * Cuenta las carpetas de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @return int
     */
    public function countByUsuario($usuarioId) {
        $sql = "SELECT COUNT(*) as total FROM carpetas WHERE usuario_id = :usuario_id AND activa = 1";
        $result = $this->db->fetchOne($sql, ['usuario_id' => $usuarioId]);
        return (int)$result['total'];
    }

    /**
     * Verifica si un usuario puede crear mas carpetas
     *
     * @param int $usuarioId ID del usuario
     * @return bool
     */
    public function canCreateFolder($usuarioId) {
        $total = $this->countByUsuario($usuarioId);
        return $total < MAX_FOLDERS_PER_CLIENT;
    }

    /**
     * Verifica si una carpeta pertenece a un usuario
     *
     * @param int $carpetaId ID de la carpeta
     * @param int $usuarioId ID del usuario
     * @return bool
     */
    public function belongsToUser($carpetaId, $usuarioId) {
        $sql = "SELECT id FROM carpetas WHERE id = :carpeta_id AND usuario_id = :usuario_id LIMIT 1";
        $result = $this->db->fetchOne($sql, ['carpeta_id' => $carpetaId, 'usuario_id' => $usuarioId]);
        return !empty($result);
    }

    /**
     * Busca carpetas por nombre
     *
     * @param int $usuarioId ID del usuario
     * @param string $search Termino de busqueda
     * @return array
     */
    public function search($usuarioId, $search) {
        $sql = "SELECT * FROM carpetas
                WHERE usuario_id = :usuario_id
                AND activa = 1
                AND nombre LIKE :search
                ORDER BY nombre ASC";

        return $this->db->fetchAll($sql, [
            'usuario_id' => $usuarioId,
            'search' => '%' . $search . '%'
        ]);
    }

    //=============================================================================
    // METODOS AUXILIARES
    //=============================================================================

    /**
     * Hidrata el objeto con datos de la base de datos
     *
     * @param array $data Datos
     * @return Carpeta
     */
    private function hydrate($data) {
        $carpeta = new self();
        $carpeta->id = (int)$data['id'];
        $carpeta->usuario_id = (int)$data['usuario_id'];
        $carpeta->nombre = $data['nombre'];
        $carpeta->descripcion = $data['descripcion'];
        $carpeta->activa = (bool)$data['activa'];
        $carpeta->fecha_creacion = $data['fecha_creacion'];
        $carpeta->fecha_actualizacion = $data['fecha_actualizacion'];

        return $carpeta;
    }

    //=============================================================================
    // GETTERS
    //=============================================================================

    public function getId() { return $this->id; }
    public function getUsuarioId() { return $this->usuario_id; }
    public function getNombre() { return $this->nombre; }
    public function getDescripcion() { return $this->descripcion; }
    public function getActiva() { return $this->activa; }
    public function getFechaCreacion() { return $this->fecha_creacion; }
    public function getFechaActualizacion() { return $this->fecha_actualizacion; }

    /**
     * Convierte el objeto a array
     *
     * @return array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'activa' => $this->activa,
            'fecha_creacion' => $this->fecha_creacion,
            'fecha_actualizacion' => $this->fecha_actualizacion
        ];
    }
}