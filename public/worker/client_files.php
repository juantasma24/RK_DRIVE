<?php
/**
 * Vista: Archivos de un Cliente (Trabajador)
 *
 * @var \App\Entity\Usuario $usuario
 * @var array               $archivos
 * @var array               $carpetas
 * @var array               $filters
 * @var string              $enPapeleraFiltro
 * @var bool                $puedeEditar
 * @var bool                $puedeEliminar
 */
$totalBytes = array_sum(array_column($archivos, 'tamano_bytes'));
$baseUrl    = APP_URL . '/?page=worker/clients&action=view&id=' . $usuario->getId();

function previewTypeWorker(string $ext): string {
    $ext = strtolower($ext);
    if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg','bmp'])) return 'image';
    if (in_array($ext, ['mp4','webm']))                                return 'video';
    if (in_array($ext, ['mp3','wav','ogg','aac','m4a']))               return 'audio';
    if ($ext === 'pdf')                                                return 'pdf';
    if (in_array($ext, ['txt','csv']))                                 return 'text';
    return 'none';
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="<?= APP_URL ?>/?page=worker/clients" style="color:var(--color-primary);">
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
    <div class="d-flex align-items-center gap-2">
        <!-- Badges de permisos activos -->
        <?php if ($puedeEditar): ?>
        <span class="badge bg-warning-subtle text-warning border py-2">
            <i class="bi bi-pencil me-1"></i>Edición activa
        </span>
        <?php endif; ?>
        <?php if ($puedeEliminar): ?>
        <span class="badge bg-danger-subtle text-danger border py-2">
            <i class="bi bi-trash me-1"></i>Eliminación activa
        </span>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/?page=worker/clients" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-file-earmark-check fs-5"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold" style="line-height:1.1;">
                        <?= count(array_filter($archivos, fn($a) => !$a['en_papelera'])) ?>
                    </div>
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
                    <div class="fs-4 fw-bold" style="line-height:1.1;">
                        <?= count(array_filter($archivos, fn($a) => $a['en_papelera'])) ?>
                    </div>
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
                <div class="stat-icon bg-secondary-subtle text-secondary">
                    <i class="bi bi-folder fs-5"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold" style="line-height:1.1;"><?= count($carpetas) ?></div>
                    <div class="text-muted" style="font-size:.75rem;">Carpetas</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= APP_URL ?>/" class="row g-2 align-items-end">
            <input type="hidden" name="page"   value="worker/clients">
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

<!-- Barra de controles -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted small me-1">
                <i class="bi bi-files me-1"></i>
                <span id="contadorArchivosWorker"><?= count($archivos) ?></span>
                archivo<?= count($archivos) != 1 ? 's' : '' ?>
            </span>
            <div class="d-flex align-items-center gap-1 ms-2">
                <span class="text-muted small">Ordenar:</span>
                <button class="btn btn-sm btn-outline-secondary sort-btn-worker active-sort" data-sort="nombre">
                    <i class="bi bi-sort-alpha-down me-1"></i>Nombre
                </button>
                <button class="btn btn-sm btn-outline-secondary sort-btn-worker" data-sort="tipo">
                    <i class="bi bi-funnel me-1"></i>Tipo
                </button>
                <button class="btn btn-sm btn-outline-secondary sort-btn-worker" data-sort="fecha">
                    <i class="bi bi-calendar me-1"></i>Fecha
                </button>
            </div>
            <div class="flex-grow-1"></div>
            <div class="input-group input-group-sm" style="max-width:200px;">
                <span class="input-group-text"><i class="bi bi-search" style="font-size:.8rem;"></i></span>
                <input type="text" id="buscarArchivoWorker" class="form-control" placeholder="Buscar..." style="font-size:.83rem;">
            </div>
            <div class="btn-group btn-group-sm" role="group">
                <button id="btnVistaListaWorker" class="btn btn-outline-secondary active" title="Vista lista">
                    <i class="bi bi-list-ul"></i>
                </button>
                <button id="btnVistaGridWorker" class="btn btn-outline-secondary" title="Vista cuadricula">
                    <i class="bi bi-grid-3x3-gap"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div id="vistaListaWorker" class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaArchivosWorker">
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
                    <?php foreach ($archivos as $a):
                        $ptW = previewTypeWorker($a['extension']);
                    ?>
                    <tr data-nombre="<?= strtolower(sanitize($a['nombre_original'])) ?>"
                        data-tipo="<?= strtolower(sanitize($a['extension'])) ?>"
                        data-fecha="<?= $a['fecha_subida'] ?>">
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
                        <td class="small text-muted"><?= formatDate($a['fecha_subida'], 'd/m/Y H:i') ?></td>
                        <td class="text-end pe-3">
                            <div class="d-flex justify-content-end gap-1">
                                <!-- Vista previa -->
                                <?php if ($ptW !== 'none'): ?>
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick="abrirPreviewWorker(<?= $a['id'] ?>, <?= json_encode($a['nombre_original'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, '<?= $ptW ?>')"
                                        title="Vista previa">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php endif; ?>
                                <!-- Descargar -->
                                <a href="<?= APP_URL ?>/?page=worker/clients&action=download&id=<?= $a['id'] ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Descargar">
                                    <i class="bi bi-download"></i>
                                </a>
                                <!-- Editar (solo si tiene permiso) -->
                                <?php if ($puedeEditar): ?>
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick="abrirModalEditar(
                                            <?= $a['id'] ?>,
                                            <?= json_encode($a['nombre_original'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                                            <?= json_encode($a['descripcion'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
                                        )"
                                        title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php endif; ?>
                                <!-- Eliminar (solo si tiene permiso) -->
                                <?php if ($puedeEliminar): ?>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="abrirModalEliminar(
                                            <?= $a['id'] ?>,
                                            <?= json_encode($a['nombre_original'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
                                        )"
                                        title="Eliminar permanentemente">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Vista cuadricula (Worker) -->
<div id="vistaGridWorker" class="d-none">
    <div class="row g-3" id="gridArchivosWorker">
        <?php foreach ($archivos as $a):
            $ptG   = previewTypeWorker($a['extension']);
            $esImg = ($ptG === 'image');
            $prevB = APP_URL . '/?page=files&action=preview&id=';
        ?>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2 grid-item-worker"
             data-nombre="<?= strtolower(sanitize($a['nombre_original'])) ?>"
             data-tipo="<?= strtolower(sanitize($a['extension'])) ?>"
             data-fecha="<?= $a['fecha_subida'] ?>">
            <div class="card h-100 file-card">
                <div class="file-card-thumb d-flex align-items-center justify-content-center position-relative"
                     style="height:110px;overflow:hidden;border-radius:.5rem .5rem 0 0;
                            background:var(--surface-2,#1e1e1e);
                            cursor:<?= $ptG !== 'none' ? 'pointer' : 'default' ?>;"
                     <?php if ($ptG !== 'none'): ?>
                     onclick="abrirPreviewWorker(<?= $a['id'] ?>, <?= json_encode($a['nombre_original'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, '<?= $ptG ?>')"
                     <?php endif; ?>>
                    <?php if ($esImg): ?>
                        <img src="<?= $prevB . $a['id'] ?>" alt="" style="width:100%;height:110px;object-fit:cover;" loading="lazy"
                             onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'bi <?= getFileIcon($a['extension']) ?> text-muted',style:'font-size:2.5rem'}))">
                    <?php else: ?>
                        <i class="bi <?= getFileIcon($a['extension']) ?> text-muted" style="font-size:2.5rem;"></i>
                    <?php endif; ?>
                </div>
                <div class="card-body p-2">
                    <p class="mb-1 fw-semibold text-truncate" title="<?= sanitize($a['nombre_original']) ?>"
                       style="font-size:.75rem;color:var(--text-primary);">
                        <?= sanitize(truncateText($a['nombre_original'], 26)) ?>
                    </p>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="badge bg-<?= getFileColor($a['extension']) ?>-subtle text-<?= getFileColor($a['extension']) ?>" style="font-size:.62rem;">
                            <?= strtoupper(sanitize($a['extension'])) ?>
                        </span>
                        <span class="text-muted" style="font-size:.67rem;"><?= formatFileSize($a['tamano_bytes']) ?></span>
                    </div>
                </div>
                <div class="card-footer p-1 d-flex gap-1 justify-content-end">
                    <?php if ($ptG !== 'none'): ?>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                            onclick="abrirPreviewWorker(<?= $a['id'] ?>, <?= json_encode($a['nombre_original'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, '<?= $ptG ?>')"
                            title="Vista previa"><i class="bi bi-eye" style="font-size:.75rem;"></i></button>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>/?page=worker/clients&action=download&id=<?= $a['id'] ?>"
                       class="btn btn-sm btn-outline-secondary py-0 px-2" title="Descargar">
                        <i class="bi bi-download" style="font-size:.75rem;"></i></a>
                    <?php if ($puedeEditar): ?>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                            onclick="abrirModalEditar(<?= $a['id'] ?>,<?= json_encode($a['nombre_original'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,<?= json_encode($a['descripcion'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)"
                            title="Editar"><i class="bi bi-pencil" style="font-size:.75rem;"></i></button>
                    <?php endif; ?>
                    <?php if ($puedeEliminar): ?>
                    <button class="btn btn-sm btn-outline-danger py-0 px-2"
                            onclick="abrirModalEliminar(<?= $a['id'] ?>,<?= json_encode($a['nombre_original'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)"
                            title="Eliminar"><i class="bi bi-trash" style="font-size:.75rem;"></i></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>


<!-- =====================================================================
     MODAL: VISTA PREVIA
====================================================================== -->
<div class="modal fade" id="modalPreviewWorker" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title text-truncate me-3" id="previewWorkerNombre" style="max-width:70%;"></h6>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <a id="previewWorkerDescargar" href="#" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i>Descargar
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0 text-center" id="previewWorkerBody"
                 style="min-height:300px;max-height:80vh;overflow:auto;
                        background:var(--surface-1,#141414);display:flex;
                        align-items:center;justify-content:center;">
            </div>
        </div>
    </div>
</div>


<!-- =====================================================================
     MODAL: EDITAR ARCHIVO
====================================================================== -->
<?php if ($puedeEditar): ?>
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
<?php endif; ?>


<!-- =====================================================================
     MODAL: ELIMINAR ARCHIVO
====================================================================== -->
<?php if ($puedeEliminar): ?>
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
<?php endif; ?>


<style>
.active-sort { background:var(--color-primary,#5ea84a)!important;color:#0d0d0d!important;border-color:var(--color-primary,#5ea84a)!important; }
.file-card   { transition:transform .18s ease,box-shadow .18s ease; }
.file-card:hover { transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.35); }
</style>
<script>
const APP_URL_WORKER = '<?= APP_URL ?>';

// ── Vista lista/grid ──────────────────────────────────────────────────────
(function(){
    let vistaWorker = localStorage.getItem('rk-view-worker-files') || 'lista';
    function aplicarVistaWorker(v) {
        vistaWorker = v;
        localStorage.setItem('rk-view-worker-files', v);
        const lista = document.getElementById('vistaListaWorker');
        const grid  = document.getElementById('vistaGridWorker');
        const btnL  = document.getElementById('btnVistaListaWorker');
        const btnG  = document.getElementById('btnVistaGridWorker');
        if (!lista || !grid) return;
        if (v === 'grid') {
            lista.classList.add('d-none'); grid.classList.remove('d-none');
            btnL.classList.remove('active'); btnG.classList.add('active');
        } else {
            grid.classList.add('d-none'); lista.classList.remove('d-none');
            btnG.classList.remove('active'); btnL.classList.add('active');
        }
    }
    document.addEventListener('DOMContentLoaded', function() { aplicarVistaWorker(vistaWorker); aplicarOrdenWorker('nombre', false); });
    document.getElementById('btnVistaListaWorker')?.addEventListener('click', function(){ aplicarVistaWorker('lista'); });
    document.getElementById('btnVistaGridWorker')?.addEventListener('click',  function(){ aplicarVistaWorker('grid'); });
})();

// ── Ordenar ───────────────────────────────────────────────────────────────
const sortDirWorker = { nombre:'asc', tipo:'asc', fecha:'desc' };
let   sortActWorker = 'nombre';

document.querySelectorAll('.sort-btn-worker').forEach(btn => {
    btn.addEventListener('click', function(){
        const campo = this.dataset.sort;
        if (sortActWorker === campo) sortDirWorker[campo] = sortDirWorker[campo]==='asc'?'desc':'asc';
        else sortActWorker = campo;
        document.querySelectorAll('.sort-btn-worker').forEach(b=>b.classList.remove('active-sort'));
        this.classList.add('active-sort');
        aplicarOrdenWorker(campo, true);
    });
});

function aplicarOrdenWorker(campo, conIcono) {
    const dir = sortDirWorker[campo];
    const tbody = document.querySelector('#tablaArchivosWorker tbody');
    if (tbody) {
        const filas = Array.from(tbody.querySelectorAll('tr'));
        filas.sort((a,b) => compWorker(a.dataset[campo], b.dataset[campo], dir));
        filas.forEach(f => tbody.appendChild(f));
    }
    const grid = document.getElementById('gridArchivosWorker');
    if (grid) {
        const items = Array.from(grid.querySelectorAll('.grid-item-worker'));
        items.sort((a,b) => compWorker(a.dataset[campo], b.dataset[campo], dir));
        items.forEach(i => grid.appendChild(i));
    }
}

function compWorker(a, b, dir) {
    a = (a||'').toLowerCase(); b = (b||'').toLowerCase();
    const r = a < b ? -1 : a > b ? 1 : 0;
    return dir==='asc' ? r : -r;
}

// ── Búsqueda ──────────────────────────────────────────────────────────────
document.getElementById('buscarArchivoWorker')?.addEventListener('input', function(){
    const term = this.value.toLowerCase();
    let vis = 0;
    document.querySelectorAll('#tablaArchivosWorker tbody tr').forEach(f => {
        const m = f.textContent.toLowerCase().includes(term);
        f.style.display = m ? '' : 'none';
        if (m) vis++;
    });
    document.querySelectorAll('#gridArchivosWorker .grid-item-worker').forEach(i => {
        i.style.display = i.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
    const c = document.getElementById('contadorArchivosWorker');
    if (c) c.textContent = term ? vis : <?= count($archivos) ?>;
});

function abrirPreviewWorker(id, nombre, tipo) {
    const url   = APP_URL_WORKER + '/?page=files&action=preview&id=' + id;
    const dlUrl = APP_URL_WORKER + '/?page=worker/clients&action=download&id=' + id;
    const body  = document.getElementById('previewWorkerBody');
    const title = document.getElementById('previewWorkerNombre');
    const dl    = document.getElementById('previewWorkerDescargar');

    title.textContent = nombre;
    dl.href = dlUrl;
    body.innerHTML = '<div class="p-4 text-muted"><i class="bi bi-hourglass-split me-2"></i>Cargando...</div>';

    switch (tipo) {
        case 'image':
            body.innerHTML = `<img src="${url}" alt="${nombre}"
                style="max-width:100%;max-height:78vh;object-fit:contain;display:block;margin:auto;">`;
            break;
        case 'video':
            body.innerHTML = `<video controls autoplay style="max-width:100%;max-height:78vh;display:block;margin:auto;">
                <source src="${url}">Tu navegador no soporta este formato.</video>`;
            break;
        case 'audio':
            body.innerHTML = `<div class="p-5 w-100">
                <i class="bi bi-music-note-beamed text-primary" style="font-size:4rem;display:block;text-align:center;margin-bottom:1.5rem;"></i>
                <audio controls autoplay style="width:100%;max-width:500px;display:block;margin:auto;">
                    <source src="${url}">Tu navegador no soporta este formato.</audio></div>`;
            break;
        case 'pdf':
            body.innerHTML = `<iframe src="${url}" style="width:100%;height:78vh;border:none;display:block;"></iframe>`;
            break;
        case 'text':
            fetch(url)
                .then(r => { if (!r.ok) throw new Error(); return r.text(); })
                .then(texto => {
                    const pre = document.createElement('pre');
                    pre.textContent = texto;
                    pre.style.cssText = 'text-align:left;padding:1.5rem;margin:0;width:100%;max-height:78vh;' +
                        'overflow:auto;font-size:.83rem;background:var(--surface-1,#141414);' +
                        'color:var(--text-primary,#e0e0e0);white-space:pre-wrap;word-break:break-word;';
                    body.innerHTML = '';
                    body.appendChild(pre);
                })
                .catch(() => {
                    body.innerHTML = '<div class="p-4 text-muted"><i class="bi bi-exclamation-circle me-2 text-warning"></i>No se pudo cargar la vista previa.</div>';
                });
            break;
    }

    new bootstrap.Modal(document.getElementById('modalPreviewWorker')).show();
}

document.getElementById('modalPreviewWorker')?.addEventListener('hidden.bs.modal', function () {
    document.getElementById('previewWorkerBody').innerHTML = '';
});

<?php if ($puedeEditar): ?>
function abrirModalEditar(id, nombre, descripcion) {
    document.getElementById('formEditar').action =
        APP_URL_WORKER + '/?page=worker/clients&action=edit&id=' + id;
    document.getElementById('editar_nombre').value      = nombre;
    document.getElementById('editar_descripcion').value = descripcion;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
<?php endif; ?>

<?php if ($puedeEliminar): ?>
function abrirModalEliminar(id, nombre) {
    document.getElementById('formEliminar').action =
        APP_URL_WORKER + '/?page=worker/clients&action=delete&id=' + id;
    document.getElementById('eliminar_nombre').textContent = '"' + nombre + '"';
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
<?php endif; ?>
</script>
