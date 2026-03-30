<?php
/**
 * Vista: Detalle de Carpeta (archivos que contiene)
 *
 * @var array $carpeta   Datos de la carpeta
 * @var array $archivos  Archivos activos dentro de la carpeta
 */
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="<?= APP_URL ?>/?page=folders">
                <i class="bi bi-folder2-open me-1"></i>Mis Carpetas
            </a>
        </li>
        <li class="breadcrumb-item active"><?= sanitize($carpeta['nombre']) ?></li>
    </ol>
</nav>

<!-- Page header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-folder-fill me-2 text-primary"></i><?= sanitize($carpeta['nombre']) ?>
        </h2>
        <?php if (!empty($carpeta['descripcion'])): ?>
        <p class="text-muted mb-0"><?= sanitize($carpeta['descripcion']) ?></p>
        <?php endif; ?>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSubir">
        <i class="bi bi-cloud-upload me-2"></i>Subir Archivo
    </button>
</div>

<!-- File list -->
<?php if (empty($archivos)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-cloud-upload text-muted" style="font-size:4rem;"></i>
        <h5 class="mt-3">Esta carpeta esta vacia</h5>
        <p class="text-muted">Sube tu primer archivo para comenzar.</p>
        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#modalSubir">
            <i class="bi bi-cloud-upload me-2"></i>Subir Archivo
        </button>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-files me-2"></i><?= count($archivos) ?> archivo<?= count($archivos) != 1 ? 's' : '' ?>
        </span>
        <div class="input-group" style="max-width:240px;">
            <span class="input-group-text" style="border-right:none;">
                <i class="bi bi-search" style="font-size:0.8rem;"></i>
            </span>
            <input type="text" id="buscarArchivo" class="form-control"
                   placeholder="Buscar archivo..."
                   style="border-left:none;font-size:0.83rem;">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="tablaArchivos">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Nombre</th>
                        <th>Tipo</th>
                        <th>Tamano</th>
                        <th>Fecha</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($archivos as $archivo): ?>
                    <tr>
                        <td class="ps-3">
                            <i class="bi <?= getFileIcon($archivo['extension']) ?> me-2 text-muted fs-5"></i>
                            <span class="fw-semibold small" title="<?= sanitize($archivo['nombre_original']) ?>">
                                <?= sanitize(truncateText($archivo['nombre_original'], 40)) ?>
                            </span>
                            <?php if (!empty($archivo['descripcion'])): ?>
                            <br><small class="text-muted ms-4">
                                <?= sanitize(truncateText($archivo['descripcion'], 60)) ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= getFileColor($archivo['extension']) ?>-subtle text-<?= getFileColor($archivo['extension']) ?> border border-<?= getFileColor($archivo['extension']) ?>-subtle">
                                <?= strtoupper(sanitize($archivo['extension'])) ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= formatFileSize($archivo['tamano_bytes']) ?></td>
                        <td class="small text-muted"><?= formatDate($archivo['fecha_subida']) ?></td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= APP_URL ?>/?page=files&action=download&id=<?= $archivo['id'] ?>"
                                   class="btn btn-outline-primary" title="Descargar">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button class="btn btn-outline-danger"
                                        onclick="confirmarMoverPapelera(<?= $archivo['id'] ?>, '<?= addslashes(sanitize($archivo['nombre_original'])) ?>')"
                                        title="Mover a papelera">
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
     MODAL: SUBIR ARCHIVO
====================================================================== -->
<div class="modal fade" id="modalSubir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= APP_URL ?>/?page=files&action=upload"
                  enctype="multipart/form-data" id="formSubir">
                <?= csrfField() ?>
                <input type="hidden" name="carpeta_id" value="<?= $carpeta['id'] ?>">

                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cloud-upload"></i>Subir Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="archivo_file" class="form-label">
                            Archivo <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control" id="archivo_file" name="archivo" required>
                        <div class="form-text">Tamano maximo: <?= formatFileSize(MAX_FILE_SIZE) ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="archivo_desc" class="form-label">
                            Descripcion <span class="text-muted small fw-normal">(opcional)</span>
                        </label>
                        <input type="text" class="form-control" id="archivo_desc" name="descripcion"
                               maxlength="255" placeholder="Descripcion breve del archivo">
                    </div>

                    <!-- Barra de progreso (visible al subir) -->
                    <div id="uploadProgress" class="d-none">
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                 style="width:100%"></div>
                        </div>
                        <small class="text-muted mt-1 d-block text-center">Subiendo archivo...</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSubir">
                        <i class="bi bi-cloud-upload"></i>Subir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- =====================================================================
     MODAL: CONFIRMAR MOVER A PAPELERA
====================================================================== -->
<div class="modal fade" id="modalPapelera" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formPapelera" action="">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-trash"></i>Mover a Papelera</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="color:var(--text-secondary);">
                        ¿Mover <strong id="papelera_nombre" style="color:var(--text-primary);"></strong>
                        a la papelera?
                    </p>
                    <p class="small mb-0 text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        El archivo podra recuperarse desde la papelera.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i>Mover a Papelera
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
// Busqueda en tabla de archivos
document.getElementById('buscarArchivo')?.addEventListener('input', function () {
    const term  = this.value.toLowerCase();
    const filas = document.querySelectorAll('#tablaArchivos tbody tr');
    filas.forEach(fila => {
        fila.style.display = fila.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});

// Modal mover a papelera
function confirmarMoverPapelera(id, nombre) {
    document.getElementById('formPapelera').action =
        '<?= APP_URL ?>/?page=files&action=trash&id=' + id;
    document.getElementById('papelera_nombre').textContent = '"' + nombre + '"';
    new bootstrap.Modal(document.getElementById('modalPapelera')).show();
}

// Mostrar progreso al subir
document.getElementById('formSubir')?.addEventListener('submit', function () {
    document.getElementById('uploadProgress').classList.remove('d-none');
    document.getElementById('btnSubir').disabled = true;
});
</script>
