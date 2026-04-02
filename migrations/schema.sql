-- =============================================================================
-- RK Marketing Drive - Esquema de Base de Datos
-- Version: 2.0.0
-- Base de datos: rk_marketing_drive
-- Charset: utf8mb4
--
-- INSTRUCCIONES:
--   1. Ejecutar este archivo primero (crea todas las tablas, vistas y triggers)
--   2. Ejecutar seed.sql despues (datos de prueba para desarrollo)
--
-- COMPATIBILIDAD: MySQL 5.7+ / MariaDB 10.3+
-- =============================================================================

CREATE DATABASE IF NOT EXISTS rk_marketing_drive
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE rk_marketing_drive;

-- =============================================================================
-- TABLA: usuarios
-- =============================================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id                      INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nombre                  VARCHAR(100)    NOT NULL,
    email                   VARCHAR(255)    NOT NULL UNIQUE,
    password_hash           VARCHAR(255)    NOT NULL,
    rol                     ENUM('cliente','admin','trabajador') NOT NULL DEFAULT 'cliente',
    almacenamiento_usado    BIGINT UNSIGNED DEFAULT 0    COMMENT 'Bytes utilizados (actualizado por triggers)',
    almacenamiento_maximo   BIGINT UNSIGNED DEFAULT 2147483648 COMMENT 'Bytes maximos (2 GB por defecto)',
    activo                  TINYINT(1)      DEFAULT 1,
    preferencia_tema        VARCHAR(10)     NOT NULL DEFAULT 'dark' COMMENT 'Tema visual preferido: dark o light',
    puede_editar_archivos   TINYINT(1)      NOT NULL DEFAULT 0 COMMENT 'Solo trabajadores: editar metadatos de archivos de clientes',
    puede_eliminar_archivos TINYINT(1)      NOT NULL DEFAULT 0 COMMENT 'Solo trabajadores: eliminar archivos de clientes',
    token_recuperacion      VARCHAR(64)     NULL COMMENT 'Token para recuperar contrasena',
    token_expiracion        DATETIME        NULL COMMENT 'Expiracion del token',
    intentos_login          INT UNSIGNED    DEFAULT 0 COMMENT 'Intentos fallidos consecutivos',
    bloqueado_hasta         DATETIME        NULL COMMENT 'Bloqueado por exceso de intentos',
    ultimo_acceso           DATETIME        NULL,
    fecha_creacion          DATETIME        DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion     DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email   (email),
    INDEX idx_rol     (rol),
    INDEX idx_activo  (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: carpetas
-- =============================================================================
CREATE TABLE IF NOT EXISTS carpetas (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id          INT UNSIGNED NOT NULL,
    nombre              VARCHAR(255) NOT NULL,
    descripcion         TEXT NULL,
    activa              TINYINT(1)   DEFAULT 1,
    fecha_creacion      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_usuario_id          (usuario_id),
    INDEX idx_activa              (activa),
    INDEX idx_carpetas_usuario_activa (usuario_id, activa),

    CONSTRAINT fk_carpetas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: archivos
-- carpeta_id es NULL para archivos sueltos (sin carpeta)
-- =============================================================================
CREATE TABLE IF NOT EXISTS archivos (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    carpeta_id          INT UNSIGNED    NULL DEFAULT NULL COMMENT 'NULL = archivo suelto sin carpeta',
    usuario_id          INT UNSIGNED    NOT NULL,
    nombre_original     VARCHAR(255)    NOT NULL COMMENT 'Nombre original del archivo',
    nombre_fisico       VARCHAR(255)    NOT NULL COMMENT 'Nombre unico en el servidor',
    tipo_mime           VARCHAR(100)    NOT NULL,
    extension           VARCHAR(10)     NOT NULL,
    tamano_bytes        BIGINT UNSIGNED NOT NULL,
    ruta_fisica         VARCHAR(500)    NOT NULL COMMENT 'Ruta absoluta en el servidor',
    descripcion         TEXT NULL,
    en_papelera         TINYINT(1)      DEFAULT 0,
    fecha_subida        DATETIME        DEFAULT CURRENT_TIMESTAMP,
    fecha_eliminacion   DATETIME        NULL COMMENT 'Fecha en que se movio a papelera',
    fecha_expiracion    DATETIME        NULL COMMENT 'Eliminacion automatica programada',
    fecha_actualizacion DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_carpeta_id               (carpeta_id),
    INDEX idx_usuario_id               (usuario_id),
    INDEX idx_en_papelera              (en_papelera),
    INDEX idx_extension                (extension),
    INDEX idx_fecha_expiracion         (fecha_expiracion),
    INDEX idx_archivos_usuario_papelera (usuario_id, en_papelera),

    CONSTRAINT fk_archivos_carpeta
        FOREIGN KEY (carpeta_id) REFERENCES carpetas(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_archivos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: notificaciones
-- =============================================================================
CREATE TABLE IF NOT EXISTS notificaciones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED NOT NULL,
    tipo            ENUM('subida','eliminacion','error','limpieza','sistema','alerta') NOT NULL,
    titulo          VARCHAR(255) NOT NULL,
    mensaje         TEXT NOT NULL,
    leida           TINYINT(1)   DEFAULT 0,
    fecha_creacion  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    fecha_lectura   DATETIME     NULL,

    INDEX idx_usuario_id          (usuario_id),
    INDEX idx_leida               (leida),
    INDEX idx_tipo                (tipo),
    INDEX idx_fecha_creacion      (fecha_creacion),
    INDEX idx_notif_usuario_leida (usuario_id, leida),

    CONSTRAINT fk_notificaciones_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: logs_actividad
-- =============================================================================
CREATE TABLE IF NOT EXISTS logs_actividad (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id       INT UNSIGNED NULL COMMENT 'NULL si es accion del sistema',
    accion           VARCHAR(50)  NOT NULL,
    descripcion      TEXT NULL,
    entidad_tipo     VARCHAR(50)  NULL COMMENT 'usuario | carpeta | archivo',
    entidad_id       INT UNSIGNED NULL,
    ip_address       VARCHAR(45)  NULL,
    user_agent       TEXT NULL,
    datos_adicionales JSON NULL,
    fecha            DATETIME     DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_usuario_id       (usuario_id),
    INDEX idx_accion           (accion),
    INDEX idx_entidad          (entidad_tipo, entidad_id),
    INDEX idx_fecha            (fecha),
    INDEX idx_logs_usuario_fecha (usuario_id, fecha),

    CONSTRAINT fk_logs_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: intentos_login
-- =============================================================================
CREATE TABLE IF NOT EXISTS intentos_login (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address      VARCHAR(45)  NOT NULL,
    email_intentado VARCHAR(255) NULL,
    exitoso         TINYINT(1)   DEFAULT 0,
    fecha           DATETIME     DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_ip_fecha (ip_address, fecha),
    INDEX idx_fecha    (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: configuracion_limpieza
-- =============================================================================
CREATE TABLE IF NOT EXISTS configuracion_limpieza (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dias_conservacion   INT UNSIGNED DEFAULT 30 COMMENT 'Dias antes de eliminar archivos de papelera',
    dias_inactividad    INT UNSIGNED DEFAULT 90 COMMENT 'Dias de inactividad antes de alerta',
    activa              TINYINT(1)   DEFAULT 1,
    ultima_ejecucion    DATETIME NULL,
    proxima_ejecucion   DATETIME NULL,
    fecha_creacion      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: administradores_email
-- =============================================================================
CREATE TABLE IF NOT EXISTS administradores_email (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre              VARCHAR(100) NOT NULL,
    email               VARCHAR(255) NOT NULL UNIQUE,
    recibe_alertas      TINYINT(1)   DEFAULT 1,
    recibe_resumenes    TINYINT(1)   DEFAULT 1,
    activo              TINYINT(1)   DEFAULT 1,
    fecha_creacion      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email  (email),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: configuracion_sistema
-- =============================================================================
CREATE TABLE IF NOT EXISTS configuracion_sistema (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave               VARCHAR(100) NOT NULL UNIQUE,
    valor               TEXT NOT NULL,
    tipo                ENUM('string','integer','boolean','json') DEFAULT 'string',
    descripcion         VARCHAR(255) NULL,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DATOS INICIALES DEL SISTEMA
-- =============================================================================

INSERT INTO configuracion_limpieza (dias_conservacion, dias_inactividad, activa) VALUES (30, 90, 1);

-- Administrador por defecto (contrasena: password — CAMBIAR en produccion)
INSERT INTO usuarios (nombre, email, password_hash, rol, almacenamiento_maximo, activo) VALUES
('Administrador Sistema', 'admin@rksolutions.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin', 10737418240, 1);

INSERT INTO configuracion_sistema (clave, valor, tipo, descripcion) VALUES
('max_file_size',      '524288000',   'integer', 'Tamano maximo de archivo en bytes (500 MB)'),
('max_storage_client', '2147483648',  'integer', 'Almacenamiento maximo por cliente en bytes (2 GB)'),
('max_folders_client', '20',          'integer', 'Maximo de carpetas por cliente'),
('allowed_extensions', 'jpg,jpeg,png,gif,webp,svg,bmp,ico,pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,mp4,avi,mov,wmv,flv,webm,mkv,mp3,wav,ogg,flac,aac,m4a,zip,rar,7z,tar,gz,txt,ai,eps,psd',
 'string',  'Extensiones de archivo permitidas (incluye txt y formatos Adobe)'),
('login_attempts',     '5',           'integer', 'Intentos fallidos antes de bloquear la IP'),
('lockout_time',       '900',         'integer', 'Tiempo de bloqueo en segundos (15 minutos)'),
('session_lifetime',   '7200',        'integer', 'Tiempo de vida de sesion en segundos (2 horas)');

-- =============================================================================
-- VISTAS
-- =============================================================================

-- Archivos con info de usuario y carpeta (incluye archivos sueltos via LEFT JOIN)
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
    c.nombre  AS carpeta_nombre,
    u.nombre  AS usuario_nombre,
    u.email   AS usuario_email,
    u.rol     AS usuario_rol
FROM archivos a
LEFT JOIN  carpetas c ON a.carpeta_id = c.id
INNER JOIN usuarios u ON a.usuario_id = u.id;

-- Estadisticas de almacenamiento por usuario
CREATE OR REPLACE VIEW v_estadisticas_almacenamiento AS
SELECT
    u.id    AS usuario_id,
    u.nombre,
    u.email,
    u.rol,
    u.almacenamiento_maximo,
    u.almacenamiento_usado,
    COUNT(DISTINCT c.id) AS total_carpetas,
    COUNT(a.id)          AS total_archivos,
    ROUND((u.almacenamiento_usado / u.almacenamiento_maximo) * 100, 2) AS porcentaje_usado
FROM usuarios u
LEFT JOIN carpetas c ON u.id = c.usuario_id AND c.activa = 1
LEFT JOIN archivos a ON u.id = a.usuario_id AND a.en_papelera = 0
GROUP BY u.id, u.nombre, u.email, u.rol, u.almacenamiento_maximo, u.almacenamiento_usado;

-- Notificaciones no leidas
CREATE OR REPLACE VIEW v_notificaciones_pendientes AS
SELECT
    n.id,
    n.tipo,
    n.titulo,
    n.mensaje,
    n.fecha_creacion,
    u.nombre AS usuario_nombre,
    u.email  AS usuario_email
FROM notificaciones n
INNER JOIN usuarios u ON n.usuario_id = u.id
WHERE n.leida = 0
ORDER BY n.fecha_creacion DESC;

-- =============================================================================
-- TRIGGERS DE ALMACENAMIENTO
-- Mantienen almacenamiento_usado sincronizado automaticamente.
-- Se eliminan antes de crear para que este script sea seguro tanto en
-- instalaciones nuevas como en actualizaciones de BD existentes.
-- Usan GREATEST(0, ...) para evitar underflow en BIGINT UNSIGNED.
-- =============================================================================

DROP TRIGGER IF EXISTS tr_archivo_insert_actualizar_almacenamiento;
DROP TRIGGER IF EXISTS tr_archivo_delete_actualizar_almacenamiento;
DROP TRIGGER IF EXISTS tr_archivo_papelera_actualizar_almacenamiento;

DELIMITER //

-- Al subir un archivo nuevo
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

-- Al eliminar un archivo permanentemente
CREATE TRIGGER tr_archivo_delete_actualizar_almacenamiento
BEFORE DELETE ON archivos
FOR EACH ROW
BEGIN
    IF OLD.en_papelera = 0 THEN
        UPDATE usuarios
        SET almacenamiento_usado = GREATEST(0, almacenamiento_usado - OLD.tamano_bytes)
        WHERE id = OLD.usuario_id;
    END IF;
END//

-- Al mover a papelera o restaurar desde papelera
CREATE TRIGGER tr_archivo_papelera_actualizar_almacenamiento
BEFORE UPDATE ON archivos
FOR EACH ROW
BEGIN
    IF OLD.en_papelera = 0 AND NEW.en_papelera = 1 THEN
        UPDATE usuarios
        SET almacenamiento_usado = GREATEST(0, almacenamiento_usado - NEW.tamano_bytes)
        WHERE id = NEW.usuario_id;
    ELSEIF OLD.en_papelera = 1 AND NEW.en_papelera = 0 THEN
        UPDATE usuarios
        SET almacenamiento_usado = almacenamiento_usado + NEW.tamano_bytes
        WHERE id = NEW.usuario_id;
    END IF;
END//

DELIMITER ;

-- =============================================================================
-- SINCRONIZAR almacenamiento_usado
-- Recalcula el contador real para todos los usuarios existentes.
-- Necesario al aplicar este script sobre una BD ya en uso.
-- =============================================================================

SET SQL_SAFE_UPDATES = 0;
UPDATE usuarios u
SET almacenamiento_usado = (
    SELECT COALESCE(SUM(tamano_bytes), 0)
    FROM archivos
    WHERE usuario_id = u.id AND en_papelera = 0
);
SET SQL_SAFE_UPDATES = 1;

-- =============================================================================
-- COLUMNAS AÑADIDAS EN v2.1.0 (safe para BDs existentes)
-- =============================================================================

-- preferencia_tema: tema visual por usuario (dark/light)
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'usuarios'
      AND COLUMN_NAME  = 'preferencia_tema'
);
SET @alter_sql = IF(@col_exists = 0,
    "ALTER TABLE usuarios ADD COLUMN preferencia_tema VARCHAR(10) NOT NULL DEFAULT 'dark' COMMENT 'Tema visual preferido: dark o light' AFTER activo",
    'SELECT 1'
);
PREPARE _stmt FROM @alter_sql;
EXECUTE _stmt;
DEALLOCATE PREPARE _stmt;

-- =============================================================================
-- FIN DEL ESQUEMA — v2.1.0
-- =============================================================================
