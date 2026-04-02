<?php
/**
 * Vista: Archivos por Cliente (Admin)
 *
 * @var array $clientes  Lista de clientes con estadísticas
 */
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="<?= APP_URL ?>/dashboard" style="color:var(--primary);">
                <i class="bi bi-speedometer2 me-1"></i>Panel
            </a>
        </li>
        <li class="breadcrumb-item active">Archivos por Cliente</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-people me-2 text-primary"></i>Archivos por Cliente</h2>
        <p class="text-muted mb-0">
            <?= count($clientes) ?> cliente<?= count($clientes) != 1 ? 's' : '' ?> registrados
        </p>
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

<!-- Barra de controles -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted small me-1">
                <i class="bi bi-people me-1"></i>
                <span id="contadorClientes"><?= count($clientes) ?></span>
                cliente<?= count($clientes) != 1 ? 's' : '' ?>
            </span>
            <div class="d-flex align-items-center gap-1 ms-2">
                <span class="text-muted small">Ordenar:</span>
                <button class="btn btn-sm btn-outline-secondary sort-btn-clientes active-sort-clientes" data-dir="asc">
                    <i class="bi bi-sort-alpha-down me-1"></i>A–Z
                </button>
                <button class="btn btn-sm btn-outline-secondary sort-btn-clientes" data-dir="desc">
                    <i class="bi bi-sort-alpha-up me-1"></i>Z–A
                </button>
            </div>
            <div class="flex-grow-1"></div>
            <div class="input-group input-group-sm" style="max-width:220px;">
                <span class="input-group-text"><i class="bi bi-search" style="font-size:.8rem;"></i></span>
                <input type="text" id="buscarCliente" class="form-control" placeholder="Buscar cliente..." style="font-size:.83rem;">
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaClientes">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Cliente</th>
                        <th>Estado</th>
                        <th>Carpetas / Archivos</th>
                        <th>Almacenamiento</th>
                        <th>Ultimo acceso</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                    <tr data-nombre="<?= strtolower(sanitize($c['nombre'])) ?>"
                        data-email="<?= strtolower(sanitize($c['email'])) ?>">
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
                        <td>
                            <?php
                            $pct = $c['almacenamiento_maximo'] > 0
                                ? round(($c['almacenamiento_usado'] / $c['almacenamiento_maximo']) * 100)
                                : 0;
                            ?>
                            <div class="d-flex align-items-center gap-2" style="min-width:130px;">
                                <div class="progress flex-grow-1" style="height:5px;">
                                    <div class="progress-bar <?= $pct > 80 ? 'bg-danger' : ($pct > 60 ? 'bg-warning' : '') ?>"
                                         style="width:<?= $pct ?>%"></div>
                                </div>
                                <span class="small text-muted text-nowrap"><?= $pct ?>%</span>
                            </div>
                            <div class="text-muted" style="font-size:.7rem;">
                                <?= formatFileSize($c['almacenamiento_usado']) ?> /
                                <?= formatFileSize($c['almacenamiento_maximo']) ?>
                            </div>
                        </td>
                        <td class="small text-muted">
                            <?= $c['ultimo_acceso'] ? formatDate($c['ultimo_acceso'], 'd/m/Y H:i') : 'Nunca' ?>
                        </td>
                        <td class="text-end pe-3">
                            <a href="<?= APP_URL ?>/?page=admin/clients&action=view&id=<?= $c['id'] ?>"
                               class="btn btn-sm btn-outline-primary"
                               title="Ver archivos">
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

<script>
(function () {
    var sortDir = 'asc';

    document.querySelectorAll('.sort-btn-clientes').forEach(function (btn) {
        btn.addEventListener('click', function () {
            sortDir = this.dataset.dir;
            document.querySelectorAll('.sort-btn-clientes').forEach(function (b) { b.classList.remove('active-sort-clientes'); });
            this.classList.add('active-sort-clientes');
            ordenar();
        });
    });

    function ordenar() {
        var tbody = document.querySelector('#tablaClientes tbody');
        var filas = Array.from(tbody.querySelectorAll('tr'));
        filas.sort(function (a, b) {
            var na = a.dataset.nombre, nb = b.dataset.nombre;
            return sortDir === 'asc' ? na.localeCompare(nb) : nb.localeCompare(na);
        });
        filas.forEach(function (f) { tbody.appendChild(f); });
    }

    document.getElementById('buscarCliente').addEventListener('input', function () {
        var term = this.value.toLowerCase();
        var vis  = 0;
        document.querySelectorAll('#tablaClientes tbody tr').forEach(function (f) {
            var match = f.dataset.nombre.includes(term) || f.dataset.email.includes(term);
            f.style.display = match ? '' : 'none';
            if (match) vis++;
        });
        document.getElementById('contadorClientes').textContent = term ? vis : <?= count($clientes) ?>;
    });
})();
</script>
<?php endif; ?>
