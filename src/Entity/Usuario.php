<?php
/**
 * RK Marketing Drive - Clase Usuario
 *
 * Modelo para la gestion de usuarios.
 *
 * @package RKMarketingDrive
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class Usuario {
    private $db;
    private $id;
    private $nombre;
    private $email;
    private $rol;
    private $almacenamiento_usado;
    private $almacenamiento_maximo;
    private $activo;
    private $ultimo_acceso;
    private $fecha_creacion;

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
     * Busca un usuario por su ID
     *
     * @param int $id ID del usuario
     * @return Usuario|null
     */
    public function findById($id) {
        $sql = "SELECT * FROM usuarios WHERE id = :id LIMIT 1";
        $data = $this->db->fetchOne($sql, ['id' => $id]);

        if ($data) {
            return $this->hydrate($data);
        }

        return null;
    }

    /**
     * Busca un usuario por su email
     *
     * @param string $email Email del usuario
     * @return Usuario|null
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
        $data = $this->db->fetchOne($sql, ['email' => $email]);

        if ($data) {
            return $this->hydrate($data);
        }

        return null;
    }

    /**
     * Crea un nuevo usuario
     *
     * @param array $data Datos del usuario
     * @return int|false ID del usuario creado o false si falla
     */
    public function create($data) {
        // Validar datos
        $required = ['nombre', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }

        // Verificar email
        $email = sanitizeEmail($data['email']);
        if (!$email) {
            return false;
        }

        // Verificar si ya existe
        if ($this->findByEmail($email)) {
            return false;
        }

        // Hashear contrasena
        $passwordHash = hashPassword($data['password']);

        // Insertar
        $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol, almacenamiento_maximo)
                VALUES (:nombre, :email, :password_hash, :rol, :almacenamiento_maximo)";

        $params = [
            'nombre' => sanitizeString($data['nombre']),
            'email' => $email,
            'password_hash' => $passwordHash,
            'rol' => $data['rol'] ?? 'cliente',
            'almacenamiento_maximo' => $data['almacenamiento_maximo'] ?? MAX_STORAGE_PER_CLIENT
        ];

        return $this->db->insert($sql, $params);
    }

    /**
     * Actualiza un usuario
     *
     * @param int $id ID del usuario
     * @param array $data Datos a actualizar
     * @return bool
     */
    public function update($id, $data) {
        $allowedFields = ['nombre', 'email', 'rol', 'almacenamiento_maximo', 'activo'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'nombre':
                        $updates[] = "nombre = :nombre";
                        $params['nombre'] = sanitizeString($data[$field]);
                        break;
                    case 'email':
                        $updates[] = "email = :email";
                        $params['email'] = sanitizeEmail($data[$field]);
                        break;
                    default:
                        $updates[] = "{$field} = :{$field}";
                        $params[$field] = $data[$field];
                }
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE usuarios SET " . implode(', ', $updates) . " WHERE id = :id";
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Elimina un usuario (soft delete)
     *
     * @param int $id ID del usuario
     * @return bool
     */
    public function delete($id) {
        $sql = "UPDATE usuarios SET activo = 0 WHERE id = :id";
        return $this->db->execute($sql, ['id' => $id]) > 0;
    }

    /**
     * Elimina un usuario permanentemente
     *
     * @param int $id ID del usuario
     * @return bool
     */
    public function forceDelete($id) {
        $sql = "DELETE FROM usuarios WHERE id = :id";
        return $this->db->execute($sql, ['id' => $id]) > 0;
    }

    //=============================================================================
    // METODOS DE AUTENTICACION
    //=============================================================================

    /**
     * Verifica las credenciales de un usuario
     *
     * @param string $email Email
     * @param string $password Contrasena
     * @return Usuario|null
     */
    public function verifyCredentials($email, $password) {
        $user = $this->findByEmail($email);

        if (!$user || !$user->activo) {
            return null;
        }

        if (verifyPassword($password, $user->password_hash)) {
            return $user;
        }

        return null;
    }

    /**
     * Actualiza el ultimo acceso del usuario
     *
     * @param int $id ID del usuario
     * @return bool
     */
    public function updateLastAccess($id) {
        $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id";
        return $this->db->execute($sql, ['id' => $id]) > 0;
    }

    /**
     * Actualiza el almacenamiento usado
     *
     * @param int $id ID del usuario
     * @param int $bytes Bytes a agregar/restar
     * @param bool $add Si es para agregar o restar
     * @return bool
     */
    public function updateStorage($id, $bytes, $add = true) {
        $operator = $add ? '+' : '-';
        $sql = "UPDATE usuarios
                SET almacenamiento_usado = almacenamiento_usado {$operator} :bytes
                WHERE id = :id";
        return $this->db->execute($sql, ['bytes' => $bytes, 'id' => $id]) > 0;
    }

    //=============================================================================
    // METODOS DE CONSULTA
    //=============================================================================

    /**
     * Obtiene todos los usuarios
     *
     * @param array $filters Filtros opcionales
     * @return array
     */
    public function getAll($filters = []) {
        $sql = "SELECT id, nombre, email, rol, almacenamiento_usado, almacenamiento_maximo,
                       activo, ultimo_acceso, fecha_creacion
                FROM usuarios
                WHERE 1=1";
        $params = [];

        if (isset($filters['rol'])) {
            $sql .= " AND rol = :rol";
            $params['rol'] = $filters['rol'];
        }

        if (isset($filters['activo'])) {
            $sql .= " AND activo = :activo";
            $params['activo'] = $filters['activo'] ? 1 : 0;
        }

        if (isset($filters['search'])) {
            $sql .= " AND (nombre LIKE :search OR email LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY fecha_creacion DESC";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params['limit'] = $filters['limit'];
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Cuenta el total de usuarios
     *
     * @param array $filters Filtros opcionales
     * @return int
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE 1=1";
        $params = [];

        if (isset($filters['rol'])) {
            $sql .= " AND rol = :rol";
            $params['rol'] = $filters['rol'];
        }

        if (isset($filters['activo'])) {
            $sql .= " AND activo = :activo";
            $params['activo'] = $filters['activo'] ? 1 : 0;
        }

        $result = $this->db->fetchOne($sql, $params);
        return (int)$result['total'];
    }

    /**
     * Obtiene clientes con sus estadisticas
     *
     * @return array
     */
    public function getClientsWithStats() {
        $sql = "SELECT u.id, u.nombre, u.email, u.almacenamiento_usado, u.almacenamiento_maximo,
                       u.ultimo_acceso, u.fecha_creacion,
                       COUNT(DISTINCT c.id) as total_carpetas,
                       COUNT(a.id) as total_archivos
                FROM usuarios u
                LEFT JOIN carpetas c ON u.id = c.usuario_id AND c.activa = 1
                LEFT JOIN archivos a ON u.id = a.usuario_id AND a.en_papelera = 0
                WHERE u.rol = 'cliente'
                GROUP BY u.id
                ORDER BY u.fecha_creacion DESC";

        return $this->db->fetchAll($sql);
    }

    //=============================================================================
    // METODOS DE ALMACENAMIENTO
    //=============================================================================

    /**
     * Verifica si el usuario tiene espacio disponible
     *
     * @param int $id ID del usuario
     * @param int $bytes Bytes a verificar
     * @return bool
     */
    public function hasSpace($id, $bytes) {
        $user = $this->findById($id);

        if (!$user) {
            return false;
        }

        return ($user->almacenamiento_usado + $bytes) <= $user->almacenamiento_maximo;
    }

    /**
     * Obtiene el porcentaje de almacenamiento usado
     *
     * @param int $id ID del usuario
     * @return float
     */
    public function getStoragePercentage($id) {
        $user = $this->findById($id);

        if (!$user || $user->almacenamiento_maximo == 0) {
            return 0;
        }

        return round(($user->almacenamiento_usado / $user->almacenamiento_maximo) * 100, 2);
    }

    //=============================================================================
    // METODOS AUXILIARES
    //=============================================================================

    /**
     * Hidrata el objeto con datos de la base de datos
     *
     * @param array $data Datos
     * @return Usuario
     */
    private function hydrate($data) {
        $user = new self();
        $user->id = (int)$data['id'];
        $user->nombre = $data['nombre'];
        $user->email = $data['email'];
        $user->password_hash = $data['password_hash'] ?? null;
        $user->rol = $data['rol'];
        $user->almacenamiento_usado = (int)$data['almacenamiento_usado'];
        $user->almacenamiento_maximo = (int)$data['almacenamiento_maximo'];
        $user->activo = (bool)$data['activo'];
        $user->ultimo_acceso = $data['ultimo_acceso'];
        $user->fecha_creacion = $data['fecha_creacion'];

        return $user;
    }

    //=============================================================================
    // GETTERS
    //=============================================================================

    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getEmail() { return $this->email; }
    public function getRol() { return $this->rol; }
    public function getAlmacenamientoUsado() { return $this->almacenamiento_usado; }
    public function getAlmacenamientoMaximo() { return $this->almacenamiento_maximo; }
    public function getActivo() { return $this->activo; }
    public function getUltimoAcceso() { return $this->ultimo_acceso; }
    public function getFechaCreacion() { return $this->fecha_creacion; }

    /**
     * Convierte el objeto a array
     *
     * @return array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'rol' => $this->rol,
            'almacenamiento_usado' => $this->almacenamiento_usado,
            'almacenamiento_maximo' => $this->almacenamiento_maximo,
            'activo' => $this->activo,
            'ultimo_acceso' => $this->ultimo_acceso,
            'fecha_creacion' => $this->fecha_creacion
        ];
    }
}