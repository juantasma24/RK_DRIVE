<?php
/**
 * Vista: Panel Principal (Dashboard)
 *
 * Vista para clientes:
 * @var array $storage     ['usado', 'maximo', 'porcentaje']
 * @var int   $carpetas    Total de carpetas activas
 * @var int   $archivos    Total de archivos activos
 * @var int   $papelera    Total de archivos en papelera
 * @var array $recientes   Últimos archivos subidos
 * @var array $actividad   Últimas acciones del usuario
 *
 * Vista adicional para admins:
 * @var int   $totalUsuarios
 * @var int   $totalClientes
 * @var int   $totalArchivos
 * @var array $almacenamiento ['usado', 'maximo', 'porcentaje']
 * @var array $clientesStats
 */

$esAdmin = isAdmin();
?>

<!-- Cabecera de bienvenida -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <?php if ($esAdmin): ?>
                <i class="bi bi-speedometer2 me-2 text-primary"></i>Panel de Administración
            <?php else: ?>
                <i class="bi bi-speedometer2 me-2 text-primary"></i>Mi Panel
            <?php endif; ?>
        </h2>
        <p class="text-muted mb-0">
            Bienvenido, <strong><?= sanitize($_SESSION['user_name'] ?? '') ?></strong>.
            <?= date('l, d \d\e F \d\e Y') ?>
        </p>
    </div>
    <?php if (!$esAdmin): ?>
    <a href="<?= APP_URL ?>/?page=folders" class="btn btn-primary">
        <i class="bi bi-folder-plus me-2"></i>Mis Carpetas
    </a>
    <?php endif; ?>
</div>

<?php if ($esAdmin): ?>
<!-- =====================================================================
     TARJETAS DE RESUMEN — ADMIN
====================================================================== -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-subtle text-primary rounded-3 p-3">
                    <i class="bi bi-people fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= $totalUsuarios ?></div>
                    <div class="text-muted small">Usuarios totales</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success-subtle text-success rounded-3 p-3">
                    <i class="bi bi-person-check fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= $totalClientes ?></div>
                    <div class="text-muted small">Clientes activos</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning-subtle text-warning rounded-3 p-3">
                    <i class="bi bi-files fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= $totalArchivos ?></div>
                    <div class="text-muted small">Archivos en el sistema</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info-subtle text-info rounded-3 p-3">
                    <i class="bi bi-hdd-stack fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= formatFileSize($almacenamiento['usado']) ?></div>
                    <div class="text-muted small">Almacenamiento usado</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Almacenamiento global -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="bi bi-hdd me-2"></i>Almacenamiento Global</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-1">
                    <span class="small"><?= formatFileSize($almacenamiento['usado']) ?> usados</span>
                    <span class="small text-muted"><?= formatFileSize($almacenamiento['maximo']) ?> total</span>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar <?= $almacenamiento['porcentaje'] > 80 ? 'bg-danger' : ($almacenamiento['porcentaje'] > 60 ? 'bg-warning' : 'bg-success') ?>"
                         style="width: <?= $almacenamiento['porcentaje'] ?>%"></div>
                </div>
                <small class="text-muted"><?= $almacenamiento['porcentaje'] ?>% del almacenamiento total utilizado</small>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between">
                <h6 class="mb-0"><i class="bi bi-people me-2"></i>Clientes Recientes</h6>
                <a href="<?= APP_URL ?>/?page=admin/users" class="small text-decoration-none">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($clientesStats)): ?>
                <div class="text-center text-muted py-4">No hay clientes registrados.</div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($clientesStats as $c): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-3">
                        <div>
                            <div class="fw-semibold small"><?= sanitize($c['nombre']) ?></div>
                            <div class="text-muted" style="font-size:0.75rem"><?= sanitize($c['email']) ?></div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary-subtle text-primary"><?= $c['archivos'] ?> archivos</span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- =====================================================================
     TARJETAS DE RESUMEN — CLIENTE
====================================================================== -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-subtle text-primary rounded-3 p-3">
                    <i class="bi bi-folder fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= $carpetas ?></div>
                    <div class="text-muted small">Carpetas</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success-subtle text-success rounded-3 p-3">
                    <i class="bi bi-file-earmark fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= $archivos ?></div>
                    <div class="text-muted small">Archivos</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger-subtle text-danger rounded-3 p-3">
                    <i class="bi bi-trash fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= $papelera ?></div>
                    <div class="text-muted small">En papelera</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning-subtle text-warning rounded-3 p-3">
                    <i class="bi bi-hdd fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= $storage['porcentaje'] ?>%</div>
                    <div class="text-muted small">Almacenamiento</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Barra de almacenamiento -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0"><i class="bi bi-hdd me-2"></i>Almacenamiento</h6>
            <span class="small text-muted">
                <?= formatFileSize($storage['usado']) ?> de <?= formatFileSize($storage['maximo']) ?>
            </span>
        </div>
        <div class="progress" style="height: 12px;">
            <div class="progress-bar <?= $storage['porcentaje'] > 80 ? 'bg-danger' : ($storage['porcentaje'] > 60 ? 'bg-warning' : 'bg-success') ?>"
                 style="width: <?= $storage['porcentaje'] ?>%"
                 title="<?= $storage['porcentaje'] ?>% usado"></div>
        </div>
        <?php if ($storage['porcentaje'] >= 80): ?>
        <div class="alert alert-warning mt-2 mb-0 py-2 small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Tu almacenamiento está casi lleno. Elimina archivos innecesarios.
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- =====================================================================
     ARCHIVOS RECIENTES + ACTIVIDAD RECIENTE
====================================================================== -->
<div class="row g-3">
    <!-- Archivos recientes -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Archivos Recientes</h6>
                <a href="<?= APP_URL ?>/?page=folders" class="small text-decoration-none">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recientes)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Aún no hay archivos subidos.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Archivo</th>
                                <th><?= $esAdmin ? 'Usuario' : 'Carpeta' ?></th>
                                <th>Tamaño</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recientes as $archivo): ?>
                            <tr>
                                <td class="ps-3">
                                    <i class="bi <?= getFileIcon($archivo['extension']) ?> me-2 text-secondary"></i>
                                    <span class="small fw-semibold" title="<?= sanitize($archivo['nombre_original']) ?>">
                                        <?= sanitize(truncateText($archivo['nombre_original'], 30)) ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?= sanitize($esAdmin ? ($archivo['usuario_nombre'] ?? '-') : ($archivo['carpeta_nombre'] ?? '-')) ?>
                                </td>
                                <td class="small"><?= formatFileSize($archivo['tamano_bytes']) ?></td>
                                <td class="small text-muted"><?= formatDate($archivo['fecha_subida'], 'd/m/Y') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Actividad reciente -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0">
                <h6 class="mb-0"><i class="bi bi-activity me-2"></i>Actividad Reciente</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($actividad)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                    Sin actividad reciente.
                </div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($actividad as $log):
                        $icono = match(true) {
                            str_contains($log['accion'], 'login')    => 'bi-box-arrow-in-right text-success',
                            str_contains($log['accion'], 'logout')   => 'bi-box-arrow-right text-secondary',
                            str_contains($log['accion'], 'upload')   => 'bi-cloud-upload text-primary',
                            str_contains($log['accion'], 'delete')   => 'bi-trash text-danger',
                            str_contains($log['accion'], 'create')   => 'bi-plus-circle text-success',
                            str_contains($log['accion'], 'password') => 'bi-key text-warning',
                            default                                  => 'bi-dot text-muted',
                        };
                    ?>
                    <li class="list-group-item px-3 py-2">
                        <div class="d-flex gap-2 align-items-start">
                            <i class="bi <?= $icono ?> mt-1"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <?php if ($esAdmin && !empty($log['usuario_nombre'])): ?>
                                <span class="small fw-semibold"><?= sanitize($log['usuario_nombre']) ?>: </span>
                                <?php endif; ?>
                                <span class="small"><?= sanitize($log['descripcion'] ?? $log['accion']) ?></span>
                                <div class="text-muted" style="font-size:0.7rem"><?= formatRelativeDate($log['fecha']) ?></div>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.stat-icon {
    flex-shrink: 0;
    width: 52px;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
