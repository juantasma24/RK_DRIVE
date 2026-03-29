<?php
/**
 * Vista: Configuración del Sistema (Admin)
 *
 * @var array $config    Mapa clave => ['clave','valor','descripcion'] de configuracion_sistema
 * @var array $limpieza  Fila de configuracion_limpieza
 */

// Helper para obtener valor con fallback
$val = fn(string $key, $default = '') => $config[$key]['valor'] ?? $default;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-sliders me-2 text-primary"></i>Configuración del Sistema</h2>
        <p class="text-muted mb-0">Ajusta los parámetros generales de la plataforma</p>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/?page=admin/settings">
    <?= csrfField() ?>

    <div class="row g-4">

        <!-- =====================================================================
             LÍMITES DE ALMACENAMIENTO Y ARCHIVOS
        ====================================================================== -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0"><i class="bi bi-hdd me-2 text-primary"></i>Almacenamiento y Archivos</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">
                            Tamaño máximo por archivo
                            <span class="text-muted small fw-normal">(bytes)</span>
                        </label>
                        <input type="number" class="form-control" name="max_file_size"
                               value="<?= (int)$val('max_file_size', 524288000) ?>" min="1">
                        <div class="form-text">
                            Valor actual: <?= formatFileSize((int)$val('max_file_size', 524288000)) ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            Almacenamiento máximo por cliente
                            <span class="text-muted small fw-normal">(bytes)</span>
                        </label>
                        <input type="number" class="form-control" name="max_storage_client"
                               value="<?= (int)$val('max_storage_client', 2147483648) ?>" min="1">
                        <div class="form-text">
                            Valor actual: <?= formatFileSize((int)$val('max_storage_client', 2147483648)) ?>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Máximo de carpetas por cliente</label>
                        <input type="number" class="form-control" name="max_folders_client"
                               value="<?= (int)$val('max_folders_client', 20) ?>" min="1" max="1000">
                    </div>
                </div>
            </div>
        </div>

        <!-- =====================================================================
             SEGURIDAD Y SESIONES
        ====================================================================== -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0"><i class="bi bi-shield-lock me-2 text-danger"></i>Seguridad y Sesiones</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Intentos de login antes de bloquear</label>
                        <input type="number" class="form-control" name="login_attempts"
                               value="<?= (int)$val('login_attempts', 5) ?>" min="1" max="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            Tiempo de bloqueo tras fallos
                            <span class="text-muted small fw-normal">(minutos)</span>
                        </label>
                        <input type="number" class="form-control" name="lockout_time"
                               value="<?= (int)$val('lockout_time', 15) ?>" min="1" max="1440">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">
                            Duración de sesión
                            <span class="text-muted small fw-normal">(segundos)</span>
                        </label>
                        <input type="number" class="form-control" name="session_lifetime"
                               value="<?= (int)$val('session_lifetime', 7200) ?>" min="300">
                        <div class="form-text">
                            Valor actual: <?= round((int)$val('session_lifetime', 7200) / 60) ?> minutos
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =====================================================================
             CONFIGURACIÓN DE LIMPIEZA AUTOMÁTICA
        ====================================================================== -->
        <?php if ($limpieza): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0"><i class="bi bi-recycle me-2 text-warning"></i>Limpieza Automática (solo lectura)</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="border rounded p-3 text-center">
                                <div class="fs-4 fw-bold text-primary"><?= (int)$limpieza['dias_conservacion'] ?></div>
                                <div class="small text-muted">Días de conservación en papelera</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="border rounded p-3 text-center">
                                <div class="fs-4 fw-bold text-warning"><?= (int)$limpieza['dias_inactividad'] ?></div>
                                <div class="small text-muted">Días de inactividad para alerta</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="border rounded p-3 text-center">
                                <?php if ($limpieza['activa']): ?>
                                <div class="fs-4 fw-bold text-success"><i class="bi bi-check-circle"></i></div>
                                <div class="small text-muted">Limpieza activa</div>
                                <?php else: ?>
                                <div class="fs-4 fw-bold text-secondary"><i class="bi bi-x-circle"></i></div>
                                <div class="small text-muted">Limpieza desactivada</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($limpieza['ultima_ejecucion'])): ?>
                        <div class="col-12">
                            <p class="text-muted small mb-0">
                                <i class="bi bi-clock-history me-1"></i>
                                Última ejecución: <?= formatDate($limpieza['ultima_ejecucion'], 'd/m/Y H:i') ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /row -->

    <!-- Botón guardar -->
    <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-floppy me-2"></i>Guardar Configuración
        </button>
    </div>

</form>


<!-- Tabla de referencia de claves -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-transparent border-0">
        <h6 class="mb-0"><i class="bi bi-table me-2"></i>Todas las claves de configuración</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Clave</th>
                        <th>Valor</th>
                        <th class="pe-3">Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($config as $clave => $row): ?>
                    <tr>
                        <td class="ps-3"><code class="small"><?= sanitize($clave) ?></code></td>
                        <td class="small"><?= sanitize($row['valor']) ?></td>
                        <td class="small text-muted pe-3"><?= sanitize($row['descripcion'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
