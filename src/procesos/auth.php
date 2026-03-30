<?php
/**
 * RK Marketing Drive - Funciones de Autenticacion
 *
 * @package RKMarketingDrive
 * @version 2.0.0
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

use App\Entity\Usuario;
use App\Entity\LogActividad;
use App\Repository\UsuarioRepository;

//=============================================================================
// FUNCIONES DE AUTENTICACION
//=============================================================================

function authenticateUser($email, $password) {
    $email = sanitizeEmail($email);
    if (!$email) {
        return ['success' => false, 'error' => 'Email no valido.'];
    }

    $clientIP = getClientIP();
    $blocked  = isIPBlocked($clientIP);
    if ($blocked) {
        return [
            'success' => false,
            'error'   => 'Demasiados intentos fallidos. Intenta de nuevo en ' .
                         ceil($blocked['remaining_time'] / 60) . ' minutos.'
        ];
    }

    $repo = new UsuarioRepository(em());
    $user = $repo->findByEmail($email);

    if (!$user || !$user->isActivo()) {
        logLoginAttempt($clientIP, $email, false);
        return ['success' => false, 'error' => 'Credenciales incorrectas.'];
    }

    if (!verifyPassword($password, $user->getPasswordHash())) {
        logLoginAttempt($clientIP, $email, false);
        return ['success' => false, 'error' => 'Credenciales incorrectas.'];
    }

    logLoginAttempt($clientIP, $email, true);

    $user->setUltimoAcceso(new \DateTimeImmutable());
    em()->flush();

    return [
        'success' => true,
        'user'    => [
            'id'                    => $user->getId(),
            'nombre'                => $user->getNombre(),
            'email'                 => $user->getEmail(),
            'rol'                   => $user->getRol(),
            'almacenamiento_usado'  => (int)$user->getAlmacenamientoUsado(),
            'almacenamiento_maximo' => (int)$user->getAlmacenamientoMaximo(),
        ]
    ];
}

function loginUser($user) {
    session_regenerate_id(true);

    $_SESSION['user_id']     = (int)$user['id'];
    $_SESSION['user_name']   = $user['nombre'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_role']   = $user['rol'];
    $_SESSION['storage_used']= (int)$user['almacenamiento_usado'];
    $_SESSION['storage_max'] = (int)$user['almacenamiento_maximo'];
    $_SESSION['logged_in_at']= time();

    logActivity($user['id'], 'login', 'Inicio de sesion exitoso', 'usuario', $user['id']);
}

function logoutUser() {
    if (isAuthenticated()) {
        logActivity(getCurrentUserId(), 'logout', 'Cierre de sesion', 'usuario', getCurrentUserId());
    }
    destroySession();
}

function requireAuth($requiredRole = null) {
    if (!isAuthenticated()) {
        redirect('/login');
    }
    if ($requiredRole === 'admin' && !isAdmin()) {
        setFlash('error', 'No tienes permisos para acceder a esta pagina.');
        redirect('/dashboard');
    }
    return true;
}

function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

function isOwner($resourceUserId) {
    return getCurrentUserId() === (int)$resourceUserId || isAdmin();
}

//=============================================================================
// RECUPERACION DE CONTRASENA
//=============================================================================

function generatePasswordResetToken($email) {
    $email = sanitizeEmail($email);
    if (!$email) {
        return ['success' => false, 'error' => 'Email no valido.'];
    }

    $repo = new UsuarioRepository(em());
    $user = $repo->findByEmail($email);

    // Por seguridad, no revelar si el email existe
    if (!$user || !$user->isActivo()) {
        return [
            'success' => true,
            'message' => 'Si el email existe, recibiras instrucciones para recuperar tu contrasena.'
        ];
    }

    $token      = generateSecureToken(32);
    $expiracion = new \DateTimeImmutable('+1 hour');

    $user->setTokenRecuperacion($token);
    $user->setTokenExpiracion($expiracion);
    em()->flush();

    return [
        'success'    => true,
        'token'      => $token,
        'user_name'  => $user->getNombre(),
        'user_email' => $email
    ];
}

function verifyPasswordResetToken($token) {
    $sql = "SELECT id, nombre, email
            FROM usuarios
            WHERE token_recuperacion = :token
              AND token_expiracion > NOW()
              AND activo = 1
            LIMIT 1";

    return em()->getConnection()->executeQuery($sql, ['token' => $token])->fetchAssociative() ?: null;
}

function resetPasswordWithToken($token, $newPassword) {
    $userData = verifyPasswordResetToken($token);
    if (!$userData) {
        return ['success' => false, 'error' => 'Token invalido o expirado.'];
    }

    $validation = validatePassword($newPassword);
    if (!$validation['valid']) {
        return ['success' => false, 'errors' => $validation['errors']];
    }

    $user = em()->find(Usuario::class, (int)$userData['id']);
    $user->setPasswordHash(hashPassword($newPassword));
    $user->setTokenRecuperacion(null);
    $user->setTokenExpiracion(null);
    em()->flush();

    logActivity($userData['id'], 'password_reset', 'Contrasena restablecida', 'usuario', $userData['id']);

    return ['success' => true, 'message' => 'Contrasena actualizada correctamente.'];
}

function changePassword($userId, $currentPassword, $newPassword) {
    $user = em()->find(Usuario::class, (int)$userId);
    if (!$user) {
        return ['success' => false, 'error' => 'Usuario no encontrado.'];
    }

    if (!verifyPassword($currentPassword, $user->getPasswordHash())) {
        return ['success' => false, 'error' => 'La contrasena actual es incorrecta.'];
    }

    $validation = validatePassword($newPassword);
    if (!$validation['valid']) {
        return ['success' => false, 'errors' => $validation['errors']];
    }

    $user->setPasswordHash(hashPassword($newPassword));
    em()->flush();

    logActivity($userId, 'password_change', 'Contrasena cambiada', 'usuario', $userId);

    return ['success' => true, 'message' => 'Contrasena actualizada correctamente.'];
}

//=============================================================================
// GESTION DE USUARIOS
//=============================================================================

function createUser($data) {
    $required = ['nombre', 'email', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'error' => "El campo {$field} es obligatorio."];
        }
    }

    $email = sanitizeEmail($data['email']);
    if (!$email) {
        return ['success' => false, 'error' => 'Email no valido.'];
    }

    $repo = new UsuarioRepository(em());
    if ($repo->findByEmail($email)) {
        return ['success' => false, 'error' => 'El email ya esta registrado.'];
    }

    $validation = validatePassword($data['password']);
    if (!$validation['valid']) {
        return ['success' => false, 'errors' => $validation['errors']];
    }

    $rol = isset($data['rol']) && in_array($data['rol'], ['cliente', 'admin']) ? $data['rol'] : 'cliente';
    $almacenamiento = isset($data['almacenamiento_maximo'])
        ? (string)sanitizeInt($data['almacenamiento_maximo'])
        : (string)MAX_STORAGE_PER_CLIENT;

    $user = new Usuario();
    $user->setNombre(sanitizeString($data['nombre']));
    $user->setEmail($email);
    $user->setPasswordHash(hashPassword($data['password']));
    $user->setRol($rol);
    $user->setAlmacenamientoMaximo($almacenamiento);

    $repo->save($user);

    logActivity(getCurrentUserId(), 'user_create',
        "Usuario creado: {$user->getNombre()} ({$email})", 'usuario', $user->getId());

    return ['success' => true, 'user_id' => $user->getId()];
}

function updateUser($userId, $data) {
    $user = em()->find(Usuario::class, (int)$userId);
    if (!$user) {
        return ['success' => false, 'error' => 'Usuario no encontrado.'];
    }

    $changed = false;

    if (isset($data['nombre'])) {
        $user->setNombre(sanitizeString($data['nombre']));
        $changed = true;
    }

    if (isset($data['email'])) {
        $email = sanitizeEmail($data['email']);
        if (!$email) {
            return ['success' => false, 'error' => 'Email no valido.'];
        }
        $existing = (new UsuarioRepository(em()))->findByEmail($email);
        if ($existing && $existing->getId() !== (int)$userId) {
            return ['success' => false, 'error' => 'El email ya esta en uso.'];
        }
        $user->setEmail($email);
        $changed = true;
    }

    if (isset($data['rol'])) {
        if (!in_array($data['rol'], ['cliente', 'admin'])) {
            return ['success' => false, 'error' => 'Rol no valido.'];
        }
        $user->setRol($data['rol']);
        $changed = true;
    }

    if (isset($data['almacenamiento_maximo'])) {
        $user->setAlmacenamientoMaximo((string)sanitizeInt($data['almacenamiento_maximo']));
        $changed = true;
    }

    if (isset($data['activo'])) {
        $user->setActivo((bool)$data['activo']);
        $changed = true;
    }

    if (!$changed) {
        return ['success' => false, 'error' => 'No hay datos para actualizar.'];
    }

    em()->flush();

    logActivity(getCurrentUserId(), 'user_update', "Usuario actualizado: ID {$userId}", 'usuario', $userId);

    return ['success' => true, 'message' => 'Usuario actualizado correctamente.'];
}

function getUserById($userId) {
    $user = em()->find(Usuario::class, (int)$userId);
    return $user ? $user->toArray() : null;
}

function getAllUsers($rol = null, $activeOnly = false) {
    $filters = [];
    if ($rol)        $filters['rol']    = $rol;
    if ($activeOnly) $filters['activo'] = true;
    return (new UsuarioRepository(em()))->getFiltered($filters);
}

//=============================================================================
// LOG DE ACTIVIDAD
//=============================================================================

function logActivity($userId, $action, $description, $entityType = null, $entityId = null, $extraData = null) {
    try {
        $log = new LogActividad();
        $log->setAccion($action);
        $log->setDescripcion($description);
        $log->setEntidadTipo($entityType);
        $log->setEntidadId($entityId ? (int)$entityId : null);
        $log->setIpAddress(getClientIP());
        $log->setUserAgent(getUserAgent());
        $log->setDatosAdicionales($extraData);

        if ($userId !== null) {
            $userRef = em()->getReference(Usuario::class, (int)$userId);
            $log->setUsuario($userRef);
        }

        em()->persist($log);
        em()->flush();
    } catch (\Throwable $e) {
        logMessage('error', 'Error al registrar actividad', ['error' => $e->getMessage()]);
    }
}

function getUserActivityLog($userId, $limit = 50) {
    $sql = "SELECT * FROM logs_actividad
            WHERE usuario_id = :user_id
            ORDER BY fecha DESC
            LIMIT :limit";

    return em()->getConnection()->executeQuery($sql, ['user_id' => $userId, 'limit' => $limit])
               ->fetchAllAssociative();
}

function getActivityLog($limit = 100, $action = null) {
    $sql = "SELECT l.*, u.nombre AS usuario_nombre, u.email AS usuario_email
            FROM logs_actividad l
            LEFT JOIN usuarios u ON l.usuario_id = u.id
            WHERE 1=1";
    $params = [];

    if ($action) {
        $sql .= " AND l.accion = :action";
        $params['action'] = $action;
    }

    $sql .= " ORDER BY l.fecha DESC LIMIT " . (int)$limit;

    return em()->getConnection()->executeQuery($sql, $params)->fetchAllAssociative();
}
