-- =============================================================================
-- Insertar usuario trabajador de prueba
-- Ejecutar una sola vez (después de add_trabajador_role.sql)
-- Contraseña: password
-- =============================================================================

INSERT INTO usuarios (nombre, email, password_hash, rol, puede_editar_archivos, puede_eliminar_archivos, activo)
VALUES (
    'Trabajador Demo',
    'trabajador@rksolutions.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'trabajador',
    0,
    0,
    1
)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
