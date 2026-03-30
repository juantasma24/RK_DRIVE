<?php
/**
 * Vista: Lista de Carpetas
 *
 * @var array $carpetas  Lista de carpetas con estadísticas
 * @var int   $limite    Máximo de carpetas permitidas
 */
$totalCarpetas = count($carpetas);
$puedCrear     = $totalCarpetas < $limite;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-folder2-open me-2 text-primary"></i>Mis Carpetas</h2>
        <p class="text-muted mb-0"><?= $totalCarpetas ?> de <?= $limite ?> carpetas utilizadas</p>
    </div>
    <?php if ($puedCrear): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
        <i class="bi bi-folder-plus me-2"></i>Nueva Carpeta
    </button>
    <?php else: ?>
    <button class="btn btn-secondary" disabled title="Has alcanzado el límite de carpetas">
        <i class="bi bi-folder-plus me-2"></i>Nueva Carpeta
    </button>
    <?php endif; ?>
</div>

<?php if (empty($carpetas)): ?>
<!-- Estado vacío -->
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-folder2 text-muted" style="font-size: 4rem;"></i>
        <h5 class="mt-3">No tienes carpetas todavía</h5>
        <p class="text-muted">Crea tu primera carpeta para empezar a organizar tus archivos.</p>
        <?php if ($puedCrear): ?>
        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#modalCrear">
            <i class="bi bi-folder-plus me-2"></i>Crear primera carpeta
        </button>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Grid de carpetas -->
<div class="row g-3">
    <?php foreach ($carpetas as $carpeta): ?>
    <div class="col-sm-6 col-lg-4 col-xl-3">
        <div class="card border-0 shadow-sm h-100 folder-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <a href="<?= APP_URL ?>/?page=folders&action=show&id=<?= $carpeta['id'] ?>"
                       class="text-decoration-none text-dark folder-icon">
                        <i class="bi bi-folder-fill text-warning" style="font-size: 2.5rem;"></i>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/?page=folders&action=show&id=<?= $carpeta['id'] ?>">
                                    <i class="bi bi-folder2-open me-2"></i>Abrir
                                </a>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="abrirModalEditar(<?= $carpeta['id'] ?>, '<?= addslashes(sanitize($carpeta['nombre'])) ?>', '<?= addslashes(sanitize($carpeta['descripcion'] ?? '')) ?>')">
                                    <i class="bi bi-pencil me-2"></i>Renombrar
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item text-danger"
                                        onclick="confirmarEliminar(<?= $carpeta['id'] ?>, '<?= addslashes(sanitize($carpeta['nombre'])) ?>', <?= (int)$carpeta['total_archivos'] ?>)">
                                    <i class="bi bi-trash me-2"></i>Eliminar
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <a href="<?= APP_URL ?>/?page=folders&action=show&id=<?= $carpeta['id'] ?>"
                   class="text-decoration-none text-dark">
                    <h6 class="card-title mb-1 text-truncate" title="<?= sanitize($carpeta['nombre']) ?>">
                        <?= sanitize($carpeta['nombre']) ?>
                    </h6>
                </a>

                <?php if (!empty($carpeta['descripcion'])): ?>
                <p class="card-text text-muted small mb-2 text-truncate" title="<?= sanitize($carpeta['descripcion']) ?>">
                    <?= sanitize($carpeta['descripcion']) ?>
                </p>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                    <span class="small text-muted">
                        <i class="bi bi-file-earmark me-1"></i><?= $carpeta['total_archivos'] ?> archivo<?= $carpeta['total_archivos'] != 1 ? 's' : '' ?>
                    </span>
                    <span class="small text-muted">
                        <?= formatFileSize($carpeta['tamano_total']) ?>
                    </span>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <small class="text-muted">
                    <i class="bi bi-calendar3 me-1"></i><?= formatDate($carpeta['fecha_creacion'], 'd/m/Y') ?>
                </small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>


<!-- =====================================================================
     MODAL: CREAR CARPETA
====================================================================== -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= APP_URL ?>/?page=folders&action=create">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-folder-plus me-2"></i>Nueva Carpeta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="crear_nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="crear_nombre" name="nombre"
                               maxlength="255" required autofocus placeholder="Ej: Campañas 2024">
                    </div>
                    <div class="mb-3">
                        <label for="crear_desc" class="form-label">Descripción <span class="text-muted small">(opcional)</span></label>
                        <textarea class="form-control" id="crear_desc" name="descripcion"
                                  rows="2" maxlength="500" placeholder="Breve descripción de la carpeta"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-folder-plus me-2"></i>Crear Carpeta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- =====================================================================
     MODAL: EDITAR CARPETA
====================================================================== -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEditar" action="">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Renombrar Carpeta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editar_nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editar_nombre" name="nombre"
                               maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="editar_desc" class="form-label">Descripción <span class="text-muted small">(opcional)</span></label>
                        <textarea class="form-control" id="editar_desc" name="descripcion"
                                  rows="2" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- =====================================================================
     MODAL: CONFIRMAR ELIMINACIÓN
====================================================================== -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEliminar" action="">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Eliminar Carpeta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar la carpeta <strong id="eliminar_nombre"></strong>?</p>
                    <div id="eliminar_aviso_archivos" class="alert alert-warning d-none">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Esta carpeta tiene archivos. Debes moverlos o eliminarlos antes de borrar la carpeta.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btn_confirmar_eliminar">
                        <i class="bi bi-trash me-2"></i>Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<style>
.folder-card { transition: transform 0.15s, box-shadow 0.15s; }
.folder-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.1) !important; }
.folder-icon i { transition: transform 0.15s; }
.folder-icon:hover i { transform: scale(1.1); }
</style>

<script>
function abrirModalEditar(id, nombre, descripcion) {
    document.getElementById('formEditar').action =
        '<?= APP_URL ?>/?page=folders&action=edit&id=' + id;
    document.getElementById('editar_nombre').value = nombre;
    document.getElementById('editar_desc').value   = descripcion;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

function confirmarEliminar(id, nombre, totalArchivos) {
    document.getElementById('formEliminar').action =
        '<?= APP_URL ?>/?page=folders&action=delete&id=' + id;
    document.getElementById('eliminar_nombre').textContent = '"' + nombre + '"';

    const aviso = document.getElementById('eliminar_aviso_archivos');
    const btnConfirmar = document.getElementById('btn_confirmar_eliminar');

    if (totalArchivos > 0) {
        aviso.classList.remove('d-none');
        btnConfirmar.disabled = true;
    } else {
        aviso.classList.add('d-none');
        btnConfirmar.disabled = false;
    }

    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>
