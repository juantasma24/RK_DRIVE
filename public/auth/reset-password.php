<?php
/**
 * Vista: Restablecer Contrasena
 *
 * @var array  $errors  Errores de validacion
 * @var bool   $success Si la contrasena fue actualizada
 * @var string $token   Token de recuperacion
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
                    Nueva Contrasena
                </h1>
                <p class="small mb-0" style="color:var(--text-muted);">
                    Elige una contrasena segura
                </p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?= sanitize($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST"
                  action="<?= APP_URL ?>/?page=reset-password&token=<?= urlencode($token) ?>">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= sanitize($token) ?>">

                <div class="mb-3">
                    <label for="password" class="form-label">Nueva Contrasena</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               required placeholder="Min. 8 caracteres" autofocus>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                                tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">
                        Minimo 8 caracteres, una mayuscula, un numero y un simbolo.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password_confirm" class="form-label">Confirmar Contrasena</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" id="password_confirm"
                               name="password_confirm" required placeholder="Repite la contrasena">
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Guardar nueva contrasena
                    </button>
                </div>
            </form>

            <div class="text-center mt-3">
                <a href="<?= APP_URL ?>/?page=login" class="small text-decoration-none"
                   style="color:var(--text-muted);">
                    <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesion
                </a>
            </div>
        </div>

        <div class="login-footer">
            <p>
                <i class="bi bi-shield-check me-1"></i>
                Conexion segura. Sus datos estan protegidos.
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput  = document.getElementById('password');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });
    }

    // Validar que las contrasenas coincidan en tiempo real
    const passwordConfirm = document.getElementById('password_confirm');
    if (passwordConfirm) {
        passwordConfirm.addEventListener('input', function () {
            if (this.value && this.value !== passwordInput.value) {
                this.setCustomValidity('Las contrasenas no coinciden.');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }
});
</script>
