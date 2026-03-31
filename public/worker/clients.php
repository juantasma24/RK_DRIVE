<?php
/**
 * Vista: Clientes (Trabajador)
 *
 * @var array $clientes       Lista de clientes
 * @var bool  $puedeEditar    Permiso de edición del trabajador
 * @var bool  $puedeEliminar  Permiso de eliminación del trabajador
 */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-people me-2 text-primary"></i>Clientes</h2>
        <p class="text-muted mb-0">
            <?= count($clientes) ?> cliente<?= count($clientes) != 1 ? 's' : '' ?> registrados
        </p>
    </div>
    <!-- Permisos activos del trabajador -->
    <div class="d-flex gap-2">
        <span class="badge <?= $puedeEditar ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary' ?> border border-current py-2 px-3">
            <i class="bi bi-pencil me-1"></i><?= $puedeEditar ? 'Puede editar' : 'Solo lectura' ?>
        </span>
        <span class="badge <?= $puedeEliminar ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary' ?> border border-current py-2 px-3">
            <i class="bi bi-trash me-1"></i><?= $puedeEliminar ? 'Puede eliminar' : 'Sin eliminar' ?>
        </span>
    </div>
</div>

<?php if (empty($clientes)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-people text-muted" style="font-size:4rem;"></i>
        <h5 class="mt-3">No hay clientes registrados</h5>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Cliente</th>
                        <th>Estado</th>
                        <th>Carpetas / Archivos</th>
                        <th>Ultimo acceso</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold" style="color:var(--text-primary);">
                                <?= sanitize($c['nombre']) ?>
                            </div>
                            <div class="text-muted small"><?= sanitize($c['email']) ?></div>
                        </td>
                        <td>
                            <?php if ($c['activo']): ?>
                            <span class="badge bg-success-subtle text-success">
                                <i class="bi bi-check-circle me-1"></i>Activo
                            </span>
                            <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary">
                                <i class="bi bi-x-circle me-1"></i>Inactivo
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <i class="bi bi-folder me-1"></i><?= (int)$c['total_carpetas'] ?>
                            &nbsp;/&nbsp;
                            <i class="bi bi-file-earmark me-1"></i><?= (int)$c['total_archivos'] ?>
                        </td>
                        <td class="small text-muted">
                            <?= $c['ultimo_acceso'] ? formatDate($c['ultimo_acceso'], 'd/m/Y H:i') : 'Nunca' ?>
                        </td>
                        <td class="text-end pe-3">
                            <a href="<?= APP_URL ?>/?page=worker/clients&action=view&id=<?= $c['id'] ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-folder2-open me-1"></i>Ver archivos
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
