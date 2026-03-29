<?php
/**
 * Vista: Notificaciones
 *
 * @var array $notificaciones Lista de notificaciones
 */
$iconos = [
    'subida'     => 'bi-cloud-upload text-primary',
    'eliminacion'=> 'bi-trash text-danger',
    'error'      => 'bi-x-circle text-danger',
    'limpieza'   => 'bi-stars text-warning',
    'sistema'    => 'bi-gear text-secondary',
    'alerta'     => 'bi-exclamation-triangle text-warning',
];
?>

<div class="mb-4">
    <h2 class="mb-1"><i class="bi bi-bell me-2 text-primary"></i>Notificaciones</h2>
    <p class="text-muted mb-0"><?= count($notificaciones) ?> notificación<?= count($notificaciones) != 1 ? 'es' : '' ?></p>
</div>

<?php if (empty($notificaciones)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-bell-slash text-muted" style="font-size:4rem;"></i>
        <h5 class="mt-3">Sin notificaciones</h5>
        <p class="text-muted">Aquí aparecerán las notificaciones del sistema.</p>
    </div>
</div>

<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            <?php foreach ($notificaciones as $n):
                $icono = $iconos[$n['tipo']] ?? 'bi-info-circle text-info';
            ?>
            <li class="list-group-item px-4 py-3 <?= !$n['leida'] ? 'bg-primary bg-opacity-10' : '' ?>">
                <div class="d-flex gap-3 align-items-start">
                    <i class="bi <?= $icono ?> fs-5 mt-1 flex-shrink-0"></i>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold small"><?= sanitize($n['titulo']) ?></span>
                            <span class="text-muted" style="font-size:.75rem"><?= formatRelativeDate($n['fecha_creacion']) ?></span>
                        </div>
                        <p class="mb-0 small text-muted"><?= sanitize($n['mensaje']) ?></p>
                    </div>
                    <?php if (!$n['leida']): ?>
                    <span class="badge bg-primary rounded-pill">Nueva</span>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>
