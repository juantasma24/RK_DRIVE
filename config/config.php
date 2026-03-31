<?php
/**
 * RK Marketing Drive - Configuracion Global
 *
 * Este archivo contiene las constantes y configuraciones globales de la aplicacion.
 * Incluir este archivo al inicio de cada script.
 *
 * @package RKMarketingDrive
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

//=============================================================================
// CONFIGURACION DEL SERVIDOR
//=============================================================================

// Modo desarrollo/produccion
define('APP_ENV', 'development'); // 'development' o 'production'

// Zona horaria
date_default_timezone_set('Europe/Madrid');

// Mostrar errores segun entorno
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

//=============================================================================
// RUTAS DE LA APLICACION
//=============================================================================

// Ruta base del proyecto (sin barra final)
define('APP_ROOT', dirname(__DIR__));

// URL base del proyecto
define('APP_URL', 'http://localhost/RK_DRIVE');

// Nombre de la aplicacion
define('APP_NAME', 'RK Marketing Drive');

// Version de la aplicacion
define('APP_VERSION', '1.2.0');

//=============================================================================
// CONFIGURACION DE SESION
//=============================================================================

// Nombre de la sesion
define('SESSION_NAME', 'RKDriveSession');

// Tiempo de vida de la sesion en segundos (2 horas)
define('SESSION_LIFETIME', 7200);

// Tiempo de inactividad maximo antes de cerrar sesion (30 minutos)
define('SESSION_IDLE_TIMEOUT', 1800);

//=============================================================================
// CONFIGURACION DE ARCHIVOS
//=============================================================================

// Directorio de uploads (fuera del document root idealmente)
define('UPLOAD_DIR', APP_ROOT . '/uploads');

// Directorio de papelera
define('TRASH_DIR', APP_ROOT . '/trash');

// Tamano maximo por archivo en bytes (500 MB)
define('MAX_FILE_SIZE', 500 * 1024 * 1024);

// Tamano maximo de almacenamiento por cliente en bytes (2 GB)
define('MAX_STORAGE_PER_CLIENT', 2 * 1024 * 1024 * 1024);

// Extensiones permitidas
define('ALLOWED_EXTENSIONS', json_encode([
    // Imagenes
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico',
    // Documentos
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'txt', 'csv',
    // Video
    'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv',
    // Audio
    'mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a',
    // Archivos comprimidos
    'zip', 'rar', '7z', 'tar', 'gz'
]));

// Tipos MIME permitidos
define('ALLOWED_MIME_TYPES', json_encode([
    // Imagenes
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp', 'image/x-icon',
    // Documentos
    'text/plain', 'text/csv',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/vnd.oasis.opendocument.text',
    'application/vnd.oasis.opendocument.spreadsheet',
    'application/vnd.oasis.opendocument.presentation',
    // Video
    'video/mp4', 'video/x-msvideo', 'video/quicktime', 'video/x-ms-wmv',
    'video/x-flv', 'video/webm', 'video/x-matroska',
    // Audio
    'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/flac', 'audio/aac', 'audio/x-m4a',
    // Archivos comprimidos
    'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
    'application/x-tar', 'application/gzip'
]));

// Maximo de carpetas por cliente
define('MAX_FOLDERS_PER_CLIENT', 20);

//=============================================================================
// CONFIGURACION DE SEGURIDAD
//=============================================================================

// Clave secreta para CSRF tokens
define('CSRF_SECRET', 'rk_drive_csrf_secret_key_change_in_production');

// Maximo intentos de login
define('MAX_LOGIN_ATTEMPTS', 5);

// Tiempo de bloqueo en segundos (15 minutos)
define('LOGIN_LOCKOUT_TIME', 900);

//=============================================================================
// CONFIGURACION DE LIMPIEZA AUTOMATICA
//=============================================================================

// Dias de conservacion de archivos por defecto
define('DEFAULT_CONSERVATION_DAYS', 30);

//=============================================================================
// CONFIGURACION DE LOGS
//=============================================================================

// Directorio de logs
define('LOG_DIR', APP_ROOT . '/logs');

// Nivel de log ('debug', 'info', 'warning', 'error')
define('LOG_LEVEL', APP_ENV === 'development' ? 'debug' : 'info');

//=============================================================================
// FUNCIONES AUXILIARES GLOBALES
//=============================================================================

/**
 * Sanitiza una cadena para prevenir XSS (alias rapido para las vistas)
 */
function sanitize($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Obtiene la URL completa de un recurso
 */
function url($path = '') {
    return APP_URL . $path;
}