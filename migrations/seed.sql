-- =============================================================================
-- RK Marketing Drive - Datos de Prueba (Seed)
-- Version: 2.0.0
--
-- INSTRUCCIONES:
--   Ejecutar DESPUES de schema.sql.
--   Solo para entornos de desarrollo. NO ejecutar en produccion.
--
-- USUARIOS CREADOS:
--   admin@rksolutions.com        contrasena: password  (rol: admin)
--   trabajador@rksolutions.com   contrasena: password  (rol: trabajador, sin permisos)
--   cliente1@empresaabc.com      contrasena: password  (rol: cliente)
--   cliente2@marketingpro.com    contrasena: password  (rol: cliente)
--   cliente3@agenciaxyz.com      contrasena: password  (rol: cliente)
-- =============================================================================

USE rk_marketing_drive;

-- =============================================================================
-- LIMPIAR DATOS EXISTENTES
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE logs_actividad;
TRUNCATE TABLE notificaciones;
TRUNCATE TABLE archivos;
TRUNCATE TABLE carpetas;
TRUNCATE TABLE intentos_login;
TRUNCATE TABLE administradores_email;
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- USUARIOS DE PRUEBA
-- Contrasena para todos: password
-- Hash generado con password_hash('password', PASSWORD_BCRYPT)
-- =============================================================================

-- Actualizar admin (ya insertado por schema.sql)
UPDATE usuarios
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@rksolutions.com';

-- Trabajador de prueba (sin permisos de edicion ni eliminacion por defecto)
INSERT INTO usuarios (nombre, email, password_hash, rol, puede_editar_archivos, puede_eliminar_archivos, activo)
VALUES ('Trabajador Demo', 'trabajador@rksolutions.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'trabajador', 0, 0, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Clientes de prueba (2 GB de almacenamiento cada uno)
INSERT INTO usuarios (nombre, email, password_hash, rol, almacenamiento_maximo, activo) VALUES
('Empresa ABC S.L.',      'cliente1@empresaabc.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 2147483648, 1),
('Marketing Digital Pro', 'cliente2@marketingpro.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 2147483648, 1),
('Agencia Creativa XYZ',  'cliente3@agenciaxyz.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 2147483648, 1),
('Cliente Inactivo',      'inactivo@ejemplo.com',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 2147483648, 0)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- =============================================================================
-- CARPETAS DE PRUEBA
-- IDs de usuario asignados tras el INSERT anterior:
--   admin=1, trabajador=2, cliente1=3, cliente2=4, cliente3=5
-- =============================================================================

INSERT INTO carpetas (usuario_id, nombre, descripcion) VALUES
-- Cliente 1
(3, 'Campana Verano 2024',   'Materiales para la campana de verano'),
(3, 'Logos Corporativos',    'Logos en diferentes formatos'),
(3, 'Fotos de Producto',     'Fotografias de productos'),
-- Cliente 2
(4, 'Proyectos Activos',     'Documentos de proyectos en curso'),
(4, 'Archivos Historicos',   'Archivos de proyectos completados'),
-- Cliente 3
(5, 'Disenos Web',           'Mockups y recursos web'),
(5, 'Videos Promocionales',  'Videos para redes sociales'),
(5, 'Presentaciones',        'Presentaciones de clientes'),
(5, 'Documentacion',         'Documentos varios');

-- =============================================================================
-- ARCHIVOS DE PRUEBA (registros en BD, sin archivos fisicos reales)
-- =============================================================================

INSERT INTO archivos (carpeta_id, usuario_id, nombre_original, nombre_fisico, tipo_mime, extension, tamano_bytes, ruta_fisica, descripcion) VALUES
-- Cliente 1 - Campana Verano 2024 (carpeta 1)
(1, 3, 'banner_principal.jpg',  'b1a2c3d4_banner.jpg',      'image/jpeg', 'jpg', 2458621,  '/uploads/3/2024/03/b1a2c3d4_banner.jpg',      'Banner principal 1920x1080'),
(1, 3, 'banner_movil.png',      'e5f6g7h8_banner_mob.png',  'image/png',  'png', 1523467,  '/uploads/3/2024/03/e5f6g7h8_banner_mob.png',  'Banner version movil'),
-- Cliente 1 - Logos Corporativos (carpeta 2)
(2, 3, 'logo_principal.svg',    'i9j0k1l2_logo.svg',        'image/svg+xml', 'svg', 45823, '/uploads/3/2024/03/i9j0k1l2_logo.svg',        'Logo vectorial principal'),
(2, 3, 'logo_horizontal.png',   'm3n4o5p6_logo_h.png',      'image/png',  'png', 892341,  '/uploads/3/2024/03/m3n4o5p6_logo_h.png',       'Logo version horizontal'),
-- Cliente 1 - Fotos de Producto (carpeta 3)
(3, 3, 'producto_001.jpg',      'q7r8s9t0_prod_001.jpg',    'image/jpeg', 'jpg', 3421567,  '/uploads/3/2024/03/q7r8s9t0_prod_001.jpg',    'Foto producto A'),
(3, 3, 'producto_002.jpg',      'u1v2w3x4_prod_002.jpg',    'image/jpeg', 'jpg', 2987654,  '/uploads/3/2024/03/u1v2w3x4_prod_002.jpg',    'Foto producto B'),
-- Cliente 2 - Proyectos Activos (carpeta 4)
(4, 4, 'propuesta_proyecto.pdf','y5z6a7b8_propuesta.pdf',   'application/pdf', 'pdf', 1567890, '/uploads/4/2024/03/y5z6a7b8_propuesta.pdf', 'Propuesta inicial'),
(4, 4, 'contrato.docx',         'c9d0e1f2_contrato.docx',   'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx', 234567, '/uploads/4/2024/03/c9d0e1f2_contrato.docx', 'Contrato firmado'),
-- Cliente 3 - Disenos Web (carpeta 6)
(6, 5, 'mockup_homepage.psd',   'g3h4i5j6_mockup.psd',      'image/vnd.adobe.photoshop', 'psd', 15678901, '/uploads/5/2024/03/g3h4i5j6_mockup.psd', 'Mockup homepage'),
(6, 5, 'recursos_web.zip',      'k7l8m9n0_recursos.zip',    'application/zip', 'zip', 45678901, '/uploads/5/2024/03/k7l8m9n0_recursos.zip', 'Pack de recursos web');

-- =============================================================================
-- SINCRONIZAR almacenamiento_usado
-- Calcula el total real desde archivos (incluye archivos sueltos y en carpetas)
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
-- NOTIFICACIONES DE PRUEBA
-- =============================================================================

INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES
(1, 'sistema', 'Sistema configurado',          'El sistema ha sido configurado correctamente.'),
(3, 'subida',  'Archivo subido',               'Se ha subido correctamente banner_principal.jpg'),
(3, 'alerta',  'Almacenamiento casi lleno',    'Has alcanzado el 80% de tu almacenamiento disponible.'),
(4, 'sistema', 'Nueva carpeta creada',         'Se ha creado la carpeta "Proyectos Activos" correctamente.');

-- =============================================================================
-- LOGS DE ACTIVIDAD DE PRUEBA
-- =============================================================================

INSERT INTO logs_actividad (usuario_id, accion, descripcion, entidad_tipo, entidad_id, ip_address) VALUES
(1, 'login',          'Inicio de sesion del administrador',        'usuario', 1, '127.0.0.1'),
(3, 'login',          'Inicio de sesion del cliente',              'usuario', 3, '192.168.1.100'),
(3, 'crear_carpeta',  'Nueva carpeta: Campana Verano 2024',        'carpeta', 1, '192.168.1.100'),
(3, 'subir_archivo',  'Archivo subido: banner_principal.jpg',      'archivo', 1, '192.168.1.100'),
(1, 'ver_usuario',    'Visualizacion de lista de usuarios',        'usuario', NULL, '127.0.0.1');

-- =============================================================================
-- ADMINISTRADORES DE EMAIL
-- =============================================================================

INSERT INTO administradores_email (nombre, email, recibe_alertas, recibe_resumenes) VALUES
('Admin Principal',  'admin@rksolutions.com',    1, 1),
('Soporte Tecnico',  'soporte@rksolutions.com',  1, 0);

-- =============================================================================
-- FIN DEL SEED — v2.0.0
-- =============================================================================
