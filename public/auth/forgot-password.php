<?php
/**
 * Vista: Recuperar Contrasena
 *
 * @var array  $errors  Errores de validacion
 * @var bool   $success Si el email fue enviado correctamente
 * @var string $email   Email ingresado
 */
?>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <img src="<?= APP_URL ?>/public/img/logos/logo_rk_negro.svg" alt="RK Drive">
        </div>

        <div class="login-body">
            <div class="text-center mb-4">
                <h1 class="h5 mb-1" style="color:var(--text-primary);font-family:var(--font-display);">
                    Recuperar Contrasena
                </h1>
                <p class="small mb-0" style="color:var(--text-muted);">
                    Te enviaremos instrucciones por email
                </p>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>
                Si el correo esta registrado, recibiras las instrucciones para restablecer tu contrasena.
            </div>
            <div class="d-grid mt-3">
                <a href="<?= APP_URL ?>/?page=login" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-2"></i>Volver al inicio de sesion
                </a>
            </div>

            <?php else: ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?= sanitize($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= APP_URL ?>/?page=forgot-password">
                <?= csrfField() ?>

                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electronico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= sanitize($email ?? '') ?>" required
                               placeholder="tu@email.com" autofocus>
                    </div>
                    <div class="form-text">Ingresa el email asociado a tu cuenta.</div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-send me-2"></i>Enviar instrucciones
                    </button>
                </div>
            </form>

            <div class="text-center mt-3">
                <a href="<?= APP_URL ?>/?page=login" class="small text-decoration-none"
                   style="color:var(--text-muted);">
                    <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesion
                </a>
            </div>

            <?php endif; ?>
        </div>

        <div class="login-footer">
            <p>
                <i class="bi bi-shield-check me-1"></i>
                Conexion segura. Sus datos estan protegidos.
            </p>
        </div>
    </div>
</div>
