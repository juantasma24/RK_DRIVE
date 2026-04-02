<?php
/**
 * Vista: Panel de Clientes (Trabajador)
 *
 * @var int   $totalClientes  Clientes activos
 * @var int   $totalArchivos  Archivos activos de clientes
 * @var int   $totalPapelera  Archivos en papelera de clientes
 * @var array $actividad      Últimas acciones de clientes
 */
?>

<!-- Page header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-speedometer2 me-2 text-primary"></i>Panel de Clientes
        </h2>
        <p class="text-muted mb-0">
            Bienvenido, <strong style="color:var(--text-primary);"><?= sanitize($_SESSION['user_name'] ?? '') ?></strong>.
            <?php
            $_dias   = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
            $_mesesD = ['January'=>'Enero','February'=>'Febrero','March'=>'Marzo','April'=>'Abril','May'=>'Mayo','June'=>'Junio','July'=>'Julio','August'=>'Agosto','September'=>'Septiembre','October'=>'Octubre','November'=>'Noviembre','December'=>'Diciembre'];
            echo $_dias[date('l')] . ', ' . date('d') . ' de ' . $_mesesD[date('F')] . ' de ' . date('Y');
            ?>
        </p>
    </div>
    <a href="<?= APP_URL ?>/?page=worker/clients" class="btn btn-primary">
        <i class="bi bi-people me-2"></i>Ver Clientes
    </a>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-people fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold" style="line-height:1.1;"><?= $totalClientes ?></div>
                    <div class="small text-muted mt-1">Clientes activos</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-files fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold" style="line-height:1.1;"><?= $totalArchivos ?></div>
                    <div class="small text-muted mt-1">Archivos activos</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger-subtle text-danger">
                    <i class="bi bi-trash fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold" style="line-height:1.1;"><?= $totalPapelera ?></div>
                    <div class="small text-muted mt-1">En papelera</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actividad reciente de clientes -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2"></i>Actividad Reciente de Clientes</span>
        <a href="<?= APP_URL ?>/?page=worker/clients" class="small text-decoration-none" style="color:var(--primary);">
            Ver clientes
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($actividad)): ?>
        <div class="text-center text-muted py-5 small">
            <i class="bi bi-clock-history" style="font-size:2.5rem;display:block;margin-bottom:.75rem;"></i>
            Sin actividad reciente de clientes.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Cliente</th>
                        <th>Acción</th>
                        <th class="pe-3">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actividad as $log): ?>
                    <tr>
                        <td class="ps-3">
                            <span class="small fw-semibold" style="color:var(--text-primary);">
                                <?= sanitize($log['usuario_nombre']) ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= sanitize($log['descripcion']) ?></td>
                        <td class="small text-muted pe-3 text-nowrap">
                            <?= formatRelativeDate($log['fecha']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
