<?php
/**
 * RK Marketing Drive - Funciones Auxiliares
 *
 * Este archivo contiene funciones de utilidad general para la aplicacion.
 *
 * @package RKMarketingDrive
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

//=============================================================================
// FUNCIONES DE FORMATO
//=============================================================================

/**
 * Formatea un tamano de archivo en formato legible
 *
 * @param int $bytes Tamano en bytes
 * @param int $precision Decimales a mostrar
 * @return string Tamano formateado
 */
function formatFileSize($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Formatea una fecha en formato legible
 *
 * @param string $datetime Fecha y hora
 * @param string $format Formato de salida
 * @return string Fecha formateada
 */
function formatDate($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }

    $ts = strtotime($datetime);
    return $ts !== false ? date($format, $ts) : '-';
}

/**
 * Formatea una fecha en formato relativo (hace X minutos, etc.)
 *
 * @param string $datetime Fecha y hora
 * @return string Fecha relativa
 */
function formatRelativeDate($datetime) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }

    $date = new DateTime($datetime);
    $now = new DateTime();
    $diff = $now->diff($date);

    if ($diff->y > 0) {
        return 'hace ' . $diff->y . ' ano' . ($diff->y > 1 ? 's' : '');
    }

    if ($diff->m > 0) {
        return 'hace ' . $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
    }

    if ($diff->d > 0) {
        return 'hace ' . $diff->d . ' dia' . ($diff->d > 1 ? 's' : '');
    }

    if ($diff->h > 0) {
        return 'hace ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
    }

    if ($diff->i > 0) {
        return 'hace ' . $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
    }

    return 'hace unos segundos';
}

/**
 * Trunca un texto a una longitud determinada
 *
 * @param string $text Texto a truncar
 * @param int $length Longitud maxima
 * @param string $append Texto a agregar al final
 * @return string Texto truncado
 */
function truncateText($text, $length = 100, $append = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }

    $truncated  = substr($text, 0, $length);
    $lastSpace  = strrpos($truncated, ' ');
    if ($lastSpace !== false) {
        $truncated = substr($truncated, 0, $lastSpace);
    }

    return $truncated . $append;
}

/**
 * Obtiene el icono de un tipo de archivo
 *
 * @param string $extension Extension del archivo
 * @return string Clase CSS del icono
 */
function getFileIcon($extension) {
    $icons = [
        // Imagenes
        'jpg'  => 'bi-file-image',
        'jpeg' => 'bi-file-image',
        'png'  => 'bi-file-image',
        'gif'  => 'bi-file-image',
        'webp' => 'bi-file-image',
        'svg'  => 'bi-file-image',
        'bmp'  => 'bi-file-image',
        'ico'  => 'bi-file-image',
        // Documentos
        'pdf'  => 'bi-file-pdf',
        'doc'  => 'bi-file-word',
        'docx' => 'bi-file-word',
        'xls'  => 'bi-file-spreadsheet',
        'xlsx' => 'bi-file-spreadsheet',
        'ppt'  => 'bi-file-slides',
        'pptx' => 'bi-file-slides',
        'odt'  => 'bi-file-text',
        'ods'  => 'bi-file-text',
        'odp'  => 'bi-file-text',
        // Video
        'mp4'  => 'bi-file-play',
        'avi'  => 'bi-file-play',
        'mov'  => 'bi-file-play',
        'wmv'  => 'bi-file-play',
        'flv'  => 'bi-file-play',
        'webm' => 'bi-file-play',
        'mkv'  => 'bi-file-play',
        // Audio
        'mp3'  => 'bi-file-music',
        'wav'  => 'bi-file-music',
        'ogg'  => 'bi-file-music',
        'flac' => 'bi-file-music',
        'aac'  => 'bi-file-music',
        'm4a'  => 'bi-file-music',
        // Archivos comprimidos
        'zip'  => 'bi-file-zip',
        'rar'  => 'bi-file-zip',
        '7z'   => 'bi-file-zip',
        'tar'  => 'bi-file-zip',
        'gz'   => 'bi-file-zip',
        // Adobe
        'ai'  => 'bi-file-earmark-image',
        'eps' => 'bi-file-earmark-image',
        'psd' => 'bi-file-earmark-image',
        // Otros
        'txt'     => 'bi-file-text',
        'default' => 'bi-file-earmark'
    ];

    return $icons[strtolower($extension)] ?? $icons['default'];
}

/**
 * Obtiene el color de badge para un tipo de archivo
 *
 * @param string $extension Extension del archivo
 * @return string Clase CSS del color
 */
function getFileColor($extension) {
    $colors = [
        // Imagenes - Verde
        'jpg' => 'success',
        'jpeg' => 'success',
        'png' => 'success',
        'gif' => 'success',
        'webp' => 'success',
        'svg' => 'success',
        // Documentos PDF - Rojo
        'pdf' => 'danger',
        // Word - Azul
        'doc' => 'primary',
        'docx' => 'primary',
        // Excel - Verde
        'xls' => 'success',
        'xlsx' => 'success',
        // PowerPoint - Naranja
        'ppt' => 'warning',
        'pptx' => 'warning',
        // Video - Purpura
        'mp4' => 'info',
        'avi' => 'info',
        'mov' => 'info',
        // Audio - Gris
        'mp3' => 'secondary',
        'wav' => 'secondary',
        // Archivos comprimidos - Amarillo
        'zip' => 'warning',
        'rar' => 'warning',
        '7z' => 'warning',
        // Adobe - Naranja
        'ai'  => 'warning',
        'eps' => 'warning',
        'psd' => 'warning',
        // Por defecto
        'default' => 'secondary'
    ];

    return $colors[strtolower($extension)] ?? $colors['default'];
}

//=============================================================================
// FUNCIONES DE VALIDACION
//=============================================================================

/**
 * Valida un email
 *
 * @param string $email Email a validar
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida una contrasena
 *
 * @param string $password Contrasena a validar
 * @return array Resultado con 'valid' y 'errors'
 */
function validatePassword($password) {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'La contrasena debe tener al menos 8 caracteres.';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'La contrasena debe contener al menos una mayuscula.';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'La contrasena debe contener al menos una minuscula.';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'La contrasena debe contener al menos un numero.';
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'La contrasena debe contener al menos un caracter especial.';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Valida un nombre de archivo
 *
 * @param string $filename Nombre del archivo
 * @param int $maxLength Longitud maxima
 * @return bool
 */
function isValidFilename($filename, $maxLength = 255) {
    if (empty($filename) || strlen($filename) > $maxLength) {
        return false;
    }

    // Caracteres no permitidos
    $invalidChars = ['/', '\\', ':', '*', '?', '"', '<', '>', '|', "\0"];

    foreach ($invalidChars as $char) {
        if (strpos($filename, $char) !== false) {
            return false;
        }
    }

    return true;
}

//=============================================================================
// FUNCIONES DE ARRAY
//=============================================================================

/**
 * Obtiene un valor de un array de forma segura
 *
 * @param array $array Array fuente
 * @param string $key Clave a buscar
 * @param mixed $default Valor por defecto
 * @return mixed
 */
function arrayGet($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * Obtiene multiples valores de un array
 *
 * @param array $array Array fuente
 * @param array $keys Claves a buscar
 * @param mixed $default Valor por defecto
 * @return array
 */
function arrayOnly($array, $keys, $default = null) {
    $result = [];

    foreach ($keys as $key) {
        $result[$key] = isset($array[$key]) ? $array[$key] : $default;
    }

    return $result;
}

/**
 * Filtra un array eliminando valores vacios
 *
 * @param array $array Array a filtrar
 * @return array
 */
function arrayFilterEmpty($array) {
    return array_filter($array, function ($value) {
        return !empty($value) || $value === '0';
    });
}

//=============================================================================
// FUNCIONES DE SESION
//=============================================================================

/**
 * Inicia la sesion de forma segura
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurar sesion segura
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');

        session_name(SESSION_NAME);
        session_start();

        // Regenerar ID si la sesion es nueva
        if (!isset($_SESSION['created'])) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }

        // Verificar timeout de inactividad
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_IDLE_TIMEOUT) {
                session_unset();
                session_destroy();
                session_start();
            }
        }

        // Actualizar last_activity solo cada 5 minutos para evitar escritura en cada request
        if (!isset($_SESSION['last_activity']) || time() - $_SESSION['last_activity'] > 300) {
            $_SESSION['last_activity'] = time();
        }
    }
}

/**
 * Destruye la sesion de forma segura
 */
function destroySession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];

        // Borrar cookie de sesion
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}

/**
 * Verifica si el usuario esta autenticado
 *
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifica si el usuario es administrador
 *
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isWorker() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'trabajador';
}

/**
 * Comprueba si el trabajador autenticado puede editar archivos.
 * Siempre consulta la BD para reflejar cambios del admin en tiempo real.
 */
function canEditFiles(): bool {
    if (isAdmin()) return true;
    if (!isWorker()) return false;
    $user = em()->find(\App\Entity\Usuario::class, getCurrentUserId());
    return $user ? $user->isPuedeEditarArchivos() : false;
}

/**
 * Comprueba si el trabajador autenticado puede eliminar archivos.
 * Siempre consulta la BD para reflejar cambios del admin en tiempo real.
 */
function canDeleteFiles(): bool {
    if (isAdmin()) return true;
    if (!isWorker()) return false;
    $user = em()->find(\App\Entity\Usuario::class, getCurrentUserId());
    return $user ? $user->isPuedeEliminarArchivos() : false;
}

/**
 * Obtiene el ID del usuario actual
 *
 * @return int|null
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Obtiene el nombre del usuario actual
 *
 * @return string|null
 */
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? null;
}

/**
 * Obtiene el email del usuario actual
 *
 * @return string|null
 */
function getCurrentUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

//=============================================================================
// FUNCIONES DE REDIRECCION Y RESPUESTA
//=============================================================================

/**
 * Redirecciona a una URL
 *
 * @param string $url URL de destino
 * @param bool $prependBase Si se debe agregar la URL base
 */
function redirect($url, $prependBase = true) {
    if ($prependBase && !preg_match('/^https?:\/\//i', $url)) {
        $url = APP_URL . $url;
    }

    header('Location: ' . $url);
    exit;
}

/**
 * Redirecciona a la pagina anterior o a una URL por defecto
 *
 * @param string $default URL por defecto
 */
function redirectBack($default = '/') {
    $referer = $_SERVER['HTTP_REFERER'] ?? null;

    if ($referer && strpos($referer, APP_URL) === 0) {
        redirect($referer, false);
    }

    redirect($default);
}

/**
 * Envia una respuesta JSON
 *
 * @param array $data Datos a enviar
 * @param int $statusCode Codigo de estado HTTP
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Envia una respuesta JSON de error
 *
 * @param string $message Mensaje de error
 * @param int $statusCode Codigo de estado HTTP
 * @param array $errors Errores adicionales
 */
function jsonError($message, $statusCode = 400, $errors = []) {
    $response = [
        'success' => false,
        'message' => $message
    ];

    if (!empty($errors)) {
        $response['errors'] = $errors;
    }

    jsonResponse($response, $statusCode);
}

/**
 * Envia una respuesta JSON de exito
 *
 * @param string $message Mensaje de exito
 * @param array $data Datos adicionales
 */
function jsonSuccess($message, $data = []) {
    $response = [
        'success' => true,
        'message' => $message
    ];

    if (!empty($data)) {
        $response['data'] = $data;
    }

    jsonResponse($response);
}

//=============================================================================
// FUNCIONES DE MENSAJES FLASH
//=============================================================================

/**
 * Establece un mensaje flash
 *
 * @param string $type Tipo de mensaje (success, error, warning, info)
 * @param string $message Mensaje a mostrar
 */
function setFlash($type, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }

    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Obtiene y limpia los mensajes flash
 *
 * @return array Mensajes flash
 */
function getFlash() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

/**
 * Verifica si hay mensajes flash
 *
 * @return bool
 */
function hasFlash() {
    return !empty($_SESSION['flash']);
}

//=============================================================================
// FUNCIONES DE DEPURACION
//=============================================================================

/**
 * Guarda un mensaje de log
 *
 * @param string $level Nivel de log (debug, info, warning, error)
 * @param string $message Mensaje
 * @param array $context Contexto adicional
 */
function logMessage($level, $message, $context = []) {
    // Verificar directorio de logs
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }

    // Niveles permitidos
    $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
    $configLevels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];

    // Verificar nivel minimo
    $minLevel = $configLevels[LOG_LEVEL] ?? 1;
    if ($levels[$level] < $minLevel) {
        return;
    }

    // Formatear mensaje
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
    $logLine = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";

    // Escribir log
    $logFile = LOG_DIR . '/app_' . date('Y-m-d') . '.log';
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

/**
 * Volca una variable para depuracion
 *
 * @param mixed $var Variable a volcar
 * @param bool $exit Si debe detener la ejecucion
 */
function dd($var, $exit = true) {
    echo '<pre style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 5px; overflow: auto;">';
    var_dump($var);
    echo '</pre>';

    if ($exit) {
        exit;
    }
}

/**
 * Volca multiples variables para depuracion
 *
 * @param mixed ...$vars Variables a volcar
 */
function ddd(...$vars) {
    echo '<pre style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 5px; overflow: auto;">';

    foreach ($vars as $var) {
        var_dump($var);
        echo "\n---\n";
    }

    echo '</pre>';
    exit;
}

//=============================================================================
// FUNCIONES DE INTERNACIONALIZACION
//=============================================================================

/**
 * Devuelve el nombre del mes en español
 */
function getMonthSpanish(string $monthEnglish): string {
    $meses = [
        'January'=>'Enero','February'=>'Febrero','March'=>'Marzo',
        'April'=>'Abril','May'=>'Mayo','June'=>'Junio',
        'July'=>'Julio','August'=>'Agosto','September'=>'Septiembre',
        'October'=>'Octubre','November'=>'Noviembre','December'=>'Diciembre',
    ];
    return $meses[$monthEnglish] ?? $monthEnglish;
}

/**
 * Devuelve el nombre del día de la semana en español
 */
function getDaySpanish(string $dayEnglish): string {
    $dias = [
        'Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles',
        'Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado','Sunday'=>'Domingo',
    ];
    return $dias[$dayEnglish] ?? $dayEnglish;
}

/**
 * Devuelve el tipo de previsualización según extensión de archivo
 */
function getPreviewType(string $ext): string {
    $ext = strtolower($ext);
    if (in_array($ext, ['jpg','jpeg','png','gif','webp','bmp'])) return 'image';
    if (in_array($ext, ['mp4','webm']))                          return 'video';
    if (in_array($ext, ['mp3','wav','ogg','aac','m4a']))         return 'audio';
    if ($ext === 'pdf')                                          return 'pdf';
    if (in_array($ext, ['txt','csv']))                           return 'text';
    return 'none';
}