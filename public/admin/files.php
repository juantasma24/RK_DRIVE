<?php
/**
 * Vista: Gestión de Archivos (Admin)
 *
 * @var array $archivos   Lista de todos los archivos del sistema
 * @var int   $totalSize  Tamaño total en bytes de archivos activos
 */
$totalArchivos = count($archivos);
$activos       = array_filter($archivos, fn($a) => !$a['en_papelera']);
$enPapelera    = array_filter($archivos, fn($a) =>  $a['en_papelera']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-files me-2 text-primary"></i>Gestion de Archivos</h2>
        <p class="text-muted mb-0">
            <?= $totalArchivos ?> archivo<?= $totalArchivos != 1 ? 's' : '' ?> en el sistema
            &mdash; <?= formatFileSize($totalSize) ?> en uso
        </p>
    </div>
</div>

<!-- Resumen rapido -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-file-earmark-check fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold" style="line-height:1.1;"><?= count($activos) ?></div>
                    <div class="text-muted small mt-1">Archivos activos</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-trash fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold" style="line-height:1.1;"><?= count($enPapelera) ?></div>
                    <div class="text-muted small mt-1">En papelera</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info-subtle text-info">
                    <i class="bi bi-hdd-stack fs-4"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold" style="line-height:1.1;"><?= formatFileSize($totalSize) ?></div>
                    <div class="text-muted small mt-1">Almacenamiento usado</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($archivos)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-inbox text-muted" style="font-size:4rem;"></i>
        <h5 class="mt-3">No hay archivos en el sistema</h5>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Archivo</th>
                        <th>Usuario</th>
                        <th>Carpeta</th>
                        <th>Tamano</th>
                        <th>Estado</th>
                        <th>Fecha subida</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($archivos as $a): ?>
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi <?= getFileIcon($a['extension']) ?> text-muted fs-5 flex-shrink-0"></i>
                                <div>
                                    <div class="small fw-semibold text-truncate"
                                         style="max-width:200px;color:var(--text-primary);"
                                         title="<?= sanitize($a['nombre_original']) ?>">
                                        <?= sanitize(truncateText($a['nombre_original'], 35)) ?>
                                    </div>
                                    <div class="text-muted" style="font-size:.7rem;">.<?= sanitize($a['extension']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small fw-semibold" style="color:var(--text-primary);">
                                <?= sanitize($a['usuario_nombre']) ?>
                            </div>
                            <div class="text-muted" style="font-size:.7rem;"><?= sanitize($a['usuario_email']) ?></div>
                        </td>
                        <td class="small text-muted"><?= sanitize($a['carpeta_nombre']) ?></td>
                        <td class="small text-muted"><?= formatFileSize($a['tamano_bytes']) ?></td>
                        <td>
                            <?php if ($a['en_papelera']): ?>
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="bi bi-trash me-1"></i>Papelera
                            </span>
                            <?php else: ?>
                            <span class="badge bg-success-subtle text-success">
                                <i class="bi bi-check-circle me-1"></i>Activo
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= formatDate($a['fecha_subida'], 'd/m/Y H:i') ?></td>
                        <td class="text-end pe-3">
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="confirmarEliminar(<?= $a['id'] ?>, '<?= addslashes(sanitize($a['nombre_original'])) ?>')"
                                    title="Eliminar permanentemente">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- =====================================================================
     MODAL: CONFIRMAR ELIMINACIÓN
====================================================================== -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEliminar" action="">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-trash"></i>Eliminar Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="color:var(--text-secondary);">
                        ¿Estas seguro de que deseas eliminar permanentemente el archivo
                        <strong id="eliminar_nombre" style="color:var(--text-primary);"></strong>?
                    </p>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        El archivo fisico se borrara del servidor. Esta accion no se puede deshacer.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i>Eliminar Permanentemente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
function confirmarEliminar(id, nombre) {
    document.getElementById('formEliminar').action =
        '<?= APP_URL ?>/?page=admin/files&action=delete&id=' + id;
    document.getElementById('eliminar_nombre').textContent = '"' + nombre + '"';
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>
