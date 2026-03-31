-- =============================================================================
-- Migración: Rol trabajador con permisos configurables por admin
-- Ejecutar una sola vez sobre la base de datos existente
-- =============================================================================

ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('cliente', 'admin', 'trabajador') NOT NULL DEFAULT 'cliente',
    ADD COLUMN puede_editar_archivos   TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Permiso para editar metadatos de archivos de clientes',
    ADD COLUMN puede_eliminar_archivos TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Permiso para eliminar archivos de clientes permanentemente';

-- Nota: el índice idx_rol ya existe desde el schema.sql original.
