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
    <p class="text-muted mb-0">Gestiona tu información y contraseña</p>
</div>

<div class="row g-4">
    <!-- Columna izquierda -->
    <div class="col-lg-4">
        <!-- Tarjeta de resumen -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center py-4">
                <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:80px;height:80px;">
                    <span class="text-white fw-bold fs-2">
                        <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                    </span>
                </div>
                <h5 class="mb-1"><?= sanitize($usuario['nombre']) ?></h5>
                <p class="text-muted small mb-2"><?= sanitize($usuario['email']) ?></p>
                <span class="badge bg-<?= $usuario['rol'] === 'admin' ? 'danger' : 'primary' ?>">
                    <?= ucfirst($usuario['rol']) ?>
                </span>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between small text-muted mb-2">
                    <span>Almacenamiento</span>
                    <span><?= formatFileSize($usuario['almacenamiento_usado']) ?> / <?= formatFileSize($usuario['almacenamiento_maximo']) ?></span>
                </div>
                <div class="progress" style="height:8px;">
                    <div class="progress-bar <?= $storagePercent > 80 ? 'bg-danger' : ($storagePercent > 60 ? 'bg-warning' : 'bg-success') ?>"
                         style="width:<?= $storagePercent ?>%"></div>
                </div>
                <div class="small text-muted mt-2">
                    <i class="bi bi-clock me-1"></i>
                    Miembro desde <?= formatDate($usuario['fecha_creacion'], 'd/m/Y') ?>
                </div>
            </div>
        </div>

        <!-- Actividad reciente -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h6 class="mb-0"><i class="bi bi-activity me-2"></i>Actividad Reciente</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($actividad)): ?>
                <p class="text-muted text-center py-3 small">Sin actividad registrada.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($actividad as $log): ?>
                    <li class="list-group-item px-3 py-2">
                        <div class="small"><?= sanitize($log['descripcion'] ?? $log['accion']) ?></div>
                        <div class="text-muted" style="font-size:.7rem"><?= formatRelativeDate($log['fecha']) ?></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Columna derecha -->
    <div class="col-lg-8">
        <!-- Formulario datos personales -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0">
                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Datos Personales</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/?page=profile">
                    <?= csrfField() ?>
                    <input type="hidden" name="accion" value="actualizar_perfil">

                    <div class="mb-3">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" name="nombre"
                               value="<?= sanitize($usuario['nombre']) ?>" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" name="email"
                               value="<?= sanitize($usuario['email']) ?>" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Último acceso</label>
                        <input type="text" class="form-control" value="<?= formatDate($usuario['ultimo_acceso']) ?>" disabled>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-2"></i>Guardar Cambios
                    </button>
                </form>
            </div>
        </div>

        <!-- Formulario cambio de contraseña -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h6 class="mb-0"><i class="bi bi-lock me-2"></i>Cambiar Contraseña</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/?page=profile">
                    <?= csrfField() ?>
                    <input type="hidden" name="accion" value="cambiar_password">

                    <div class="mb-3">
                        <label class="form-label">Contraseña actual</label>
                        <input type="password" class="form-control" name="password_actual" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" class="form-control" name="password_nueva"
                               id="nuevaPass" required>
                        <div class="form-text">Mínimo 8 caracteres, una mayúscula, un número y un símbolo.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmar nueva contraseña</label>
                        <input type="password" class="form-control" name="password_confirmar"
                               id="confirmarPass" required>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key me-2"></i>Cambiar Contraseña
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
    this.classList.toggle('is-valid', match && this.value.length > 0);
});
</script>
