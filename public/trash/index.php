<?php
/**
 * Vista: Papelera de Reciclaje
 *
 * Variables comunes:
 * @var array $archivos   Todos los archivos en papelera (array plano)
 *
 * Solo para admin:
 * @var array $porCliente ['cliente_id' => ['nombre' => '...', 'archivos' => [...]]]
 */

$esAdmin = isAdmin();
$total   = count($archivos);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-trash me-2 text-danger"></i>Papelera</h2>
        <p class="text-muted mb-0">
            <?= $total ?> archivo<?= $total != 1 ? 's' : '' ?> en papelera
            <?php if ($esAdmin && $total > 0): ?>
                de <?= count($porCliente) ?> cliente<?= count($porCliente) != 1 ? 's' : '' ?>
            <?php endif; ?>
        </p>
    </div>
    <?php if ($total > 0): ?>
    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalVaciar">
        <i class="bi bi-trash3 me-2"></i>Vaciar Papelera
    </button>
    <?php endif; ?>
</div>

<?php if ($total === 0): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-trash text-muted" style="font-size:4rem;"></i>
        <h5 class="mt-3">La papelera está vacía</h5>
        <p class="text-muted">Los archivos eliminados aparecerán aquí.</p>
        <?php if ($esAdmin): ?>
        <a href="<?= APP_URL ?>/?page=admin/clients" class="btn btn-primary mt-2">
            <i class="bi bi-folder2-open me-2"></i>Archivos por Cliente
        </a>
        <?php else: ?>
        <a href="<?= APP_URL ?>/?page=folders" class="btn btn-primary mt-2">
            <i class="bi bi-files me-2"></i>Ir a Mis Archivos
        </a>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($esAdmin): ?>
<!-- =====================================================================
     VISTA ADMIN: agrupado por cliente
====================================================================== -->
<div class="alert alert-warning mb-3">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Los archivos en papelera se eliminan automáticamente después de <strong>30 días</strong>.
    Puedes restaurarlos antes de que expiren.
</div>

<?php foreach ($porCliente as $clienteId => $grupo): ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-person-circle me-2" style="color:var(--primary);"></i>
            <strong style="color:var(--text-primary);"><?= sanitize($grupo['nombre']) ?></strong>
            <span class="badge bg-danger-subtle text-danger ms-2"><?= count($grupo['archivos']) ?> archivo<?= count($grupo['archivos']) != 1 ? 's' : '' ?></span>
        </span>
        <a href="<?= APP_URL ?>/?page=admin/clients&action=view&id=<?= $clienteId ?>" class="small text-decoration-none" style="color:var(--primary);">
            Ver archivos activos
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Nombre</th>
                        <th>Carpeta</th>
                        <th>Tamaño</th>
                        <th>Eliminado</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grupo['archivos'] as $archivo): ?>
                    <tr>
                        <td class="ps-3">
                            <i class="bi <?= getFileIcon($archivo['extension']) ?> me-2 text-muted fs-5"></i>
                            <span class="small fw-semibold" title="<?= sanitize($archivo['nombre_original']) ?>">
                                <?= sanitize(truncateText($archivo['nombre_original'], 40)) ?>
                            </span>
                        </td>
                        <td class="small text-muted">
                            <?= $archivo['carpeta_nombre'] ? sanitize($archivo['carpeta_nombre']) : '<span class="fst-italic">Sin carpeta</span>' ?>
                        </td>
                        <td class="small text-muted"><?= formatFileSize($archivo['tamano_bytes']) ?></td>
                        <td class="small text-muted"><?= formatRelativeDate($archivo['fecha_eliminacion']) ?></td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <form method="POST"
                                      action="<?= APP_URL ?>/?page=files&action=restore&id=<?= $archivo['id'] ?>"
                                      class="d-inline">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-outline-success" title="Restaurar">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </form>
                                <button class="btn btn-outline-danger"
                                        onclick="confirmarEliminar(<?= $archivo['id'] ?>, '<?= addslashes(sanitize($archivo['nombre_original'])) ?>')"
                                        title="Eliminar permanentemente">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php else: ?>
<!-- =====================================================================
     VISTA CLIENTE: lista propia
====================================================================== -->
<div class="alert alert-warning mb-3">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Los archivos en la papelera se eliminan automáticamente después de <strong>30 días</strong>.
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Nombre</th>
                        <th>Carpeta</th>
                        <th>Tamaño</th>
                        <th>Eliminado</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($archivos as $archivo): ?>
                    <tr>
                        <td class="ps-3">
                            <i class="bi <?= getFileIcon($archivo['extension']) ?> me-2 text-muted fs-5"></i>
                            <span class="small fw-semibold" title="<?= sanitize($archivo['nombre_original']) ?>">
                                <?= sanitize(truncateText($archivo['nombre_original'], 40)) ?>
                            </span>
                        </td>
                        <td class="small text-muted">
                            <?= $archivo['carpeta_nombre'] ? sanitize($archivo['carpeta_nombre']) : '<span class="fst-italic">Sin carpeta</span>' ?>
                        </td>
                        <td class="small text-muted"><?= formatFileSize($archivo['tamano_bytes']) ?></td>
                        <td class="small text-muted"><?= formatRelativeDate($archivo['fecha_eliminacion']) ?></td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <form method="POST"
                                      action="<?= APP_URL ?>/?page=files&action=restore&id=<?= $archivo['id'] ?>"
                                      class="d-inline">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-outline-success" title="Restaurar">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </form>
                                <button class="btn btn-outline-danger"
                                        onclick="confirmarEliminar(<?= $archivo['id'] ?>, '<?= addslashes(sanitize($archivo['nombre_original'])) ?>')"
                                        title="Eliminar permanentemente">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- Modal: Eliminar permanentemente -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEliminar">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-trash3 me-1"></i>Eliminar Permanentemente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="color:var(--text-secondary);">
                        ¿Eliminar <strong id="eliminar_nombre" style="color:var(--text-primary);"></strong> permanentemente?
                    </p>
                    <p class="text-danger small mb-0">
                        <i class="bi bi-exclamation-circle me-1"></i>Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3 me-1"></i>Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Vaciar Papelera -->
<div class="modal fade" id="modalVaciar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= APP_URL ?>/?page=files&action=empty">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-trash3 me-2"></i>Vaciar Papelera
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($esAdmin): ?>
                    <p style="color:var(--text-secondary);">
                        ¿Eliminar permanentemente los
                        <strong style="color:var(--text-primary);"><?= $total ?> archivo(s)</strong>
                        de <strong style="color:var(--text-primary);"><?= count($porCliente) ?> cliente(s)</strong>?
                    </p>
                    <?php else: ?>
                    <p style="color:var(--text-secondary);">
                        ¿Eliminar permanentemente los
                        <strong style="color:var(--text-primary);"><?= $total ?> archivo(s)</strong>
                        de la papelera?
                    </p>
                    <?php endif; ?>
                    <p class="text-danger small mb-0">
                        <i class="bi bi-exclamation-circle me-1"></i>Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3 me-1"></i>Vaciar Papelera
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmarEliminar(id, nombre) {
    document.getElementById('formEliminar').action =
        '<?= APP_URL ?>/?page=files&action=delete&id=' + id;
    document.getElementById('eliminar_nombre').textContent = '"' + nombre + '"';
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>
