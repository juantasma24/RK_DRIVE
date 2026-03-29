-- =============================================================================
-- RK Marketing Drive - Índices de rendimiento
-- Ejecutar una sola vez tras schema.sql y seed.sql
-- =============================================================================

-- archivos: columnas usadas frecuentemente en JOIN y WHERE
ALTER TABLE archivos
    ADD INDEX IF NOT EXISTS idx_archivos_carpeta    (carpeta_id),
    ADD INDEX IF NOT EXISTS idx_archivos_usuario    (usuario_id),
    ADD INDEX IF NOT EXISTS idx_archivos_papelera   (en_papelera),
    ADD INDEX IF NOT EXISTS idx_archivos_usuario_papelera (usuario_id, en_papelera);

-- carpetas: columnas usadas en JOIN y WHERE
ALTER TABLE carpetas
    ADD INDEX IF NOT EXISTS idx_carpetas_usuario        (usuario_id),
    ADD INDEX IF NOT EXISTS idx_carpetas_usuario_activa (usuario_id, activa);

-- logs_actividad: filtros por usuario y fecha
ALTER TABLE logs_actividad
    ADD INDEX IF NOT EXISTS idx_logs_usuario       (usuario_id),
    ADD INDEX IF NOT EXISTS idx_logs_usuario_fecha (usuario_id, fecha);

-- notificaciones: listado y marcado de leídas
ALTER TABLE notificaciones
    ADD INDEX IF NOT EXISTS idx_notif_usuario       (usuario_id),
    ADD INDEX IF NOT EXISTS idx_notif_usuario_leida (usuario_id, leida);

-- intentos_login: verificación de bloqueo por IP
ALTER TABLE intentos_login
    ADD INDEX IF NOT EXISTS idx_intentos_ip       (ip_address),
    ADD INDEX IF NOT EXISTS idx_intentos_ip_fecha (ip_address, fecha);
