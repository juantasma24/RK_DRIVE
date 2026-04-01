<?php
/**
 * RK Marketing Drive - Funciones de Seguridad
 *
 * Este archivo contiene todas las funciones relacionadas con seguridad:
 * validacion de inputs, sanitizacion, prevencion de ataques, etc.
 *
 * @package RKMarketingDrive
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

//=============================================================================
// SANITIZACION DE INPUTS
//=============================================================================

/**
 * Sanitiza un string para prevenir XSS
 *
 * @param string $input String a sanitizar
 * @param bool $stripTags Si se deben eliminar tags HTML
 * @return string String sanitizado
 */
function sanitizeString($input, $stripTags = true) {
    if (!is_string($input)) {
        return '';
    }

    $input = trim($input);

    if ($stripTags) {
        $input = strip_tags($input);
    }

    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitiza un email
 *
 * @param string $email Email a sanitizar
 * @return string|null Email sanitizado o null si es invalido
 */
function sanitizeEmail($email) {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
}

/**
 * Sanitiza un entero
 *
 * @param mixed $input Valor a sanitizar
 * @param int $min Valor minimo permitido
 * @param int $max Valor maximo permitido
 * @return int|null Entero sanitizado o null si es invalido
 */
function sanitizeInt($input, $min = null, $max = null) {
    $options = [];

    if ($min !== null) {
        $options['min_range'] = $min;
    }

    if ($max !== null) {
        $options['max_range'] = $max;
    }

    $result = filter_var($input, FILTER_VALIDATE_INT, ['options' => $options]);
    return $result !== false ? (int)$result : null;
}

/**
 * Sanitiza un flotante
 *
 * @param mixed $input Valor a sanitizar
 * @param float $min Valor minimo permitido
 * @param float $max Valor maximo permitido
 * @return float|null Flotante sanitizado o null si es invalido
 */
function sanitizeFloat($input, $min = null, $max = null) {
    $result = filter_var($input, FILTER_VALIDATE_FLOAT);
    if ($result === false) {
        return null;
    }

    if ($min !== null && $result < $min) {
        return null;
    }

    if ($max !== null && $result > $max) {
        return null;
    }

    return (float)$result;
}

/**
 * Sanitiza una URL
 *
 * @param string $url URL a sanitizar
 * @return string|null URL sanitizada o null si es invalida
 */
function sanitizeUrl($url) {
    $url = filter_var(trim($url), FILTER_SANITIZE_URL);
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
}

/**
 * Sanitiza un nombre de archivo
 *
 * @param string $filename Nombre del archivo
 * @return string Nombre sanitizado
 */
function sanitizeFilename($filename) {
    // Eliminar caracteres no permitidos
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

    // Eliminar multiples puntos (prevencion de extension spoofing)
    $filename = preg_replace('/\.{2,}/', '.', $filename);

    // Eliminar espacios
    $filename = str_replace(' ', '_', $filename);

    // Limitar longitud
    if (strlen($filename) > 255) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $filename = substr($name, 0, 250 - strlen($ext)) . '.' . $ext;
    }

    return $filename;
}

//=============================================================================
// VALIDACION DE ARCHIVOS
//=============================================================================

/**
 * Valida un archivo subido
 *
 * @param array $file Archivo de $_FILES
 * @param int $maxSize Tamano maximo en bytes
 * @return array Resultado con 'valid' y 'error'
 */
function validateUploadedFile($file, $maxSize = null) {
    $errors = [];

    // Verificar errores de subida PHP
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamano maximo de upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamano maximo del formulario.',
        UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente.',
        UPLOAD_ERR_NO_FILE => 'No se subio ningun archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo.',
        UPLOAD_ERR_EXTENSION => 'Una extension PHP detuvo la subida.'
    ];

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        $errors[] = $uploadErrors[$errorCode] ?? 'Error desconocido al subir el archivo.';
        return ['valid' => false, 'errors' => $errors];
    }

    // Verificar que el archivo existe
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'Archivo no valido.';
        return ['valid' => false, 'errors' => $errors];
    }

    // Verificar tamano
    $maxSize = $maxSize ?? MAX_FILE_SIZE;
    if ($file['size'] > $maxSize) {
        $errors[] = 'El archivo excede el tamano maximo permitido (' . formatFileSize($maxSize) . ').';
    }

    // Verificar tipo MIME real (no solo el declarado)
    $realMime = getMimeType($file['tmp_name']);
    $allowedMimes = json_decode(ALLOWED_MIME_TYPES, true);

    // En Windows/XAMPP, finfo puede devolver application/octet-stream para archivos de texto,
    // o application/x-empty para archivos vacios. Usar extension como fallback en ambos casos.
    if ($realMime === 'application/octet-stream' || $realMime === 'application/x-empty') {
        $extForMime = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fallbackMime = getMimeTypeByExtension($extForMime);
        if ($fallbackMime !== 'application/octet-stream') {
            $realMime = $fallbackMime;
        }
    }

    if (!in_array($realMime, $allowedMimes)) {
        $errors[] = 'Tipo de archivo no permitido.';
    }

    // Verificar extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = json_decode(ALLOWED_EXTENSIONS, true);

    if (!in_array($ext, $allowedExtensions)) {
        $errors[] = 'Extension de archivo no permitida.';
    }

    // Verificar que el MIME declarado coincide con el real
    if (!empty($file['type']) && $file['type'] !== $realMime) {
        // Solo warning, no bloquear (algunos navegadores envian MIME incorrecto)
        logMessage('warning', 'MIME type mismatch', [
            'declared' => $file['type'],
            'real' => $realMime,
            'filename' => $file['name']
        ]);
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'mime' => $realMime,
        'extension' => $ext,
        'size' => $file['size']
    ];
}

/**
 * Obtiene el tipo MIME real de un archivo
 *
 * @param string $filepath Ruta al archivo
 * @return string Tipo MIME
 */
function getMimeType($filepath) {
    // Intentar con finfo (preferido)
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        return $mime ?: 'application/octet-stream';
    }

    // Alternativa con mime_content_type
    if (function_exists('mime_content_type')) {
        return mime_content_type($filepath) ?: 'application/octet-stream';
    }

    // Fallback basico por extension
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    return getMimeTypeByExtension($ext);
}

/**
 * Devuelve el tipo MIME por extension de archivo
 *
 * @param string $ext Extension sin punto
 * @return string Tipo MIME
 */
function getMimeTypeByExtension($ext) {
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'bmp' => 'image/bmp',
        'ico' => 'image/x-icon',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'mp4' => 'video/mp4',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime',
        'wmv' => 'video/x-ms-wmv',
        'flv' => 'video/x-flv',
        'webm' => 'video/webm',
        'mkv' => 'video/x-matroska',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'flac' => 'audio/flac',
        'aac' => 'audio/aac',
        'm4a' => 'audio/x-m4a',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed',
        'tar' => 'application/x-tar',
        'gz' => 'application/gzip',
        // Adobe
        'ai'  => 'application/postscript',
        'eps' => 'application/postscript',
        'psd' => 'image/vnd.adobe.photoshop',
    ];

    return $mimeTypes[$ext] ?? 'application/octet-stream';
}

/**
 * Verifica si un archivo es una imagen valida
 *
 * @param string $filepath Ruta al archivo
 * @return bool
 */
function isValidImage($filepath) {
    $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];

    if (function_exists('exif_imagetype')) {
        $type = @exif_imagetype($filepath);
        return $type !== false && in_array(image_type_to_mime_type($type), $imageMimes);
    }

    return in_array(getMimeType($filepath), $imageMimes);
}

//=============================================================================
// VALIDACION DE RUTAS
//=============================================================================

/**
 * Verifica si una ruta es segura (previene path traversal)
 *
 * @param string $path Ruta a verificar
 * @param string $baseDir Directorio base permitido
 * @return bool
 */
function isPathSafe($path, $baseDir = null) {
    $baseDir = $baseDir ?? UPLOAD_DIR;

    // Normalizar rutas
    $realBase = realpath($baseDir);
    $realPath = realpath($path);

    // Si el archivo no existe todavia, verificar el directorio padre
    if ($realPath === false) {
        $parentDir = dirname($path);
        $realParent = realpath($parentDir);
        if ($realParent === false) {
            return false;
        }
        return $realParent === $realBase || strpos($realParent, $realBase . DIRECTORY_SEPARATOR) === 0;
    }

    return $realPath !== false && (
        $realPath === $realBase ||
        strpos($realPath, $realBase . DIRECTORY_SEPARATOR) === 0
    );
}

/**
 * Genera una ruta segura para un archivo
 *
 * @param int $userId ID del usuario
 * @param string $filename Nombre del archivo
 * @return string Ruta segura
 */
function generateSecurePath($userId, $filename) {
    $year = date('Y');
    $month = date('m');

    // Generar nombre unico
    $uniqueName = bin2hex(random_bytes(8)) . '_' . sanitizeFilename($filename);

    // Estructura de directorios: uploads/userId/year/month/
    $dir = UPLOAD_DIR . '/' . $userId . '/' . $year . '/' . $month;

    // Crear directorio si no existe
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de destino.');
        }
    }

    return $dir . '/' . $uniqueName;
}

//=============================================================================
// PROTECCION CSRF
//=============================================================================

/**
 * Genera un token CSRF
 *
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    return $_SESSION['csrf_token'];
}

/**
 * Genera un campo hidden con el token CSRF
 *
 * @return string Campo hidden HTML
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Verifica un token CSRF
 *
 * @param string $token Token a verificar
 * @param int $maxAge Edad maxima del token en segundos
 * @return bool
 */
function verifyCSRFToken($token, $maxAge = 3600) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    // Verificar que el token coincide
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }

    // Verificar antiguedad del token
    if (isset($_SESSION['csrf_token_time'])) {
        if (time() - $_SESSION['csrf_token_time'] > $maxAge) {
            return false;
        }
    }

    return true;
}

/**
 * Verifica el token CSRF de una petición POST y termina si es invalido
 *
 * @param bool $exit Si debe terminar la ejecucion
 * @return bool
 */
function requireCSRFToken($exit = true) {
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($token)) {
        if ($exit) {
            http_response_code(403);
            die('Token CSRF invalido. Por favor, recarga la pagina e intenta de nuevo.');
        }
        return false;
    }

    return true;
}

//=============================================================================
// PROTECCION CONTRA FUERZA BRUTA
//=============================================================================

/**
 * Verifica si una IP esta bloqueada por intentos fallidos
 *
 * @param string $ip Direccion IP
 * @return bool|array False si no esta bloqueada, array con info si lo esta
 */
function isIPBlocked($ip) {
    $db = db();

    // Contar intentos fallidos en los ultimos 15 minutos
    $sql = "SELECT COUNT(*) as attempts
            FROM intentos_login
            WHERE ip_address = :ip
            AND exitoso = 0
            AND fecha > DATE_SUB(NOW(), INTERVAL :lockout MINUTE)";

    $result = $db->fetchOne($sql, [
        'ip' => $ip,
        'lockout' => floor(LOGIN_LOCKOUT_TIME / 60)
    ]);

    $attempts = (int)($result['attempts'] ?? 0);

    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        return [
            'blocked' => true,
            'attempts' => $attempts,
            'remaining_time' => LOGIN_LOCKOUT_TIME
        ];
    }

    return false;
}

/**
 * Registra un intento de login
 *
 * @param string $ip Direccion IP
 * @param string $email Email intentado
 * @param bool $successful Si el intento fue exitoso
 */
function logLoginAttempt($ip, $email, $successful = false) {
    $db = db();

    $sql = "INSERT INTO intentos_login (ip_address, email_intentado, exitoso) VALUES (:ip, :email, :success)";

    $db->insert($sql, [
        'ip' => $ip,
        'email' => $email,
        'success' => $successful ? 1 : 0
    ]);
}

/**
 * Limpia intentos de login antiguos
 */
function cleanOldLoginAttempts() {
    $db = db();

    // Eliminar intentos mayores a 24 horas
    $sql = "DELETE FROM intentos_login WHERE fecha < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $db->execute($sql);
}

//=============================================================================
// HEADERS DE SEGURIDAD
//=============================================================================

/**
 * Establece headers de seguridad HTTP
 */
function setSecurityHeaders() {
    // Prevenir clickjacking
    header('X-Frame-Options: SAMEORIGIN');

    // Prevenir MIME type sniffing
    header('X-Content-Type-Options: nosniff');

    // Habilitar XSS protection en navegadores antiguos
    header('X-XSS-Protection: 1; mode=block');

    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; frame-ancestors 'self'");

    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // HSTS (solo en HTTPS)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    // Permissions Policy
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}

/**
 * Establece headers para prevenir cache en paginas protegidas
 */
function setNoCacheHeaders() {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
}

//=============================================================================
// UTILIDADES DE SEGURIDAD
//=============================================================================

/**
 * Genera un hash seguro de contrasena
 *
 * @param string $password Contrasena en texto plano
 * @return string Hash de la contrasena
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifica una contrasena contra su hash
 *
 * @param string $password Contrasena en texto plano
 * @param string $hash Hash almacenado
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Genera un token seguro aleatorio
 *
 * @param int $length Longitud del token en bytes
 * @return string Token hexadecimal
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Obtiene la IP real del cliente
 *
 * @return string IP del cliente
 */
function getClientIP(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Obtiene el User Agent del cliente
 *
 * @return string User Agent
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}