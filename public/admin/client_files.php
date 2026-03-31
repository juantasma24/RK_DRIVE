<?php
/**
 * Vista: Archivos de un Cliente (Admin)
 *
 * @var \App\Entity\Usuario $usuario         Cliente seleccionado
 * @var array               $archivos        Archivos del cliente (resultado DBAL)
 * @var array               $carpetas        Carpetas activas del cliente
 * @var array               $filters         Filtros activos
 * @var string              $enPapeleraFiltro Valor raw del filtro en_papelera ('')
 */
$totalArchivos = count($archivos);
$activos       = array_filter($archivos, fn($a) => !$a['en_papelera']);
$enPapelera    = array_filter($archivos, fn($a) =>  $a['en_papelera']);
$totalBytes    = array_sum(array_column($archivos, 'tamano_bytes'));

$baseUrl = APP_URL . '/?page=admin/clients&action=view&id=' . $usuario->getId();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="<?= APP_URL ?>/?page=admin/clients" style="color:var(--color-primary);">
                <i class="bi bi-people me-1"></i>Clientes
            </a>
        </li>
        <li class="breadcrumb-item active"><?= sanitize($usuario->getNombre()) ?></li>
    </ol>
</nav>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-folder2-open me-2 text-primary"></i><?= sanitize($usuario->getNombre()) ?>
        </h2>
        <p class="text-muted mb-0 small"><?= sanitize($usuario->getEmail()) ?></p>
    </div>
    <a href="<?= APP_URL ?>/?page=admin/clients" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<!-- Stats rápidas -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-file-earmark-check fs-5"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold" style="line-height:1.1;"><?= count($activos) ?></div>
                    <div class="text-muted" style="font-size:.75rem;">Activos</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-trash fs-5"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold" style="line-height:1.1;"><?= count($enPapelera) ?></div>
                    <div class="text-muted" style="font-size:.75rem;">Papelera</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="stat-icon bg-info-subtle text-info">
                    <i class="bi bi-hdd fs-5"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold" style="line-height:1.1;"><?= formatFileSize($totalBytes) ?></div>
                    <div class="text-muted" style="font-size:.75rem;">Mostrados</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <?php
                $pct = $usuario->getAlmacenamientoMaximo() > 0
                    ? round(($usuario->getAlmacenamientoUsado() / $usuario->getAlmacenamientoMaximo()) * 100)
                    : 0;
                $colorPct = $pct > 80 ? 'danger' : ($pct > 60 ? 'warning' : 'success');
                ?>
                <div class="stat-icon bg-<?= $colorPct ?>-subtle text-<?= $colorPct ?>">
                    <i class="bi bi-hdd-stack fs-5"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold" style="line-height:1.1;"><?= $pct ?>%</div>
                    <div class="text-muted" style="font-size:.75rem;">
                        <?= formatFileSize($usuario->getAlmacenamientoUsado()) ?> /
                        <?= formatFileSize($usuario->getAlmacenamientoMaximo()) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= APP_URL ?>/" class="row g-2 align-items-end">
            <input type="hidden" name="page"   value="admin/clients">
            <input type="hidden" name="action" value="view">
            <input type="hidden" name="id"     value="<?= $usuario->getId() ?>">

            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small mb-1">Estado</label>
                <select name="en_papelera" class="form-select form-select-sm">
                    <option value=""  <?= $enPapeleraFiltro === ''  ? 'selected' : '' ?>>Todos</option>
                    <option value="0" <?= $enPapeleraFiltro === '0' ? 'selected' : '' ?>>Solo activos</option>
                    <option value="1" <?= $enPapeleraFiltro === '1' ? 'selected' : '' ?>>Solo papelera</option>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small mb-1">Carpeta</label>
                <select name="carpeta_id" class="form-select form-select-sm">
                    <option value="">Todas las carpetas</option>
                    <?php foreach ($carpetas as $cap): ?>
                    <option value="<?= $cap['id'] ?>"
                        <?= isset($filters['carpeta_id']) && $filters['carpeta_id'] == $cap['id'] ? 'selected' : '' ?>>
                        <?= sanitize($cap['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label small mb-1">Tipo</label>
                <input type="text" name="extension" class="form-control form-control-sm"
                       placeholder="pdf, jpg…"
                       value="<?= sanitize($filters['extension'] ?? '') ?>">
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small mb-1">Buscar nombre</label>
                <input type="text" name="busqueda" class="form-control form-control-sm"
                       placeholder="nombre del archivo…"
                       value="<?= sanitize($filters['busqueda'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-1 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1" title="Filtrar">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="<?= $baseUrl ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de archivos -->
<?php if (empty($archivos)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-inbox text-muted" style="font-size:4rem;"></i>
        <h5 class="mt-3">No se encontraron archivos</h5>
        <?php if (!empty($filters)): ?>
        <a href="<?= $baseUrl ?>" class="btn btn-outline-secondary btn-sm mt-2">
            <i class="bi bi-x-lg me-1"></i>Limpiar filtros
        </a>
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
                        <th class="ps-3">Archivo</th>
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
                                         style="max-width:220px;color:var(--text-primary);"
                                         title="<?= sanitize($a['nombre_original']) ?>">
                                        <?= sanitize(truncateText($a['nombre_original'], 38)) ?>
                                    </div>
                                    <div class="text-muted" style="font-size:.7rem;">.<?= sanitize($a['extension']) ?></div>
                                </div>
                            </div>
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
                        <td class="small text-muted">
                            <?= formatDate($a['fecha_subida'], 'd/m/Y H:i') ?>
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-flex justify-content-end gap-1">
                                <!-- Descargar -->
                                <a href="<?= APP_URL ?>/?page=admin/clients&action=download&id=<?= $a['id'] ?>"
                                   class="btn btn-sm btn-outline-secondary"
                                   title="Descargar">
                                    <i class="bi bi-download"></i>
                                </a>
                                <!-- Editar -->
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick="abrirModalEditar(
                                            <?= $a['id'] ?>,
                                            '<?= addslashes(sanitize($a['nombre_original'])) ?>',
                                            '<?= addslashes(sanitize($a['descripcion'] ?? '')) ?>'
                                        )"
                                        title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <!-- Eliminar -->
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="abrirModalEliminar(
                                            <?= $a['id'] ?>,
                                            '<?= addslashes(sanitize($a['nombre_original'])) ?>'
                                        )"
                                        title="Eliminar permanentemente">
                                    <i class="bi bi-trash"></i>
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


<!-- =====================================================================
     MODAL: EDITAR ARCHIVO
====================================================================== -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEditar" action="">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editar_nombre"
                               name="nombre_original" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripcion</label>
                        <textarea class="form-control" id="editar_descripcion"
                                  name="descripcion" rows="3" maxlength="500"
                                  placeholder="Descripcion opcional…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- =====================================================================
     MODAL: ELIMINAR ARCHIVO
====================================================================== -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEliminar" action="">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Eliminar Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="color:var(--text-secondary);">
                        ¿Estas seguro de que deseas eliminar permanentemente
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
                        <i class="bi bi-trash me-1"></i>Eliminar Permanentemente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
function abrirModalEditar(id, nombre, descripcion) {
    document.getElementById('formEditar').action =
        '<?= APP_URL ?>/?page=admin/clients&action=edit&id=' + id;
    document.getElementById('editar_nombre').value      = nombre;
    document.getElementById('editar_descripcion').value = descripcion;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

function abrirModalEliminar(id, nombre) {
    document.getElementById('formEliminar').action =
        '<?= APP_URL ?>/?page=admin/clients&action=delete&id=' + id;
    document.getElementById('eliminar_nombre').textContent = '"' + nombre + '"';
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>
