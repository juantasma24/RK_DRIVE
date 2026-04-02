<?php
/**
 * Vista: Detalle de Carpeta (archivos que contiene)
 *
 * @var array $carpeta   Datos de la carpeta
 * @var array $archivos  Archivos activos dentro de la carpeta
 */

$previewBase = APP_URL . '/?page=files&action=preview&id=';

// Clasifica el tipo de previsualización por extensión
function previewType(string $ext): string {
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
            <a href="<?= APP_URL ?>/?page=folders" style="color:var(--color-primary);">
                <i class="bi bi-files me-1"></i>Mis Archivos
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

<!-- Barra de controles -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center gap-2">

            <!-- Contador con mes y año -->
            <span class="text-muted small me-1">
                <i class="bi bi-files me-1"></i>
                <span id="contadorArchivos"><?= count($archivos) ?></span>
                archivo<?= count($archivos) != 1 ? 's' : '' ?> - <?= date('F Y') ?>
            </span>

            <!-- Ordenar -->
            <div class="d-flex align-items-center gap-1 ms-2">
                <span class="text-muted small">Ordenar:</span>
                <button class="btn btn-sm btn-outline-secondary sort-btn active-sort" data-sort="nombre">
                    <i class="bi bi-sort-alpha-down me-1"></i>Nombre
                </button>
                <button class="btn btn-sm btn-outline-secondary sort-btn" data-sort="tipo">
                    <i class="bi bi-funnel me-1"></i>Tipo
                </button>
                <button class="btn btn-sm btn-outline-secondary sort-btn" data-sort="fecha">
                    <i class="bi bi-calendar me-1"></i>Fecha
                </button>
            </div>

            <!-- Spacer -->
            <div class="flex-grow-1"></div>

            <!-- Buscar -->
            <div class="input-group input-group-sm" style="max-width:200px;">
                <span class="input-group-text"><i class="bi bi-search" style="font-size:.8rem;"></i></span>
                <input type="text" id="buscarArchivo" class="form-control"
                       placeholder="Buscar..." style="font-size:.83rem;">
            </div>

            <!-- Toggle vista -->
            <div class="btn-group btn-group-sm" role="group" aria-label="Vista">
                <button id="btnVistLista" class="btn btn-outline-secondary active" title="Vista lista">
                    <i class="bi bi-list-ul"></i>
                </button>
                <button id="btnVistaGrid" class="btn btn-outline-secondary" title="Vista cuadricula">
                    <i class="bi bi-grid-3x3-gap"></i>
                </button>
            </div>

        </div>
    </div>
</div>

<!-- =====================================================================
     VISTA LISTA
====================================================================== -->
<div id="vistaLista" class="card">
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
                    <?php foreach ($archivos as $a):
                        $pt = previewType($a['extension']);
                    ?>
                    <tr data-nombre="<?= strtolower(sanitize($a['nombre_original'])) ?>"
                        data-tipo="<?= strtolower(sanitize($a['extension'])) ?>"
                        data-fecha="<?= $a['fecha_subida'] ?>">
                        <td class="ps-3">
                            <i class="bi <?= getFileIcon($a['extension']) ?> me-2 text-muted fs-5"></i>
                            <span class="fw-semibold small" title="<?= sanitize($a['nombre_original']) ?>">
                                <?= sanitize(truncateText($a['nombre_original'], 40)) ?>
                            </span>
                            <?php if (!empty($a['descripcion'])): ?>
                            <br><small class="text-muted ms-4">
                                <?= sanitize(truncateText($a['descripcion'], 60)) ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= getFileColor($a['extension']) ?>-subtle text-<?= getFileColor($a['extension']) ?> border border-<?= getFileColor($a['extension']) ?>-subtle">
                                <?= strtoupper(sanitize($a['extension'])) ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= formatFileSize($a['tamano_bytes']) ?></td>
                        <td class="small text-muted"><?= formatDate($a['fecha_subida']) ?></td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <?php if ($pt !== 'none'): ?>
                                <button class="btn btn-outline-secondary"
                                        onclick="abrirPreview(<?= $a['id'] ?>, <?= htmlspecialchars(json_encode($a['nombre_original']), ENT_QUOTES, 'UTF-8') ?>, '<?= $pt ?>')"
                                        title="Vista previa">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php endif; ?>
                                <a href="<?= APP_URL ?>/?page=files&action=download&id=<?= $a['id'] ?>"
                                   class="btn btn-outline-primary" title="Descargar">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button class="btn btn-outline-secondary"
                                        onclick="abrirModalEditarArchivo(<?= $a['id'] ?>, '<?= addslashes(sanitize($a['nombre_original'])) ?>', '<?= addslashes(sanitize($a['descripcion'] ?? '')) ?>')"
                                        title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                        onclick="confirmarMoverPapelera(<?= $a['id'] ?>, <?= htmlspecialchars(json_encode($a['nombre_original']), ENT_QUOTES, 'UTF-8') ?>)"
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

<!-- =====================================================================
     VISTA CUADRÍCULA
====================================================================== -->
<div id="vistaGrid" class="d-none">
    <div class="row g-3" id="gridArchivos">
        <?php foreach ($archivos as $a):
            $pt        = previewType($a['extension']);
            $esImagen  = ($pt === 'image');
        ?>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2 grid-item"
             data-nombre="<?= strtolower(sanitize($a['nombre_original'])) ?>"
             data-tipo="<?= strtolower(sanitize($a['extension'])) ?>"
             data-fecha="<?= $a['fecha_subida'] ?>">
            <div class="card h-100 file-card">

                <!-- Thumbnail / Icono -->
                <div class="file-card-thumb d-flex align-items-center justify-content-center"
                     style="height:120px;overflow:hidden;border-radius:.5rem .5rem 0 0;
                            background:var(--surface-2,#1e1e1e);cursor:<?= $pt !== 'none' ? 'pointer' : 'default' ?>;"
                     <?php if ($pt !== 'none'): ?>
                     onclick="abrirPreview(<?= $a['id'] ?>, <?= htmlspecialchars(json_encode($a['nombre_original']), ENT_QUOTES, 'UTF-8') ?>, '<?= $pt ?>')"
                     title="Vista previa"
                     <?php endif; ?>>
                    <?php if ($esImagen): ?>
                        <img src="<?= $previewBase . $a['id'] ?>"
                             alt="<?= sanitize($a['nombre_original']) ?>"
                             style="width:100%;height:120px;object-fit:cover;"
                             loading="lazy"
                             onerror="this.parentElement.innerHTML='<i class=\'bi <?= getFileIcon($a['extension']) ?> text-muted\' style=\'font-size:3rem;\'></i>'">
                    <?php else: ?>
                        <i class="bi <?= getFileIcon($a['extension']) ?> text-muted"
                           style="font-size:3rem;"></i>
                        <?php if ($pt !== 'none'): ?>
                        <div class="position-absolute" style="top:8px;right:8px;">
                            <span class="badge bg-dark bg-opacity-75">
                                <i class="bi bi-eye-fill" style="font-size:.65rem;"></i>
                            </span>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="card-body p-2">
                    <p class="mb-1 small fw-semibold text-truncate"
                       title="<?= sanitize($a['nombre_original']) ?>"
                       style="color:var(--text-primary);font-size:.78rem;">
                        <?= sanitize(truncateText($a['nombre_original'], 28)) ?>
                    </p>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="badge bg-<?= getFileColor($a['extension']) ?>-subtle text-<?= getFileColor($a['extension']) ?>"
                              style="font-size:.65rem;">
                            <?= strtoupper(sanitize($a['extension'])) ?>
                        </span>
                        <span class="text-muted" style="font-size:.68rem;">
                            <?= formatFileSize($a['tamano_bytes']) ?>
                        </span>
                    </div>
                </div>

                <div class="card-footer p-1 d-flex gap-1 justify-content-end">
                    <?php if ($pt !== 'none'): ?>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                            onclick="abrirPreview(<?= $a['id'] ?>, <?= htmlspecialchars(json_encode($a['nombre_original']), ENT_QUOTES, 'UTF-8') ?>, '<?= $pt ?>')"
                            title="Vista previa">
                        <i class="bi bi-eye" style="font-size:.75rem;"></i>
                    </button>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>/?page=files&action=download&id=<?= $a['id'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2" title="Descargar">
                        <i class="bi bi-download" style="font-size:.75rem;"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                            onclick="abrirModalEditarArchivo(<?= $a['id'] ?>, '<?= addslashes(sanitize($a['nombre_original'])) ?>', '<?= addslashes(sanitize($a['descripcion'] ?? '')) ?>')"
                            title="Editar">
                        <i class="bi bi-pencil" style="font-size:.75rem;"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger py-0 px-2"
                            onclick="confirmarMoverPapelera(<?= $a['id'] ?>, <?= htmlspecialchars(json_encode($a['nombre_original']), ENT_QUOTES, 'UTF-8') ?>)"
                            title="Mover a papelera">
                        <i class="bi bi-trash" style="font-size:.75rem;"></i>
                    </button>
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
<div class="modal fade" id="modalPreview" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title text-truncate me-3" id="previewNombre" style="max-width:70%;"></h6>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <a id="previewDescargar" href="#" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i>Descargar
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0 text-center" id="previewBody"
                 style="min-height:300px;max-height:80vh;overflow:auto;
                        background:var(--surface-1,#141414);display:flex;
                        align-items:center;justify-content:center;">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>
</div>


<!-- =====================================================================
     MODAL: EDITAR ARCHIVO
====================================================================== -->
<div class="modal fade" id="modalEditarArchivo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEditarArchivo" action="">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i>Editar Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editar_nombre_archivo" class="form-label">
                            Nombre <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="editar_nombre_archivo" name="nombre"
                               maxlength="255" required>
                    </div>
                    <div class="mb-0">
                        <label for="editar_desc_archivo" class="form-label">
                            Descripcion <span class="text-muted small fw-normal">(opcional)</span>
                        </label>
                        <textarea class="form-control" id="editar_desc_archivo" name="descripcion"
                                  rows="2" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


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
                    <h5 class="modal-title"><i class="bi bi-cloud-upload me-2"></i>Subir Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Archivo <span class="text-danger">*</span></label>
                        <div id="dropZone" style="border:2px dashed var(--color-primary,#5ea84a);border-radius:.5rem;padding:1.5rem 1rem;text-align:center;cursor:pointer;transition:background .2s,border-color .2s;">
                            <i class="bi bi-cloud-upload" style="font-size:2rem;color:var(--color-primary,#5ea84a);"></i>
                            <p class="mb-1 mt-1 small fw-semibold" style="color:var(--text-primary);">
                                Arrastra tu archivo aqui o <span style="color:var(--color-primary,#5ea84a);text-decoration:underline;cursor:pointer;">seleccionalo</span>
                            </p>
                            <p class="mb-2 small text-muted">Tamano maximo: <?= formatFileSize(MAX_FILE_SIZE) ?></p>
                            <div id="dropNombre" class="d-none">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                    <i class="bi bi-file-earmark-check me-1"></i>
                                    <span id="dropNombreTexto"></span>
                                </span>
                            </div>
                        </div>
                        <input type="file" class="d-none" id="archivo_file" name="archivo" required>
                    </div>
                    <div class="mb-3">
                        <label for="archivo_desc" class="form-label">
                            Descripcion <span class="text-muted small fw-normal">(opcional)</span>
                        </label>
                        <input type="text" class="form-control" id="archivo_desc" name="descripcion"
                               maxlength="255" placeholder="Descripcion breve del archivo">
                    </div>
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
                        <i class="bi bi-cloud-upload me-1"></i>Subir
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
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-trash me-2"></i>Mover a Papelera
                    </h5>
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
                        <i class="bi bi-trash me-1"></i>Mover a Papelera
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
const APP_URL   = '<?= APP_URL ?>';
let sortDir     = { nombre: 'asc', tipo: 'asc', fecha: 'desc' };
let sortActual  = 'nombre';
let vistaActual = localStorage.getItem('rk-view-files') || 'lista';

// ── Inicializar vista ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    aplicarVista(vistaActual);
    aplicarOrden(sortActual, false);
});

// ── Toggle lista / grid ───────────────────────────────────────────────────
document.getElementById('btnVistLista')?.addEventListener('click', function () {
    aplicarVista('lista');
});
document.getElementById('btnVistaGrid')?.addEventListener('click', function () {
    aplicarVista('grid');
});

function aplicarVista(vista) {
    vistaActual = vista;
    localStorage.setItem('rk-view-files', vista);
    const lista = document.getElementById('vistaLista');
    const grid  = document.getElementById('vistaGrid');
    const btnL  = document.getElementById('btnVistLista');
    const btnG  = document.getElementById('btnVistaGrid');
    if (!lista || !grid) return;

    if (vista === 'grid') {
        lista.classList.add('d-none');
        grid.classList.remove('d-none');
        btnL?.classList.remove('active');
        btnG?.classList.add('active');
    } else {
        grid.classList.add('d-none');
        lista.classList.remove('d-none');
        btnG?.classList.remove('active');
        btnL?.classList.add('active');
    }
}

// ── Ordenar ───────────────────────────────────────────────────────────────
document.querySelectorAll('.sort-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const campo = this.dataset.sort;
        if (sortActual === campo) {
            sortDir[campo] = sortDir[campo] === 'asc' ? 'desc' : 'asc';
        } else {
            sortActual = campo;
        }
        document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active-sort'));
        this.classList.add('active-sort');
        aplicarOrden(campo, true);
    });
});

function aplicarOrden(campo, conIcono) {
    const dir = sortDir[campo];

    // Ordenar filas de la tabla
    const tbody = document.querySelector('#tablaArchivos tbody');
    if (tbody) {
        const filas = Array.from(tbody.querySelectorAll('tr'));
        filas.sort((a, b) => comparar(a.dataset[campo], b.dataset[campo], dir));
        filas.forEach(f => tbody.appendChild(f));
    }

    // Ordenar cards del grid
    const grid = document.getElementById('gridArchivos');
    if (grid) {
        const items = Array.from(grid.querySelectorAll('.grid-item'));
        items.sort((a, b) => comparar(a.dataset[campo], b.dataset[campo], dir));
        items.forEach(i => grid.appendChild(i));
    }

    // Actualizar icono del botón activo
    if (conIcono) {
        document.querySelectorAll('.sort-btn').forEach(btn => {
            const icons = { asc: 'bi-sort-alpha-down', desc: 'bi-sort-alpha-up' };
            if (btn.dataset.sort === campo) {
                const i = btn.querySelector('i');
                if (i) i.className = 'bi ' + (dir === 'asc' ? icons.asc : icons.desc) + ' me-1';
            }
        });
    }
}

function comparar(a, b, dir) {
    a = (a || '').toLowerCase();
    b = (b || '').toLowerCase();
    const result = a < b ? -1 : a > b ? 1 : 0;
    return dir === 'asc' ? result : -result;
}

// ── Búsqueda ──────────────────────────────────────────────────────────────
document.getElementById('buscarArchivo')?.addEventListener('input', function () {
    const term    = this.value.toLowerCase();
    let visibles  = 0;

    // Tabla
    document.querySelectorAll('#tablaArchivos tbody tr').forEach(fila => {
        const match = fila.textContent.toLowerCase().includes(term);
        fila.style.display = match ? '' : 'none';
        if (match) visibles++;
    });

    // Grid
    document.querySelectorAll('#gridArchivos .grid-item').forEach(item => {
        const match = item.textContent.toLowerCase().includes(term);
        item.style.display = match ? '' : 'none';
    });

    const contador = document.getElementById('contadorArchivos');
    if (contador) contador.textContent = term ? visibles : <?= count($archivos) ?>;
});

// ── Vista previa ──────────────────────────────────────────────────────────
function abrirPreview(id, nombre, tipo) {
    const url  = APP_URL + '/?page=files&action=preview&id=' + id;
    const dlUrl = APP_URL + '/?page=files&action=download&id=' + id;
    const body = document.getElementById('previewBody');
    const title = document.getElementById('previewNombre');
    const dl   = document.getElementById('previewDescargar');

    title.textContent = nombre;
    dl.href = dlUrl;
    body.innerHTML = '<div class="p-4 text-muted"><i class="bi bi-hourglass-split me-2"></i>Cargando...</div>';

    let contenido = '';

    switch (tipo) {
        case 'image':
            contenido = `<img src="${url}" alt="${nombre}"
                              style="max-width:100%;max-height:78vh;object-fit:contain;display:block;margin:auto;"
                              onerror="this.replaceWith(errorPreview())">`;
            body.innerHTML = contenido;
            break;

        case 'video':
            contenido = `<video controls autoplay
                                style="max-width:100%;max-height:78vh;display:block;margin:auto;">
                            <source src="${url}">
                            Tu navegador no soporta este formato de video.
                         </video>`;
            body.innerHTML = contenido;
            break;

        case 'audio':
            contenido = `<div class="p-5 w-100">
                            <i class="bi bi-music-note-beamed text-primary" style="font-size:4rem;display:block;text-align:center;margin-bottom:1.5rem;"></i>
                            <audio controls autoplay style="width:100%;max-width:500px;display:block;margin:auto;">
                                <source src="${url}">
                                Tu navegador no soporta este formato de audio.
                            </audio>
                         </div>`;
            body.innerHTML = contenido;
            break;

        case 'pdf':
            contenido = `<iframe src="${url}" style="width:100%;height:78vh;border:none;display:block;"></iframe>`;
            body.innerHTML = contenido;
            break;

        case 'text':
            fetch(url)
                .then(r => {
                    if (!r.ok) throw new Error('Error ' + r.status);
                    return r.text();
                })
                .then(texto => {
                    const pre = document.createElement('pre');
                    pre.textContent = texto;
                    pre.style.cssText = 'text-align:left;padding:1.5rem;margin:0;width:100%;' +
                                        'max-height:78vh;overflow:auto;font-size:.83rem;' +
                                        'background:var(--surface-1,#141414);color:var(--text-primary,#e0e0e0);' +
                                        'white-space:pre-wrap;word-break:break-word;';
                    body.innerHTML = '';
                    body.appendChild(pre);
                })
                .catch(() => { body.innerHTML = errorPreview(); });
            break;
    }

    new bootstrap.Modal(document.getElementById('modalPreview')).show();
}

function errorPreview() {
    const div = document.createElement('div');
    div.className = 'p-4 text-muted';
    div.innerHTML = '<i class="bi bi-exclamation-circle me-2 text-warning"></i>No se pudo cargar la vista previa.';
    return div;
}

// ── Editar archivo ──────────────────────────────────────────────────────
function abrirModalEditarArchivo(id, nombre, descripcion) {
    document.getElementById('formEditarArchivo').action =
        APP_URL + '/?page=files&action=edit&id=' + id;
    document.getElementById('editar_nombre_archivo').value = nombre;
    document.getElementById('editar_desc_archivo').value = descripcion || '';
    new bootstrap.Modal(document.getElementById('modalEditarArchivo')).show();
}

// Limpiar recursos al cerrar el modal
document.getElementById('modalPreview')?.addEventListener('hidden.bs.modal', function () {
    document.getElementById('previewBody').innerHTML = '';
});

// ── Mover a papelera ──────────────────────────────────────────────────────
function confirmarMoverPapelera(id, nombre) {
    document.getElementById('formPapelera').action =
        APP_URL + '/?page=files&action=trash&id=' + id;
    document.getElementById('papelera_nombre').textContent = '"' + nombre + '"';
    new bootstrap.Modal(document.getElementById('modalPapelera')).show();
}

// ── Drag & drop + progreso de subida ─────────────────────────────────────
(function () {
    const dropZone  = document.getElementById('dropZone');
    const fileInput = document.getElementById('archivo_file');
    const dropLabel = document.getElementById('dropNombre');
    const dropTexto = document.getElementById('dropNombreTexto');
    const btnSubir  = document.getElementById('btnSubir');
    if (!dropZone || !fileInput) return;
    function mostrarArchivo(nombre) {
        dropTexto.textContent = nombre;
        dropLabel.classList.remove('d-none');
        if (btnSubir) btnSubir.disabled = false;
    }
    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', function () {
        if (this.files.length) mostrarArchivo(this.files[0].name);
    });
    ['dragenter','dragover'].forEach(ev => {
        dropZone.addEventListener(ev, e => {
            e.preventDefault();
            dropZone.style.background = 'var(--color-primary-subtle, rgba(94,168,74,.12))';
            dropZone.style.borderStyle = 'solid';
        });
    });
    dropZone.addEventListener('dragleave', e => {
        if (!dropZone.contains(e.relatedTarget)) {
            dropZone.style.background = '';
            dropZone.style.borderStyle = 'dashed';
        }
    });
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.style.background = '';
        dropZone.style.borderStyle = 'dashed';
        const files = e.dataTransfer.files;
        if (!files.length) return;
        const dt = new DataTransfer();
        dt.items.add(files[0]);
        fileInput.files = dt.files;
        mostrarArchivo(files[0].name);
    });
    document.getElementById('modalSubir')?.addEventListener('hidden.bs.modal', () => {
        fileInput.value = '';
        dropLabel.classList.add('d-none');
        dropTexto.textContent = '';
        dropZone.style.background = '';
        dropZone.style.borderStyle = 'dashed';
    });
    document.getElementById('formSubir')?.addEventListener('submit', function () {
        document.getElementById('uploadProgress').classList.remove('d-none');
        if (btnSubir) btnSubir.disabled = true;
    });
})();
</script>

<style>
.active-sort {
    background: var(--color-primary, #5ea84a) !important;
    color: #0d0d0d !important;
    border-color: var(--color-primary, #5ea84a) !important;
}
.file-card {
    transition: transform .18s ease, box-shadow .18s ease;
}
.file-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,.35);
}
</style>
