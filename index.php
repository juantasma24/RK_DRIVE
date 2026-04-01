<?php
/**
 * RK Marketing Drive - Punto de Entrada
 *
 * Este archivo es el punto de entrada principal de la aplicacion.
 * Carga la configuracion, inicializa la sesion y maneja el routing basico.
 *
 * @package RKMarketingDrive
 * @version 1.0.0
 */

//=============================================================================
// CONFIGURACION INICIAL
//=============================================================================

// Activar output buffering para evitar "headers already sent"
ob_start();

// Definir constante para verificar que la aplicacion se inicia correctamente
define('APP_STARTED', true);

//=============================================================================
// CARGAR ARCHIVOS NECESARIOS (antes de usar cualquier funcion)
//=============================================================================

// Composer autoloader (Doctrine + PSR-4 App\)
require_once __DIR__ . '/vendor/autoload.php';

// Configuracion global
if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

// Funciones auxiliares
require_once __DIR__ . '/src/procesos/functions.php';

// Funciones de seguridad
require_once __DIR__ . '/src/procesos/security.php';

// Funciones de autenticacion
require_once __DIR__ . '/src/procesos/auth.php';

// Configuracion de base de datos
require_once __DIR__ . '/config/database.php';

// Doctrine ORM - EntityManager
require_once __DIR__ . '/config/doctrine.php';

// Configuracion de email
require_once __DIR__ . '/config/mail.php';

// Establecer headers de seguridad
setSecurityHeaders();

// Iniciar sesion segura
startSecureSession();

//=============================================================================
// AUTOLOAD DE CLASES
//=============================================================================

// Solo controladores sin namespace (las entidades App\ las carga el autoloader de Composer)
spl_autoload_register(function ($className) {
    if (str_contains($className, '\\')) {
        return;
    }
    $file = __DIR__ . '/src/controllers/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

//=============================================================================
// CONEXION A BASE DE DATOS
//=============================================================================

try {
    $database = db();
} catch (Exception $e) {
    logMessage('error', 'Error de conexion a base de datos', ['error' => $e->getMessage()]);
    die('Error de conexion. Por favor, intente mas tarde.');
}

//=============================================================================
// ROUTING BASICO
//=============================================================================

// Obtener la pagina solicitada
$page = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

// Sanitizar inputs
$page = sanitizeString($page);
$action = sanitizeString($action);
if ($id !== null) {
    $id = sanitizeInt($id);
}

// Rutas permitidas
$publicRoutes = ['login', 'register', 'forgot-password', 'reset-password', 'logout'];
$clientRoutes = ['dashboard', 'folders', 'files', 'trash', 'profile', 'notifications'];
$adminRoutes  = ['admin', 'admin/users', 'admin/files', 'admin/logs', 'admin/settings', 'admin/clients'];
$workerRoutes = ['worker/clients'];

//=============================================================================
// VERIFICAR AUTENTICACION
//=============================================================================

$isLoggedIn = isAuthenticated();
$userRole = $_SESSION['user_role'] ?? null;

// Si no esta autenticado y trata de acceder a una ruta protegida
if (!$isLoggedIn && !in_array($page, $publicRoutes)) {
    redirect('/login');
}

// Si esta autenticado y trata de acceder a login/register
if ($isLoggedIn && in_array($page, ['login', 'register'])) {
    redirect('/dashboard');
}

// Verificar permisos de administrador
if (in_array($page, $adminRoutes) && $userRole !== 'admin') {
    setFlash('error', 'No tienes permisos para acceder a esta pagina.');
    redirect('/?page=dashboard');
}

// Verificar permisos de trabajador
if (in_array($page, $workerRoutes) && !in_array($userRole, ['trabajador', 'admin'])) {
    setFlash('error', 'No tienes permisos para acceder a esta pagina.');
    redirect('/?page=dashboard');
}

// Redirigir trabajadores que intenten acceder a rutas de cliente
$clientRoutesRestrictedForWorker = array_diff($clientRoutes, ['profile', 'notifications']);
if ($isLoggedIn && $userRole === 'trabajador' && in_array($page, $clientRoutesRestrictedForWorker)) {
    redirect('/?page=worker/clients');
}

// No-cache para páginas autenticadas
if ($isLoggedIn) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}

//=============================================================================
// INCLUIR CONTROLADOR Y VISTA
//=============================================================================

// Variable para almacenar datos para la vista
$viewData = [];

// Determinar que controlador cargar
switch ($page) {
    // Rutas publicas
    case 'login':
        $pageTitle = 'Iniciar Sesion';
        include __DIR__ . '/src/controllers/AuthController.php';
        $controller = new AuthController();
        $viewData = $controller->login();
        break;

    case 'register':
        $pageTitle = 'Registro';
        include __DIR__ . '/src/controllers/AuthController.php';
        $controller = new AuthController();
        $viewData = $controller->register();
        break;

    case 'forgot-password':
        $pageTitle = 'Recuperar Contrasena';
        include __DIR__ . '/src/controllers/AuthController.php';
        $controller = new AuthController();
        $viewData = $controller->forgotPassword();
        break;

    case 'reset-password':
        $pageTitle = 'Restablecer Contrasena';
        include __DIR__ . '/src/controllers/AuthController.php';
        $controller = new AuthController();
        $viewData = $controller->resetPassword();
        break;

    case 'logout':
        include __DIR__ . '/src/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;

    // Rutas de cliente
    case 'dashboard':
        $pageTitle = 'Panel Principal';
        include __DIR__ . '/src/controllers/DashboardController.php';
        $controller = new DashboardController();
        $viewData = $controller->index();
        break;

    case 'folders':
        $pageTitle = 'Mis Carpetas';
        include __DIR__ . '/src/controllers/CarpetaController.php';
        $controller = new CarpetaController();
        $viewData = $controller->handleRequest($action, $id);
        break;

    case 'files':
        $pageTitle = 'Archivos';
        include __DIR__ . '/src/controllers/ArchivoController.php';
        $controller = new ArchivoController();
        $viewData = $controller->handleRequest($action, $id);
        break;

    case 'trash':
        $pageTitle = 'Papelera';
        include __DIR__ . '/src/controllers/ArchivoController.php';
        $controller = new ArchivoController();
        $viewData = $controller->trash();
        break;

    case 'profile':
        $pageTitle = 'Mi Perfil';
        include __DIR__ . '/src/controllers/UsuarioController.php';
        $controller = new UsuarioController();
        $viewData = $controller->profile();
        break;

    case 'notifications':
        $pageTitle = 'Notificaciones';
        include __DIR__ . '/src/controllers/NotificacionController.php';
        $controller = new NotificacionController();
        $viewData = $controller->index();
        break;

    // Rutas de administrador
    case 'admin':
    case 'admin/users':
        $pageTitle = 'Gestion de Usuarios';
        include __DIR__ . '/src/controllers/AdminController.php';
        $controller = new AdminController();
        $viewData = $controller->users($action, $id);
        break;

    case 'admin/files':
        $pageTitle = 'Gestion de Archivos';
        include __DIR__ . '/src/controllers/AdminController.php';
        $controller = new AdminController();
        $viewData = $controller->files($action, $id);
        break;

    case 'admin/logs':
        $pageTitle = 'Logs de Actividad';
        include __DIR__ . '/src/controllers/AdminController.php';
        $controller = new AdminController();
        $viewData = $controller->logs();
        break;

    case 'admin/settings':
        $pageTitle = 'Configuracion';
        include __DIR__ . '/src/controllers/AdminController.php';
        $controller = new AdminController();
        $viewData = $controller->settings();
        break;

    case 'admin/clients':
        $pageTitle = 'Archivos por Cliente';
        include __DIR__ . '/src/controllers/AdminController.php';
        $controller = new AdminController();
        $viewData = $controller->clients($action, $id);
        break;

    case 'worker/clients':
        $pageTitle = 'Archivos por Cliente';
        include __DIR__ . '/src/controllers/TrabajadorController.php';
        $controller = new TrabajadorController();
        $viewData = $controller->clients($action, $id);
        break;

    // Ruta por defecto
    default:
        $pageTitle = 'Pagina no encontrada';
        http_response_code(404);
        $viewData = [
            'view' => 'errors/404',
            'title' => 'Pagina no encontrada'
        ];
}

//=============================================================================
// RENDERIZAR VISTA
//=============================================================================

// Inicializacion defensiva de viewData
if (!is_array($viewData)) {
    $viewData = [];
}

// Extraer datos para la vista
extract($viewData);

// Determinar que vista cargar
if (isset($viewData['view'])) {
    $viewFile = __DIR__ . '/public/' . $viewData['view'] . '.php';
} else {
    $viewFile = __DIR__ . '/public/' . $page . '/' . $action . '.php';
}

// Verificar si existe la vista
if (file_exists($viewFile)) {
    // Incluir layout si es necesario
    if (!isset($viewData['skip_layout'])) {
        include __DIR__ . '/src/components/header.php';
    }

    include $viewFile;

    if (!isset($viewData['skip_layout'])) {
        include __DIR__ . '/src/components/footer.php';
    }
} else {
    // Vista no encontrada - con layout completo
    http_response_code(404);
    $pageTitle = 'Pagina no encontrada';
    $viewData['message'] = 'Lo sentimos, la pagina que buscas no existe o ha sido movida.';
    include __DIR__ . '/src/components/header.php';
    include __DIR__ . '/public/errors/404.php';
    include __DIR__ . '/src/components/footer.php';
}

//=============================================================================
// CERRAR CONEXION
//=============================================================================

// Limpiar recursos
if (isset($database)) {
    $database = null;
}
