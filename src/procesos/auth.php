<?php
/**
 * RK Marketing Drive - Funciones de Autenticacion
 *
 * Este archivo contiene todas las funciones relacionadas con autenticacion:
 * login, logout, verificacion de sesion, etc.
 *
 * @package RKMarketingDrive
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

//=============================================================================
// FUNCIONES DE AUTENTICACION
//=============================================================================

/**
 * Intenta autenticar un usuario
 *
 * @param string $email Email del usuario
 * @param string $password Contrasena en texto plano
 * @return array Resultado con 'success' y datos del usuario o 'error'
 */
function authenticateUser($email, $password) {
    $db = db();

    // Sanitizar email
    $email = sanitizeEmail($email);
    if (!$email) {
        return [
            'success' => false,
            'error' => 'Email no valido.'
        ];
    }

    // Verificar si la IP esta bloqueada
    $clientIP = getClientIP();
    $blocked = isIPBlocked($clientIP);

    if ($blocked) {
        return [
            'success' => false,
            'error' => 'Demasiados intentos fallidos. Intenta de nuevo en ' .
                       ceil($blocked['remaining_time'] / 60) . ' minutos.'
        ];
    }

    // Buscar usuario
    $sql = "SELECT id, nombre, email, password_hash, rol, activo,
                   almacenamiento_usado, almacenamiento_maximo
            FROM usuarios
            WHERE email = :email AND activo = 1
            LIMIT 1";

    $user = $db->fetchOne($sql, ['email' => $email]);

    if (!$user) {
        // Registrar intento fallido
        logLoginAttempt($clientIP, $email, false);

        // Mensaje generico para no revelar si existe el email
        return [
            'success' => false,
            'error' => 'Credenciales incorrectas.'
        ];
    }

    // Verificar contrasena
    if (!verifyPassword($password, $user['password_hash'])) {
        // Registrar intento fallido
        logLoginAttempt($clientIP, $email, false);

        return [
            'success' => false,
            'error' => 'Credenciales incorrectas.'
        ];
    }

    // Registrar intento exitoso
    logLoginAttempt($clientIP, $email, true);

    // Actualizar ultimo acceso
    $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id";
    $db->execute($sql, ['id' => $user['id']]);

    // Limpiar datos sensibles
    unset($user['password_hash']);

    return [
        'success' => true,
        'user' => $user
    ];
}

/**
 * Inicia la sesion de un usuario
 *
 * @param array $user Datos del usuario
 */
function loginUser($user) {
    // Regenerar ID de sesion para prevenir session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['nombre'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['rol'];
    $_SESSION['storage_used'] = (int)$user['almacenamiento_usado'];
    $_SESSION['storage_max'] = (int)$user['almacenamiento_maximo'];
    $_SESSION['logged_in_at'] = time();

    // Registrar log de actividad
    logActivity(
        $user['id'],
        'login',
        'Inicio de sesion exitoso',
        'usuario',
        $user['id']
    );
}

/**
 * Cierra la sesion del usuario
 */
function logoutUser() {
    // Registrar log antes de cerrar sesion
    if (isAuthenticated()) {
        logActivity(
            getCurrentUserId(),
            'logout',
            'Cierre de sesion',
            'usuario',
            getCurrentUserId()
        );
    }

    // Destruir sesion
    destroySession();
}

/**
 * Verifica si el usuario actual tiene acceso
 *
 * @param string $requiredRole Rol requerido ('cliente' o 'admin')
 * @return bool
 */
function requireAuth($requiredRole = null) {
    // Verificar si esta autenticado
    if (!isAuthenticated()) {
        redirect('/login');
    }

    // Verificar rol si se especifica
    if ($requiredRole !== null) {
        if ($requiredRole === 'admin' && !isAdmin()) {
            setFlash('error', 'No tienes permisos para acceder a esta pagina.');
            redirect('/dashboard');
        }
    }

    return true;
}

/**
 * Verifica si el usuario tiene un rol especifico
 *
 * @param string $role Rol a verificar
 * @return bool
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Verifica si el usuario es propietario de un recurso
 *
 * @param int $resourceUserId ID del usuario propietario
 * @return bool
 */
function isOwner($resourceUserId) {
    return getCurrentUserId() === (int)$resourceUserId || isAdmin();
}

//=============================================================================
// FUNCIONES DE RECUPERACION DE CONTRASENA
//=============================================================================

/**
 * Genera un token de recuperacion de contrasena
 *
 * @param string $email Email del usuario
 * @return array Resultado con 'success' y 'token' o 'error'
 */
function generatePasswordResetToken($email) {
    $db = db();

    // Sanitizar email
    $email = sanitizeEmail($email);
    if (!$email) {
        return [
            'success' => false,
            'error' => 'Email no valido.'
        ];
    }

    // Buscar usuario
    $sql = "SELECT id, nombre FROM usuarios WHERE email = :email AND activo = 1 LIMIT 1";
    $user = $db->fetchOne($sql, ['email' => $email]);

    if (!$user) {
        // Por seguridad, no revelar si el email existe
        return [
            'success' => true,
            'message' => 'Si el email existe, recibiras instrucciones para recuperar tu contrasena.'
        ];
    }

    // Generar token
    $token = generateSecureToken(32);
    $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Guardar token
    $sql = "UPDATE usuarios
            SET token_recuperacion = :token, token_expiracion = :expiration
            WHERE id = :id";

    $db->execute($sql, [
        'token' => $token,
        'expiration' => $expiration,
        'id' => $user['id']
    ]);

    return [
        'success' => true,
        'token' => $token,
        'user_name' => $user['nombre'],
        'user_email' => $email
    ];
}

/**
 * Verifica un token de recuperacion
 *
 * @param string $token Token a verificar
 * @return array|null Datos del usuario o null si es invalido
 */
function verifyPasswordResetToken($token) {
    $db = db();

    $sql = "SELECT id, nombre, email
            FROM usuarios
            WHERE token_recuperacion = :token
            AND token_expiracion > NOW()
            AND activo = 1
            LIMIT 1";

    return $db->fetchOne($sql, ['token' => $token]);
}

/**
 * Restablece la contrasena con un token
 *
 * @param string $token Token de recuperacion
 * @param string $newPassword Nueva contrasena
 * @return array Resultado con 'success' o 'error'
 */
function resetPasswordWithToken($token, $newPassword) {
    $db = db();

    // Verificar token
    $user = verifyPasswordResetToken($token);

    if (!$user) {
        return [
            'success' => false,
            'error' => 'Token invalido o expirado.'
        ];
    }

    // Validar nueva contrasena
    $validation = validatePassword($newPassword);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'errors' => $validation['errors']
        ];
    }

    // Hashear nueva contrasena
    $passwordHash = hashPassword($newPassword);

    // Actualizar contrasena y limpiar token
    $sql = "UPDATE usuarios
            SET password_hash = :hash, token_recuperacion = NULL, token_expiracion = NULL
            WHERE id = :id";

    $db->execute($sql, [
        'hash' => $passwordHash,
        'id' => $user['id']
    ]);

    // Registrar actividad
    logActivity(
        $user['id'],
        'password_reset',
        'Contrasena restablecida',
        'usuario',
        $user['id']
    );

    return [
        'success' => true,
        'message' => 'Contrasena actualizada correctamente.'
    ];
}

/**
 * Cambia la contrasena del usuario actual
 *
 * @param int $userId ID del usuario
 * @param string $currentPassword Contrasena actual
 * @param string $newPassword Nueva contrasena
 * @return array Resultado con 'success' o 'error'
 */
function changePassword($userId, $currentPassword, $newPassword) {
    $db = db();

    // Obtener contrasena actual
    $sql = "SELECT password_hash FROM usuarios WHERE id = :id LIMIT 1";
    $user = $db->fetchOne($sql, ['id' => $userId]);

    if (!$user) {
        return [
            'success' => false,
            'error' => 'Usuario no encontrado.'
        ];
    }

    // Verificar contrasena actual
    if (!verifyPassword($currentPassword, $user['password_hash'])) {
        return [
            'success' => false,
            'error' => 'La contrasena actual es incorrecta.'
        ];
    }

    // Validar nueva contrasena
    $validation = validatePassword($newPassword);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'errors' => $validation['errors']
        ];
    }

    // Hashear nueva contrasena
    $passwordHash = hashPassword($newPassword);

    // Actualizar contrasena
    $sql = "UPDATE usuarios SET password_hash = :hash WHERE id = :id";
    $db->execute($sql, [
        'hash' => $passwordHash,
        'id' => $userId
    ]);

    // Registrar actividad
    logActivity(
        $userId,
        'password_change',
        'Contrasena cambiada',
        'usuario',
        $userId
    );

    return [
        'success' => true,
        'message' => 'Contrasena actualizada correctamente.'
    ];
}

//=============================================================================
// FUNCIONES DE GESTION DE USUARIOS
//=============================================================================

/**
 * Crea un nuevo usuario
 *
 * @param array $data Datos del usuario
 * @return array Resultado con 'success' y 'user_id' o 'error'
 */
function createUser($data) {
    $db = db();

    // Validar datos obligatorios
    $required = ['nombre', 'email', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return [
                'success' => false,
                'error' => "El campo {$field} es obligatorio."
            ];
        }
    }

    // Validar email
    $email = sanitizeEmail($data['email']);
    if (!$email) {
        return [
            'success' => false,
            'error' => 'Email no valido.'
        ];
    }

    // Verificar si el email ya existe
    $sql = "SELECT id FROM usuarios WHERE email = :email LIMIT 1";
    $existing = $db->fetchOne($sql, ['email' => $email]);

    if ($existing) {
        return [
            'success' => false,
            'error' => 'El email ya esta registrado.'
        ];
    }

    // Validar contrasena
    $validation = validatePassword($data['password']);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'errors' => $validation['errors']
        ];
    }

    // Preparar datos
    $nombre = sanitizeString($data['nombre']);
    $passwordHash = hashPassword($data['password']);
    $rol = isset($data['rol']) && in_array($data['rol'], ['cliente', 'admin']) ? $data['rol'] : 'cliente';
    $almacenamientoMaximo = isset($data['almacenamiento_maximo'])
        ? sanitizeInt($data['almacenamiento_maximo'])
        : MAX_STORAGE_PER_CLIENT;

    // Insertar usuario
    $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol, almacenamiento_maximo)
            VALUES (:nombre, :email, :hash, :rol, :almacenamiento)";

    $userId = $db->insert($sql, [
        'nombre' => $nombre,
        'email' => $email,
        'hash' => $passwordHash,
        'rol' => $rol,
        'almacenamiento' => $almacenamientoMaximo
    ]);

    // Registrar actividad
    logActivity(
        getCurrentUserId(),
        'user_create',
        "Usuario creado: {$nombre} ({$email})",
        'usuario',
        $userId
    );

    return [
        'success' => true,
        'user_id' => $userId
    ];
}

/**
 * Actualiza un usuario
 *
 * @param int $userId ID del usuario
 * @param array $data Datos a actualizar
 * @return array Resultado con 'success' o 'error'
 */
function updateUser($userId, $data) {
    $db = db();

    // Verificar que el usuario existe
    $sql = "SELECT id FROM usuarios WHERE id = :id LIMIT 1";
    $user = $db->fetchOne($sql, ['id' => $userId]);

    if (!$user) {
        return [
            'success' => false,
            'error' => 'Usuario no encontrado.'
        ];
    }

    $updates = [];
    $params = ['id' => $userId];

    // Campos permitidos para actualizar
    $allowedFields = ['nombre', 'email', 'rol', 'almacenamiento_maximo', 'activo'];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            switch ($field) {
                case 'nombre':
                    $updates[] = "nombre = :nombre";
                    $params['nombre'] = sanitizeString($data[$field]);
                    break;

                case 'email':
                    $email = sanitizeEmail($data[$field]);
                    if (!$email) {
                        return ['success' => false, 'error' => 'Email no valido.'];
                    }
                    // Verificar que no exista otro usuario con el mismo email
                    $sql = "SELECT id FROM usuarios WHERE email = :email AND id != :id LIMIT 1";
                    $existing = $db->fetchOne($sql, ['email' => $email, 'id' => $userId]);
                    if ($existing) {
                        return ['success' => false, 'error' => 'El email ya esta en uso.'];
                    }
                    $updates[] = "email = :email";
                    $params['email'] = $email;
                    break;

                case 'rol':
                    if (!in_array($data[$field], ['cliente', 'admin'])) {
                        return ['success' => false, 'error' => 'Rol no valido.'];
                    }
                    $updates[] = "rol = :rol";
                    $params['rol'] = $data[$field];
                    break;

                case 'almacenamiento_maximo':
                    $updates[] = "almacenamiento_maximo = :almacenamiento";
                    $params['almacenamiento'] = sanitizeInt($data[$field]);
                    break;

                case 'activo':
                    $updates[] = "activo = :activo";
                    $params['activo'] = $data[$field] ? 1 : 0;
                    break;
            }
        }
    }

    if (empty($updates)) {
        return [
            'success' => false,
            'error' => 'No hay datos para actualizar.'
        ];
    }

    $sql = "UPDATE usuarios SET " . implode(', ', $updates) . " WHERE id = :id";
    $db->execute($sql, $params);

    // Registrar actividad
    logActivity(
        getCurrentUserId(),
        'user_update',
        "Usuario actualizado: ID {$userId}",
        'usuario',
        $userId
    );

    return [
        'success' => true,
        'message' => 'Usuario actualizado correctamente.'
    ];
}

/**
 * Obtiene un usuario por su ID
 *
 * @param int $userId ID del usuario
 * @return array|null Datos del usuario o null
 */
function getUserById($userId) {
    $db = db();

    $sql = "SELECT id, nombre, email, rol, almacenamiento_usado, almacenamiento_maximo,
                   activo, ultimo_acceso, fecha_creacion
            FROM usuarios
            WHERE id = :id
            LIMIT 1";

    return $db->fetchOne($sql, ['id' => $userId]);
}

/**
 * Obtiene todos los usuarios
 *
 * @param string $rol Filtrar por rol (opcional)
 * @param bool $activeOnly Solo usuarios activos
 * @return array Lista de usuarios
 */
function getAllUsers($rol = null, $activeOnly = false) {
    $db = db();

    $sql = "SELECT id, nombre, email, rol, almacenamiento_usado, almacenamiento_maximo,
                   activo, ultimo_acceso, fecha_creacion
            FROM usuarios
            WHERE 1=1";

    $params = [];

    if ($rol) {
        $sql .= " AND rol = :rol";
        $params['rol'] = $rol;
    }

    if ($activeOnly) {
        $sql .= " AND activo = 1";
    }

    $sql .= " ORDER BY fecha_creacion DESC";

    return $db->fetchAll($sql, $params);
}

//=============================================================================
// FUNCIONES DE LOG DE ACTIVIDAD
//=============================================================================

/**
 * Registra una actividad en el log
 *
 * @param int|null $userId ID del usuario (null para sistema)
 * @param string $action Codigo de accion
 * @param string $description Descripcion de la accion
 * @param string|null $entityType Tipo de entidad afectada
 * @param int|null $entityId ID de la entidad afectada
 * @param array|null $extraData Datos adicionales
 */
function logActivity($userId, $action, $description, $entityType = null, $entityId = null, $extraData = null) {
    $db = db();

    $sql = "INSERT INTO logs_actividad
            (usuario_id, accion, descripcion, entidad_tipo, entidad_id, ip_address, user_agent, datos_adicionales)
            VALUES (:user_id, :action, :description, :entity_type, :entity_id, :ip, :user_agent, :extra_data)";

    $db->insert($sql, [
        'user_id' => $userId,
        'action' => $action,
        'description' => $description,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'ip' => getClientIP(),
        'user_agent' => getUserAgent(),
        'extra_data' => $extraData ? json_encode($extraData) : null
    ]);
}

/**
 * Obtiene el log de actividad de un usuario
 *
 * @param int $userId ID del usuario
 * @param int $limit Limite de registros
 * @return array Lista de actividades
 */
function getUserActivityLog($userId, $limit = 50) {
    $db = db();

    $sql = "SELECT * FROM logs_actividad
            WHERE usuario_id = :user_id
            ORDER BY fecha DESC
            LIMIT :limit";

    return $db->fetchAll($sql, [
        'user_id' => $userId,
        'limit' => $limit
    ]);
}

/**
 * Obtiene el log de actividad general
 *
 * @param int $limit Limite de registros
 * @param string|null $action Filtrar por accion
 * @return array Lista de actividades
 */
function getActivityLog($limit = 100, $action = null) {
    $db = db();

    $sql = "SELECT l.*, u.nombre as usuario_nombre, u.email as usuario_email
            FROM logs_actividad l
            LEFT JOIN usuarios u ON l.usuario_id = u.id
            WHERE 1=1";

    $params = [];

    if ($action) {
        $sql .= " AND l.accion = :action";
        $params['action'] = $action;
    }

    $sql .= " ORDER BY l.fecha DESC LIMIT :limit";
    $params['limit'] = $limit;

    return $db->fetchAll($sql, $params);
}