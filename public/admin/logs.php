<?php
/**
 * Vista: Logs de Actividad (Admin)
 *
 * @var array  $logs           Lista de registros de actividad
 * @var string $filtroAccion   Filtro de búsqueda por acción
 * @var string $filtroUsuario  Filtro de búsqueda por usuario
 */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-journal-text me-2 text-primary"></i>Logs de Actividad</h2>
        <p class="text-muted mb-0"><?= count($logs) ?> registros encontrados</p>
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= APP_URL ?>/" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="admin/logs">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Filtrar por acción</label>
                <input type="text" class="form-control" name="accion"
                       value="<?= sanitize($filtroAccion) ?>"
                       placeholder="Ej: login, upload, delete…">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Filtrar por usuario</label>
                <input type="text" class="form-control" name="usuario"
                       value="<?= sanitize($filtroUsuario) ?>"
                       placeholder="Nombre o email…">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>Buscar
                </button>
                <?php if ($filtroAccion || $filtroUsuario): ?>
                <a href="<?= APP_URL ?>/?page=admin/logs" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Limpiar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (empty($logs)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-journal-x text-muted" style="font-size: 4rem;"></i>
        <h5 class="mt-3">No se encontraron registros</h5>
        <?php if ($filtroAccion || $filtroUsuario): ?>
        <p class="text-muted">Prueba con otros filtros de búsqueda.</p>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Descripción</th>
                        <th>Entidad</th>
                        <th class="pe-3">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log):
                        $icono = match(true) {
                            str_contains($log['accion'], 'login')    => 'bi-box-arrow-in-right text-success',
                            str_contains($log['accion'], 'logout')   => 'bi-box-arrow-right text-secondary',
                            str_contains($log['accion'], 'upload')   => 'bi-cloud-upload text-primary',
                            str_contains($log['accion'], 'delete')   => 'bi-trash text-danger',
                            str_contains($log['accion'], 'create')   => 'bi-plus-circle text-success',
                            str_contains($log['accion'], 'edit')     => 'bi-pencil text-warning',
                            str_contains($log['accion'], 'password') => 'bi-key text-warning',
                            str_contains($log['accion'], 'toggle')   => 'bi-toggle-on text-info',
                            str_contains($log['accion'], 'settings') => 'bi-gear text-secondary',
                            default                                   => 'bi-dot text-muted',
                        };
                    ?>
                    <tr>
                        <td class="ps-3 text-nowrap small text-muted">
                            <?= formatDate($log['fecha'], 'd/m/Y') ?><br>
                            <span style="font-size:.7rem"><?= formatDate($log['fecha'], 'H:i:s') ?></span>
                        </td>
                        <td>
                            <?php if (!empty($log['usuario_nombre'])): ?>
                            <div class="small fw-semibold"><?= sanitize($log['usuario_nombre']) ?></div>
                            <div class="text-muted" style="font-size:.7rem"><?= sanitize($log['usuario_email']) ?></div>
                            <?php else: ?>
                            <span class="text-muted small">Sistema</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border small">
                                <i class="bi <?= $icono ?> me-1"></i><?= sanitize($log['accion']) ?>
                            </span>
                        </td>
                        <td class="small"><?= sanitize($log['descripcion'] ?? '—') ?></td>
                        <td class="small text-muted"><?= sanitize($log['entidad_tipo'] ?? '—') ?></td>
                        <td class="small text-muted pe-3 text-nowrap"><?= sanitize($log['ip_address'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (count($logs) >= 300): ?>
    <div class="card-footer bg-transparent border-0 text-center">
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>Se muestran los últimos 300 registros. Usa los filtros para acotar resultados.
        </small>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
