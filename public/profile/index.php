<?php
/**
 * Vista: Perfil de Usuario
 *
 * @var array $usuario   Datos del usuario
 * @var array $actividad Últimas actividades
 */
$storagePercent = $usuario['almacenamiento_maximo'] > 0
    ? round(($usuario['almacenamiento_usado'] / $usuario['almacenamiento_maximo']) * 100)
    : 0;
?>

<div class="mb-4">
    <h2 class="mb-1"><i class="bi bi-person-circle me-2 text-primary"></i>Mi Perfil</h2>
    <p class="text-muted mb-0">Gestiona tu informacion y contrasena</p>
</div>

<div class="row g-4">
    <!-- Columna izquierda -->
    <div class="col-lg-4">
        <!-- Tarjeta de resumen -->
        <div class="card mb-4">
            <div class="card-body text-center py-4">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:72px;height:72px;background:var(--primary);">
                    <span style="color:#0d0d0d;font-weight:700;font-size:1.5rem;">
                        <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                    </span>
                </div>
                <h5 class="mb-1"><?= sanitize($usuario['nombre']) ?></h5>
                <p class="text-muted small mb-2"><?= sanitize($usuario['email']) ?></p>
                <span class="badge <?= $usuario['rol'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                    <?= ucfirst($usuario['rol']) ?>
                </span>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between small text-muted mb-2">
                    <span>Almacenamiento</span>
                    <span>
                        <?= formatFileSize($usuario['almacenamiento_usado']) ?> /
                        <?= formatFileSize($usuario['almacenamiento_maximo']) ?>
                    </span>
                </div>
                <div class="progress" style="height:7px;">
                    <div class="progress-bar <?= $storagePercent > 80 ? 'bg-danger' : ($storagePercent > 60 ? 'bg-warning' : '') ?>"
                         style="width:<?= $storagePercent ?>%"></div>
                </div>
                <div class="small text-muted mt-2">
                    <i class="bi bi-clock me-1"></i>
                    Miembro desde <?= formatDate($usuario['fecha_creacion'], 'd/m/Y') ?>
                </div>
            </div>
        </div>

        <!-- Actividad reciente -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-activity me-2"></i>Actividad Reciente
            </div>
            <div class="card-body p-0">
                <?php if (empty($actividad)): ?>
                <p class="text-muted text-center py-3 small mb-0">Sin actividad registrada.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($actividad as $log): ?>
                    <li class="list-group-item px-3 py-2">
                        <div class="small" style="color:var(--text-secondary);">
                            <?= sanitize($log['descripcion'] ?? $log['accion']) ?>
                        </div>
                        <div class="text-muted" style="font-size:.7rem;">
                            <?= formatRelativeDate($log['fecha']) ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Columna derecha -->
    <div class="col-lg-8">
        <!-- Datos Personales -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-person me-2"></i>Datos Personales
            </div>
            <div class="card-body">
                <?php if (isAdmin()): ?>
                <!-- Admin: puede editar nombre y email -->
                <form method="POST" action="<?= APP_URL ?>/?page=profile">
                    <?= csrfField() ?>
                    <input type="hidden" name="accion" value="actualizar_perfil">
                    <div class="mb-3">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" name="nombre"
                               value="<?= sanitize($usuario['nombre']) ?>" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo electronico</label>
                        <input type="email" class="form-control" name="email"
                               value="<?= sanitize($usuario['email']) ?>" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color:var(--text-dim);">Ultimo acceso</label>
                        <input type="text" class="form-control"
                               value="<?= formatDate($usuario['ultimo_acceso']) ?>" disabled>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-2"></i>Guardar Cambios
                    </button>
                </form>
                <?php else: ?>
                <!-- Cliente / Trabajador: solo lectura -->
                <div class="mb-3">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" class="form-control" value="<?= sanitize($usuario['nombre']) ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Correo electronico</label>
                    <input type="email" class="form-control" value="<?= sanitize($usuario['email']) ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color:var(--text-dim);">Ultimo acceso</label>
                    <input type="text" class="form-control"
                           value="<?= formatDate($usuario['ultimo_acceso']) ?>" disabled>
                </div>
                <div class="alert alert-info py-2 small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Para cambiar tu nombre o correo, contacta al administrador.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulario cambio de contraseña -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lock me-2"></i>Cambiar Contrasena
            </div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/?page=profile">
                    <?= csrfField() ?>
                    <input type="hidden" name="accion" value="cambiar_password">

                    <div class="mb-3">
                        <label class="form-label">Contrasena actual</label>
                        <input type="password" class="form-control" name="password_actual" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nueva contrasena</label>
                        <input type="password" class="form-control" name="password_nueva"
                               id="nuevaPass" required>
                        <div class="form-text">
                            Minimo 8 caracteres, una mayuscula, un numero y un simbolo.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmar nueva contrasena</label>
                        <input type="password" class="form-control" name="password_confirmar"
                               id="confirmarPass" required>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key me-2"></i>Cambiar Contrasena
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('confirmarPass')?.addEventListener('input', function () {
    const match = this.value === document.getElementById('nuevaPass').value;
    this.classList.toggle('is-invalid', !match);
    this.classList.toggle('is-valid',   match && this.value.length > 0);
});
</script>
