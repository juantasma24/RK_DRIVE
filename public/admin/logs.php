<?php
/**
 * Vista: Logs de Actividad (Admin)
 *
 * @var array  $logs           Lista de registros de actividad
 * @var string $filtroAccion   Filtro de búsqueda por acción
 * @var string $filtroUsuario  Filtro de búsqueda por usuario
 * @var string $filtroDesde    Fecha inicio (YYYY-MM-DD)
 * @var string $filtroHasta    Fecha fin (YYYY-MM-DD)
 */
$hayFiltros = $filtroAccion || $filtroUsuario || $filtroDesde || $filtroHasta;

// Construir URL de exportación con los filtros activos
$exportParams = http_build_query(array_filter([
    'page'        => 'admin/logs',
    'action'      => 'export',
    'accion'      => $filtroAccion,
    'usuario'     => $filtroUsuario,
    'fecha_desde' => $filtroDesde,
    'fecha_hasta' => $filtroHasta,
]));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-journal-text me-2 text-primary"></i>Logs de Actividad</h2>
        <p class="text-muted mb-0"><?= count($logs) ?> registros encontrados</p>
    </div>
    <a href="<?= APP_URL ?>/?<?= $exportParams ?>" class="btn btn-outline-success">
        <i class="bi bi-download me-2"></i>Exportar CSV
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= APP_URL ?>/" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="admin/logs">
            <div class="col-md-3">
                <label class="form-label">Filtrar por accion</label>
                <input type="text" class="form-control" name="accion"
                       value="<?= sanitize($filtroAccion) ?>"
                       placeholder="login, upload, delete...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Filtrar por usuario</label>
                <input type="text" class="form-control" name="usuario"
                       value="<?= sanitize($filtroUsuario) ?>"
                       placeholder="Nombre o email...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Desde</label>
                <input type="date" class="form-control" name="fecha_desde"
                       value="<?= sanitize($filtroDesde) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Hasta</label>
                <input type="date" class="form-control" name="fecha_hasta"
                       value="<?= sanitize($filtroHasta) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search me-1"></i>Buscar
                </button>
                <?php if ($hayFiltros): ?>
                <a href="<?= APP_URL ?>/?page=admin/logs" class="btn btn-outline-secondary" title="Limpiar filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (empty($logs)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-journal-x text-muted" style="font-size:4rem;"></i>
        <h5 class="mt-3">No se encontraron registros</h5>
        <?php if ($filtroAccion || $filtroUsuario): ?>
        <p class="text-muted">Prueba con otros filtros de busqueda.</p>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Fecha</th>
                        <th>Usuario</th>
                        <th>Accion</th>
                        <th>Descripcion</th>
                        <th>Entidad</th>
                        <th class="pe-3">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log):
                        $icono = match(true) {
                            str_contains($log['accion'], 'login')    => 'bi-box-arrow-in-right text-success',
                            str_contains($log['accion'], 'logout')   => 'bi-box-arrow-right text-secondary',
                            str_contains($log['accion'], 'upload')   => 'bi-cloud-upload text-info',
                            str_contains($log['accion'], 'delete')   => 'bi-trash text-danger',
                            str_contains($log['accion'], 'create')   => 'bi-plus-circle text-success',
                            str_contains($log['accion'], 'edit')     => 'bi-pencil text-warning',
                            str_contains($log['accion'], 'password') => 'bi-key text-warning',
                            str_contains($log['accion'], 'toggle')   => 'bi-toggle-on text-info',
                            str_contains($log['accion'], 'settings') => 'bi-gear text-muted',
                            default                                   => 'bi-dot text-muted',
                        };
                    ?>
                    <tr>
                        <td class="ps-3 text-nowrap small text-muted">
                            <?= formatDate($log['fecha'], 'd/m/Y') ?><br>
                            <span style="font-size:.7rem;"><?= formatDate($log['fecha'], 'H:i:s') ?></span>
                        </td>
                        <td>
                            <?php if (!empty($log['usuario_nombre'])): ?>
                            <div class="small fw-semibold" style="color:var(--text-primary);">
                                <?= sanitize($log['usuario_nombre']) ?>
                            </div>
                            <div class="text-muted" style="font-size:.7rem;"><?= sanitize($log['usuario_email']) ?></div>
                            <?php else: ?>
                            <span class="text-muted small">Sistema</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border small">
                                <i class="bi <?= $icono ?> me-1"></i><?= sanitize($log['accion']) ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= sanitize($log['descripcion'] ?? '—') ?></td>
                        <td class="small text-muted"><?= sanitize($log['entidad_tipo'] ?? '—') ?></td>
                        <td class="small text-muted pe-3 text-nowrap"><?= sanitize($log['ip_address'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPaginas > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted"><?= $totalRegistros ?> registros totales</small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <?php
                $params = array_filter([
                    'page'       => 'admin/logs',
                    'accion'     => $filtroAccion,
                    'usuario'    => $filtroUsuario,
                    'fecha_desde'=> $filtroDesde,
                    'fecha_hasta'=> $filtroHasta,
                    'p'          => $i,
                ]);
                ?>
                <li class="page-item <?= $i === $paginaActual ? 'active' : '' ?>">
                    <a class="page-link" href="<?= APP_URL ?>/?<?= http_build_query($params) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
