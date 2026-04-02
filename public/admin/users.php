<?php
/**
 * Vista: Gestión de Usuarios (Admin)
 *
 * @var array $usuarios  Lista de usuarios con estadísticas
 */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-people me-2 text-primary"></i>Gestion de Usuarios</h2>
        <p class="text-muted mb-0">
            <?= count($usuarios) ?> usuario<?= count($usuarios) != 1 ? 's' : '' ?> registrados
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/?page=admin/users&action=export" class="btn btn-outline-success">
            <i class="bi bi-download me-2"></i>Exportar CSV
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
            <i class="bi bi-person-plus me-2"></i>Nuevo Usuario
        </button>
    </div>
</div>

<?php if (empty($usuarios)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-people text-muted" style="font-size:4rem;"></i>
        <h5 class="mt-3">No hay usuarios registrados</h5>
    </div>
</div>
<?php else: ?>

<!-- Barra de controles -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted small me-1">
                <i class="bi bi-people me-1"></i>
                <span id="contadorUsuarios"><?= count($usuarios) ?></span>
                usuario<?= count($usuarios) != 1 ? 's' : '' ?>
            </span>
            <div class="d-flex align-items-center gap-1 ms-2">
                <span class="text-muted small">Ordenar:</span>
                <button class="btn btn-sm btn-outline-secondary sort-btn-usuarios active-sort" data-dir="asc">
                    <i class="bi bi-sort-alpha-down me-1"></i>A–Z
                </button>
                <button class="btn btn-sm btn-outline-secondary sort-btn-usuarios" data-dir="desc">
                    <i class="bi bi-sort-alpha-up me-1"></i>Z–A
                </button>
            </div>
            <div class="d-flex align-items-center gap-1 ms-2">
                <span class="text-muted small">Rol:</span>
                <button class="btn btn-sm btn-outline-secondary rol-btn active-rol" data-rol="">Todos</button>
                <button class="btn btn-sm btn-outline-secondary rol-btn" data-rol="admin">Admin</button>
                <button class="btn btn-sm btn-outline-secondary rol-btn" data-rol="trabajador">Trabajador</button>
                <button class="btn btn-sm btn-outline-secondary rol-btn" data-rol="cliente">Cliente</button>
            </div>
            <div class="flex-grow-1"></div>
            <div class="input-group input-group-sm" style="max-width:220px;">
                <span class="input-group-text"><i class="bi bi-search" style="font-size:.8rem;"></i></span>
                <input type="text" id="buscarUsuario" class="form-control" placeholder="Buscar usuario..." style="font-size:.83rem;">
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaUsuarios">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Carpetas / Archivos</th>
                        <th>Almacenamiento</th>
                        <th>Permisos</th>
                        <th>Ultimo acceso</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr data-nombre="<?= strtolower(sanitize($u['nombre'])) ?>"
                        data-email="<?= strtolower(sanitize($u['email'])) ?>"
                        data-rol="<?= sanitize($u['rol']) ?>">
                        <td class="ps-3">
                            <div class="fw-semibold" style="color:var(--text-primary);">
                                <?= sanitize($u['nombre']) ?>
                            </div>
                            <div class="text-muted small"><?= sanitize($u['email']) ?></div>
                        </td>
                        <td>
                            <?php if ($u['rol'] === 'admin'): ?>
                            <span class="badge bg-danger-subtle text-danger">Admin</span>
                            <?php elseif ($u['rol'] === 'trabajador'): ?>
                            <span class="badge bg-warning-subtle text-warning">Trabajador</span>
                            <?php else: ?>
                            <span class="badge bg-primary-subtle text-primary">Cliente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['activo']): ?>
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
                            <?php if ($u['rol'] === 'cliente'): ?>
                                <i class="bi bi-folder me-1"></i><?= (int)$u['total_carpetas'] ?>
                                &nbsp;/&nbsp;
                                <i class="bi bi-file-earmark me-1"></i><?= (int)$u['total_archivos'] ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $pct = $u['almacenamiento_maximo'] > 0
                                ? round(($u['almacenamiento_usado'] / $u['almacenamiento_maximo']) * 100)
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
                                <?= formatFileSize($u['almacenamiento_usado']) ?> /
                                <?= formatFileSize($u['almacenamiento_maximo']) ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($u['rol'] === 'trabajador'): ?>
                            <div class="d-flex flex-column gap-1">
                                <form method="POST"
                                      action="<?= APP_URL ?>/?page=admin/users&action=toggleEditPerm&id=<?= $u['id'] ?>">
                                    <?= csrfField() ?>
                                    <button type="submit"
                                            class="btn btn-xs <?= $u['puede_editar_archivos'] ? 'btn-warning' : 'btn-outline-secondary' ?>"
                                            style="font-size:.7rem;padding:.15rem .4rem;"
                                            title="<?= $u['puede_editar_archivos'] ? 'Revocar edición' : 'Permitir edición' ?>">
                                        <i class="bi bi-pencil me-1"></i><?= $u['puede_editar_archivos'] ? 'Editar: ON' : 'Editar: OFF' ?>
                                    </button>
                                </form>
                                <form method="POST"
                                      action="<?= APP_URL ?>/?page=admin/users&action=toggleDeletePerm&id=<?= $u['id'] ?>">
                                    <?= csrfField() ?>
                                    <button type="submit"
                                            class="btn btn-xs <?= $u['puede_eliminar_archivos'] ? 'btn-danger' : 'btn-outline-secondary' ?>"
                                            style="font-size:.7rem;padding:.15rem .4rem;"
                                            title="<?= $u['puede_eliminar_archivos'] ? 'Revocar eliminación' : 'Permitir eliminación' ?>">
                                        <i class="bi bi-trash me-1"></i><?= $u['puede_eliminar_archivos'] ? 'Eliminar: ON' : 'Eliminar: OFF' ?>
                                    </button>
                                </form>
                            </div>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?= $u['ultimo_acceso'] ? formatDate($u['ultimo_acceso'], 'd/m/Y H:i') : 'Nunca' ?>
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-flex justify-content-end gap-1">
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick="abrirModalEditar(<?= $u['id'] ?>, '<?= addslashes(sanitize($u['nombre'])) ?>', '<?= addslashes(sanitize($u['email'])) ?>', '<?= $u['rol'] ?>', <?= (int)round($u['almacenamiento_maximo'] / (1024**3)) ?>)"
                                        title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($u['id'] !== getCurrentUserId()): ?>
                                <form method="POST"
                                      action="<?= APP_URL ?>/?page=admin/users&action=toggle&id=<?= $u['id'] ?>">
                                    <?= csrfField() ?>
                                    <button type="submit"
                                            class="btn btn-sm <?= $u['activo'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                            title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>">
                                        <i class="bi <?= $u['activo'] ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                    </button>
                                </form>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="abrirModalEliminar(<?= $u['id'] ?>, '<?= addslashes(sanitize($u['nombre'])) ?>')"
                                        title="Eliminar">
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
<?php endif; ?>

<script>
(function () {
    var sortDir    = 'asc';
    var rolActivo  = '';

    function filas() {
        return Array.from(document.querySelectorAll('#tablaUsuarios tbody tr'));
    }

    function aplicarFiltros() {
        var term = document.getElementById('buscarUsuario').value.toLowerCase();
        var vis  = 0;
        filas().forEach(function (f) {
            var matchNombre = f.dataset.nombre.includes(term) || f.dataset.email.includes(term);
            var matchRol    = rolActivo === '' || f.dataset.rol === rolActivo;
            var visible     = matchNombre && matchRol;
            f.style.display = visible ? '' : 'none';
            if (visible) vis++;
        });
        document.getElementById('contadorUsuarios').textContent = (term || rolActivo) ? vis : <?= count($usuarios) ?>;
    }

    function ordenar() {
        var tbody = document.querySelector('#tablaUsuarios tbody');
        var sorted = filas().sort(function (a, b) {
            var na = a.dataset.nombre, nb = b.dataset.nombre;
            return sortDir === 'asc' ? na.localeCompare(nb) : nb.localeCompare(na);
        });
        sorted.forEach(function (f) { tbody.appendChild(f); });
    }

    document.querySelectorAll('.sort-btn-usuarios').forEach(function (btn) {
        btn.addEventListener('click', function () {
            sortDir = this.dataset.dir;
            document.querySelectorAll('.sort-btn-usuarios').forEach(function (b) { b.classList.remove('active-sort'); });
            this.classList.add('active-sort');
            ordenar();
        });
    });

    document.querySelectorAll('.rol-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            rolActivo = this.dataset.rol;
            document.querySelectorAll('.rol-btn').forEach(function (b) { b.classList.remove('active-rol'); });
            this.classList.add('active-rol');
            aplicarFiltros();
        });
    });

    document.getElementById('buscarUsuario').addEventListener('input', aplicarFiltros);
})();
</script>

<style>
.active-rol {
    background: var(--primary) !important;
    color: #0d0d0d !important;
    border-color: var(--primary) !important;
}
</style>


<!-- =====================================================================
     MODAL: CREAR USUARIO
====================================================================== -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= APP_URL ?>/?page=admin/users&action=create">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i>Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre"
                               maxlength="255" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contrasena <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Rol</label>
                            <select class="form-select" name="rol">
                                <option value="cliente">Cliente</option>
                                <option value="trabajador">Trabajador</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Almacenamiento (GB)</label>
                            <input type="number" class="form-control" name="storage_gb"
                                   value="2" min="1" max="100">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i>Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- =====================================================================
     MODAL: EDITAR USUARIO
====================================================================== -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEditar" action="">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i>Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editar_nombre" name="nombre"
                               maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="editar_email" name="email"
                               maxlength="255" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Rol</label>
                            <select class="form-select" id="editar_rol" name="rol">
                                <option value="cliente">Cliente</option>
                                <option value="trabajador">Trabajador</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Almacenamiento (GB)</label>
                            <input type="number" class="form-control" id="editar_storage"
                                   name="storage_gb" min="1" max="100">
                        </div>
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
     MODAL: ELIMINAR USUARIO
====================================================================== -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEliminar" action="">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-trash"></i>Eliminar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="color:var(--text-secondary);">
                        ¿Estas seguro de que deseas eliminar al usuario
                        <strong id="eliminar_nombre" style="color:var(--text-primary);"></strong>?
                    </p>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Esta accion eliminara tambien todos sus archivos fisicos. No se puede deshacer.
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
function abrirModalEditar(id, nombre, email, rol, storageGb) {
    document.getElementById('formEditar').action =
        '<?= APP_URL ?>/?page=admin/users&action=edit&id=' + id;
    document.getElementById('editar_nombre').value  = nombre;
    document.getElementById('editar_email').value   = email;
    document.getElementById('editar_rol').value     = rol;
    document.getElementById('editar_storage').value = storageGb;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

function abrirModalEliminar(id, nombre) {
    document.getElementById('formEliminar').action =
        '<?= APP_URL ?>/?page=admin/users&action=delete&id=' + id;
    document.getElementById('eliminar_nombre').textContent = '"' + nombre + '"';
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>
