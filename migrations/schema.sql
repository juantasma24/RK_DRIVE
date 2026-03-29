-- =============================================================================
-- RK Marketing Drive - Esquema de Base de Datos
-- Version: 1.0.0
-- Base de datos: rk_marketing_drive
-- Charset: utf8mb4
-- =============================================================================

-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS rk_marketing_drive
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE rk_marketing_drive;

-- =============================================================================
-- TABLA: usuarios
-- Almacena los usuarios del sistema (clientes y administradores)
-- =============================================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    almacenamiento_usado BIGINT UNSIGNED DEFAULT 0 COMMENT 'Bytes utilizados',
    almacenamiento_maximo BIGINT UNSIGNED DEFAULT 2147483648 COMMENT 'Bytes maximos (2GB por defecto)',
    activo TINYINT(1) DEFAULT 1,
    token_recuperacion VARCHAR(64) NULL COMMENT 'Token para recuperar contrasena',
    token_expiracion DATETIME NULL COMMENT 'Expiracion del token de recuperacion',
    intentos_login INT UNSIGNED DEFAULT 0 COMMENT 'Intentos fallidos de login',
    bloqueado_hasta DATETIME NULL COMMENT 'Fecha hasta la que esta bloqueado',
    ultimo_acceso DATETIME NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: carpetas
-- Almacena las carpetas creadas por los clientes
-- =============================================================================
CREATE TABLE IF NOT EXISTS carpetas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    activa TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_usuario_id (usuario_id),
    INDEX idx_activa (activa),

    CONSTRAINT fk_carpetas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: archivos
-- Almacena los archivos subidos por los clientes
-- =============================================================================
CREATE TABLE IF NOT EXISTS archivos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    carpeta_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    nombre_original VARCHAR(255) NOT NULL COMMENT 'Nombre original del archivo',
    nombre_fisico VARCHAR(255) NOT NULL COMMENT 'Nombre unico en el servidor',
    tipo_mime VARCHAR(100) NOT NULL,
    extension VARCHAR(10) NOT NULL,
    tamano_bytes BIGINT UNSIGNED NOT NULL,
    ruta_fisica VARCHAR(500) NOT NULL COMMENT 'Ruta completa del archivo',
    descripcion TEXT NULL,
    en_papelera TINYINT(1) DEFAULT 0 COMMENT 'Si esta en papelera de reciclaje',
    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_eliminacion DATETIME NULL COMMENT 'Fecha en que se movio a papelera',
    fecha_expiracion DATETIME NULL COMMENT 'Fecha automatica de eliminacion',
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_carpeta_id (carpeta_id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_en_papelera (en_papelera),
    INDEX idx_fecha_expiracion (fecha_expiracion),
    INDEX idx_extension (extension),

    CONSTRAINT fk_archivos_carpeta
        FOREIGN KEY (carpeta_id) REFERENCES carpetas(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_archivos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: notificaciones
-- Almacena las notificaciones del sistema
-- =============================================================================
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tipo ENUM('subida', 'eliminacion', 'error', 'limpieza', 'sistema', 'alerta') NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensaje TEXT NOT NULL,
    leida TINYINT(1) DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_lectura DATETIME NULL,

    INDEX idx_usuario_id (usuario_id),
    INDEX idx_leida (leida),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha_creacion (fecha_creacion),

    CONSTRAINT fk_notificaciones_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: logs_actividad
-- Registra todas las acciones importantes del sistema
-- =============================================================================
CREATE TABLE IF NOT EXISTS logs_actividad (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL COMMENT 'NULL si es accion del sistema',
    accion VARCHAR(50) NOT NULL,
    descripcion TEXT NULL,
    entidad_tipo VARCHAR(50) NULL COMMENT 'Tipo de entidad afectada (usuario, carpeta, archivo)',
    entidad_id INT UNSIGNED NULL COMMENT 'ID de la entidad afectada',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    datos_adicionales JSON NULL COMMENT 'Datos extra en formato JSON',
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_usuario_id (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_entidad (entidad_tipo, entidad_id),
    INDEX idx_fecha (fecha),

    CONSTRAINT fk_logs_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: configuracion_limpieza
-- Configuracion del sistema de limpieza automatica
-- =============================================================================
CREATE TABLE IF NOT EXISTS configuracion_limpieza (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dias_conservacion INT UNSIGNED DEFAULT 30 COMMENT 'Dias antes de eliminar archivos de papelera',
    dias_inactividad INT UNSIGNED DEFAULT 90 COMMENT 'Dias de inactividad antes de alerta',
    activa TINYINT(1) DEFAULT 1,
    ultima_ejecucion DATETIME NULL,
    proxima_ejecucion DATETIME NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: administradores_email
-- Lista de emails de administradores para notificaciones
-- =============================================================================
CREATE TABLE IF NOT EXISTS administradores_email (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    recibe_alertas TINYINT(1) DEFAULT 1 COMMENT 'Recibe alertas del sistema',
    recibe_resumenes TINYINT(1) DEFAULT 1 COMMENT 'Recibe resumenes periodicos',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: intentos_login
-- Registra intentos de login fallidos por IP
-- =============================================================================
CREATE TABLE IF NOT EXISTS intentos_login (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email_intentado VARCHAR(255) NULL,
    exitoso TINYINT(1) DEFAULT 0,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_ip_fecha (ip_address, fecha),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: configuracion_sistema
-- Configuraciones generales del sistema
-- =============================================================================
CREATE TABLE IF NOT EXISTS configuracion_sistema (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    tipo ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    descripcion VARCHAR(255) NULL,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DATOS INICIALES
-- =============================================================================

-- Insertar configuracion de limpieza por defecto
INSERT INTO configuracion_limpieza (dias_conservacion, dias_inactividad, activa) VALUES
(30, 90, 1);

-- Insertar administrador por defecto
-- Contrasena: Admin123! (hasheada con bcrypt)
INSERT INTO usuarios (nombre, email, password_hash, rol, almacenamiento_maximo, activo) VALUES
('Administrador Sistema', 'admin@rksolutions.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 10737418240, 1);
-- La contrasena hasheada es 'password' para demo, CAMBIAR en produccion

-- Insertar configuraciones del sistema por defecto
INSERT INTO configuracion_sistema (clave, valor, tipo, descripcion) VALUES
('max_file_size', '524288000', 'integer', 'Tamano maximo de archivo en bytes (500MB)'),
('max_storage_client', '2147483648', 'integer', 'Almacenamiento maximo por cliente en bytes (2GB)'),
('max_folders_client', '20', 'integer', 'Maximo de carpetas por cliente'),
('allowed_extensions', 'jpg,jpeg,png,gif,webp,svg,bmp,ico,pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,mp4,avi,mov,wmv,flv,webm,mkv,mp3,wav,ogg,flac,aac,m4a,zip,rar,7z,tar,gz', 'string', 'Extensiones de archivo permitidas'),
('login_attempts', '5', 'integer', 'Intentos de login fallidos antes de bloquear'),
('lockout_time', '900', 'integer', 'Tiempo de bloqueo en segundos (15 minutos)'),
('session_lifetime', '7200', 'integer', 'Tiempo de vida de sesion en segundos (2 horas)');

-- =============================================================================
-- VISTAS UTILES
-- =============================================================================

-- Vista para ver archivos con informacion de usuario y carpeta
CREATE OR REPLACE VIEW v_archivos_completos AS
SELECT
    a.id,
    a.nombre_original,
    a.nombre_fisico,
    a.tipo_mime,
    a.extension,
    a.tamano_bytes,
    a.descripcion,
    a.en_papelera,
    a.fecha_subida,
    a.fecha_eliminacion,
    a.fecha_expiracion,
    c.nombre AS carpeta_nombre,
    u.nombre AS usuario_nombre,
    u.email AS usuario_email,
    u.rol AS usuario_rol
FROM archivos a
INNER JOIN carpetas c ON a.carpeta_id = c.id
INNER JOIN usuarios u ON a.usuario_id = u.id;

-- Vista para estadisticas de almacenamiento por usuario
CREATE OR REPLACE VIEW v_estadisticas_almacenamiento AS
SELECT
    u.id AS usuario_id,
    u.nombre,
    u.email,
    u.rol,
    u.almacenamiento_maximo,
    u.almacenamiento_usado,
    COUNT(DISTINCT c.id) AS total_carpetas,
    COUNT(a.id) AS total_archivos,
    ROUND((u.almacenamiento_usado / u.almacenamiento_maximo) * 100, 2) AS porcentaje_usado
FROM usuarios u
LEFT JOIN carpetas c ON u.id = c.usuario_id AND c.activa = 1
LEFT JOIN archivos a ON u.id = a.usuario_id AND a.en_papelera = 0
GROUP BY u.id, u.nombre, u.email, u.rol, u.almacenamiento_maximo, u.almacenamiento_usado;

-- Vista para notificaciones no leidas
CREATE OR REPLACE VIEW v_notificaciones_pendientes AS
SELECT
    n.id,
    n.tipo,
    n.titulo,
    n.mensaje,
    n.fecha_creacion,
    u.nombre AS usuario_nombre,
    u.email AS usuario_email
FROM notificaciones n
INNER JOIN usuarios u ON n.usuario_id = u.id
WHERE n.leida = 0
ORDER BY n.fecha_creacion DESC;

-- =============================================================================
-- TRIGGERS
-- =============================================================================

-- Trigger para actualizar almacenamiento_usado despues de insertar archivo
DELIMITER //
CREATE TRIGGER tr_archivo_insert_actualizar_almacenamiento
AFTER INSERT ON archivos
FOR EACH ROW
BEGIN
    IF NEW.en_papelera = 0 THEN
        UPDATE usuarios
        SET almacenamiento_usado = almacenamiento_usado + NEW.tamano_bytes
        WHERE id = NEW.usuario_id;
    END IF;
END//
DELIMITER ;

-- Trigger para actualizar almacenamiento_usado despues de eliminar archivo
DELIMITER //
CREATE TRIGGER tr_archivo_delete_actualizar_almacenamiento
BEFORE DELETE ON archivos
FOR EACH ROW
BEGIN
    IF OLD.en_papelera = 0 THEN
        UPDATE usuarios
        SET almacenamiento_usado = almacenamiento_usado - OLD.tamano_bytes
        WHERE id = OLD.usuario_id;
    END IF;
END//
DELIMITER ;

-- Trigger para actualizar almacenamiento cuando se mueve a papelera
DELIMITER //
CREATE TRIGGER tr_archivo_papelera_actualizar_almacenamiento
BEFORE UPDATE ON archivos
FOR EACH ROW
BEGIN
    IF OLD.en_papelera = 0 AND NEW.en_papelera = 1 THEN
        UPDATE usuarios
        SET almacenamiento_usado = almacenamiento_usado - NEW.tamano_bytes
        WHERE id = NEW.usuario_id;
    ELSEIF OLD.en_papelera = 1 AND NEW.en_papelera = 0 THEN
        UPDATE usuarios
        SET almacenamiento_usado = almacenamiento_usado + NEW.tamano_bytes
        WHERE id = NEW.usuario_id;
    END IF;
END//
DELIMITER ;

-- =============================================================================
-- FIN DEL ESQUEMA
-- =============================================================================