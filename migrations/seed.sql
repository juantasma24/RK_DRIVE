-- =============================================================================
-- RK Marketing Drive - Datos de Semilla (Seed Data)
-- Version: 1.0.0
-- Datos de prueba para desarrollo
-- =============================================================================

USE rk_marketing_drive;

-- =============================================================================
-- LIMPIAR DATOS EXISTENTES (SOLO DESARROLLO)
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE logs_actividad;
TRUNCATE TABLE notificaciones;
TRUNCATE TABLE archivos;
TRUNCATE TABLE carpetas;
TRUNCATE TABLE intentos_login;
TRUNCATE TABLE administradores_email;

-- Solo truncar usuarios si no es produccion
-- TRUNCATE TABLE usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- USUARIOS DE PRUEBA
-- =============================================================================

-- Administrador ya existe del schema.sql, actualizamos si es necesario
UPDATE usuarios SET
    password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@rksolutions.com';
-- Contrasena: Admin123!

-- Insertar clientes de prueba
-- Contrasena para todos: Cliente123!
-- Hash generado con: password_hash('Cliente123!', PASSWORD_BCRYPT)
INSERT INTO usuarios (nombre, email, password_hash, rol, almacenamiento_maximo, activo) VALUES
('Empresa ABC S.L.', 'cliente1@empresaabc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 2147483648, 1),
('Marketing Digital Pro', 'cliente2@marketingpro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 2147483648, 1),
('Agencia Creativa XYZ', 'cliente3@agenciaxyz.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 2147483648, 1),
('Cliente Inactivo', 'inactivo@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 2147483648, 0)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- =============================================================================
-- CARPETAS DE PRUEBA
-- =============================================================================

INSERT INTO carpetas (usuario_id, nombre, descripcion) VALUES
-- Cliente 1 (ID 2)
(2, 'Campana Verano 2024', 'Materiales para la campana de verano'),
(2, 'Logos Corporativos', 'Logos en diferentes formatos'),
(2, 'Fotos de Producto', 'Fotografias de productos'),

-- Cliente 2 (ID 3)
(3, 'Proyectos Activos', 'Documentos de proyectos en curso'),
(3, 'Archivos Historicos', 'Archivos de proyectos completados'),

-- Cliente 3 (ID 4)
(4, 'Diseos Web', 'Mockups y recursos web'),
(4, 'Videos Promocionales', 'Videos para redes sociales'),
(4, 'Presentaciones', 'Presentaciones de clientes'),
(4, 'Documentacion', 'Documentos varios');

-- =============================================================================
-- ARCHIVOS DE PRUEBA (Simulados - Sin archivos reales)
-- =============================================================================

INSERT INTO archivos (carpeta_id, usuario_id, nombre_original, nombre_fisico, tipo_mime, extension, tamano_bytes, ruta_fisica, descripcion) VALUES
-- Carpeta 1 (Campana Verano 2024)
(1, 2, 'banner_principal.jpg', 'b1a2c3d4_banner.jpg', 'image/jpeg', 'jpg', 2458621, '/uploads/2/2024/03/b1a2c3d4_banner.jpg', 'Banner principal 1920x1080'),
(1, 2, 'banner_movil.png', 'e5f6g7h8_banner_mob.png', 'image/png', 'png', 1523467, '/uploads/2/2024/03/e5f6g7h8_banner_mob.png', 'Banner version movil'),

-- Carpeta 2 (Logos Corporativos)
(2, 2, 'logo_principal.svg', 'i9j0k1l2_logo.svg', 'image/svg+xml', 'svg', 45823, '/uploads/2/2024/03/i9j0k1l2_logo.svg', 'Logo vectorial principal'),
(2, 2, 'logo_horizontal.png', 'm3n4o5p6_logo_h.png', 'image/png', 'png', 892341, '/uploads/2/2024/03/m3n4o5p6_logo_h.png', 'Logo version horizontal'),

-- Carpeta 3 (Fotos de Producto)
(3, 2, 'producto_001.jpg', 'q7r8s9t0_prod_001.jpg', 'image/jpeg', 'jpg', 3421567, '/uploads/2/2024/03/q7r8s9t0_prod_001.jpg', 'Foto producto A'),
(3, 2, 'producto_002.jpg', 'u1v2w3x4_prod_002.jpg', 'image/jpeg', 'jpg', 2987654, '/uploads/2/2024/03/u1v2w3x4_prod_002.jpg', 'Foto producto B'),

-- Carpeta 4 (Proyectos Activos - Cliente 2)
(4, 3, 'propuesta_proyecto.pdf', 'y5z6a7b8_propuesta.pdf', 'application/pdf', 'pdf', 1567890, '/uploads/3/2024/03/y5z6a7b8_propuesta.pdf', 'Propuesta inicial del proyecto'),
(4, 3, 'contrato.docx', 'c9d0e1f2_contrato.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx', 234567, '/uploads/3/2024/03/c9d0e1f2_contrato.docx', 'Contrato firmado'),

-- Carpeta 6 (Diseos Web - Cliente 3)
(6, 4, 'mockup_homepage.psd', 'g3h4i5j6_mockup.psd', 'application/octet-stream', 'psd', 15678901, '/uploads/4/2024/03/g3h4i5j6_mockup.psd', 'Mockup homepage en Photoshop'),
(6, 4, 'recursos_web.zip', 'k7l8m9n0_recursos.zip', 'application/zip', 'zip', 45678901, '/uploads/4/2024/03/k7l8m9n0_recursos.zip', 'Pack de recursos web');

-- =============================================================================
-- NOTIFICACIONES DE PRUEBA
-- =============================================================================

INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES
(1, 'sistema', 'Bienvenido al sistema', 'El sistema ha sido configurado correctamente.'),
(2, 'subida', 'Archivo subido', 'Se ha subido correctamente el archivo banner_principal.jpg'),
(2, 'alerta', 'Almacenamiento casi lleno', 'Has alcanzado el 80% de tu almacenamiento disponible.'),
(3, 'sistema', 'Nueva carpeta creada', 'Se ha creado la carpeta "Proyectos Activos" correctamente.');

-- =============================================================================
-- LOGS DE ACTIVIDAD DE PRUEBA
-- =============================================================================

INSERT INTO logs_actividad (usuario_id, accion, descripcion, entidad_tipo, entidad_id, ip_address) VALUES
(1, 'login', 'Inicio de sesion del administrador', 'usuario', 1, '127.0.0.1'),
(2, 'login', 'Inicio de sesion del cliente', 'usuario', 2, '192.168.1.100'),
(2, 'crear_carpeta', 'Nueva carpeta creada: Campana Verano 2024', 'carpeta', 1, '192.168.1.100'),
(2, 'subir_archivo', 'Archivo subido: banner_principal.jpg', 'archivo', 1, '192.168.1.100'),
(1, 'ver_usuario', 'Visualizacion de lista de usuarios', 'usuario', NULL, '127.0.0.1');

-- =============================================================================
-- ADMINISTRADORES DE EMAIL
-- =============================================================================

INSERT INTO administradores_email (nombre, email, recibe_alertas, recibe_resumenes) VALUES
('Admin Principal', 'admin@rksolutions.com', 1, 1),
('Soporte Tecnico', 'soporte@rksolutions.com', 1, 0);

-- =============================================================================
-- ACTUALIZAR ALMACENAMIENTO USADO
-- =============================================================================

-- Calcular el almacenamiento usado por cada usuario basandose en sus archivos
UPDATE usuarios u
JOIN (
    SELECT c.usuario_id, COALESCE(SUM(a.tamano_bytes), 0) AS total
    FROM archivos a
    INNER JOIN carpetas c ON a.carpeta_id = c.id
    WHERE a.en_papelera = 0
    GROUP BY c.usuario_id
) AS stats ON u.id = stats.usuario_id
SET u.almacenamiento_usado = stats.total;

-- =============================================================================
-- FIN DEL SEED
-- =============================================================================