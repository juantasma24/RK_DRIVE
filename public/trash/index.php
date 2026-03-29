<?php
/**
 * Vista: Papelera de Reciclaje
 *
 * @var array $archivos  Archivos en papelera
 */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-trash me-2 text-danger"></i>Papelera</h2>
        <p class="text-muted mb-0"><?= count($archivos) ?> archivo<?= count($archivos) != 1 ? 's' : '' ?> en papelera</p>
    </div>
    <?php if (!empty($archivos)): ?>
    <button class="btn btn-outline-danger" onclick="confirmarVaciarPapelera()">
        <i class="bi bi-trash3 me-2"></i>Vaciar Papelera
    </button>
    <?php endif; ?>
</div>

<?php if (empty($archivos)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-trash text-muted" style="font-size: 4rem;"></i>
        <h5 class="mt-3">La papelera está vacía</h5>
        <p class="text-muted">Los archivos eliminados aparecerán aquí.</p>
        <a href="<?= APP_URL ?>/?page=folders" class="btn btn-primary mt-2">
            <i class="bi bi-folder2-open me-2"></i>Ir a Mis Carpetas
        </a>
    </div>
</div>

<?php else: ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Los archivos en la papelera se eliminan automáticamente después de <strong>30 días</strong>.
</div>

<div class="card border-0 shadow-sm">
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
                            <i class="bi <?= getFileIcon($archivo['extension']) ?> me-2 text-secondary fs-5"></i>
                            <span class="small fw-semibold">
                                <?= sanitize(truncateText($archivo['nombre_original'], 40)) ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= sanitize($archivo['carpeta_nombre']) ?></td>
                        <td class="small"><?= formatFileSize($archivo['tamano_bytes']) ?></td>
                        <td class="small text-muted"><?= formatRelativeDate($archivo['fecha_eliminacion']) ?></td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <form method="POST" action="<?= APP_URL ?>/?page=files&action=restore&id=<?= $archivo['id'] ?>" class="d-inline">
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


<!-- Modal eliminar permanentemente -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEliminar">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-trash3 me-2"></i>Eliminar Permanentemente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Eliminar <strong id="eliminar_nombre"></strong> permanentemente?</p>
                    <p class="text-danger small mb-0"><i class="bi bi-exclamation-circle me-1"></i>Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash3 me-2"></i>Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmarEliminar(id, nombre) {
    document.getElementById('formEliminar').action = '<?= APP_URL ?>/?page=files&action=delete&id=' + id;
    document.getElementById('eliminar_nombre').textContent = '"' + nombre + '"';
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}

function confirmarVaciarPapelera() {
    if (confirm('¿Vaciar toda la papelera? Esta acción no se puede deshacer.')) {
        // Se implementará con AdminController o acción masiva
        alert('Función próximamente disponible.');
    }
}
</script>
