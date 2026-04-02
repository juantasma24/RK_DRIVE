<?php
/**
 * Vista: Login
 *
 * @var array $errors Errores de validacion
 * @var string $email Email ingresado (para recordar)
 */
?>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <img src="<?= APP_URL ?>/public/img/logos/logo_rk_blanco.svg" alt="RK Drive">
        </div>

        <div class="login-body">
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?= sanitize($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div id="validationAlert" class="alert alert-danger d-none">
                <ul class="mb-0" id="validationList"></ul>
            </div>

            <form method="POST" action="<?= APP_URL ?>/login" id="loginForm" novalidate>
                <?= csrfField() ?>

                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electronico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= sanitize($email ?? '') ?>" required
                               placeholder="tu@email.com" autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contrasena</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               required placeholder="Tu contrasena">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                                tabindex="-1" title="Mostrar/ocultar contrasena">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div class="form-check mb-0">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label small" for="remember">Recordarme</label>
                    </div>
                    <a href="<?= APP_URL ?>/forgot-password" class="small text-decoration-none"
                       style="color:var(--text-muted);">Olvide mi contrasena</a>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesion
                    </button>
                </div>
            </form>
        </div>

        <div class="login-footer">
            <p>
                <i class="bi bi-shield-check me-1"></i>
                Conexion segura. Sus datos estan protegidos.
            </p>
        </div>
    </div>

    <div class="login-info mt-4 w-100" style="max-width:420px;">
        <div class="alert alert-info py-2 small text-center mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Contacta al administrador para obtener tus credenciales de acceso.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Toggle password visibility
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

    // Form validation
    const loginForm      = document.getElementById('loginForm');
    const validationAlert = document.getElementById('validationAlert');
    const validationList  = document.getElementById('validationList');

    function showValidationErrors(errors) {
        validationList.innerHTML = errors.map(function(e) { return '<li>' + e + '</li>'; }).join('');
        validationAlert.classList.remove('d-none');
        validationAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideValidationErrors() {
        validationAlert.classList.add('d-none');
        validationList.innerHTML = '';
    }

    document.getElementById('email').addEventListener('input', hideValidationErrors);
    document.getElementById('password').addEventListener('input', hideValidationErrors);

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            const email    = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const errors   = [];

            if (!email)    errors.push('El correo electronico es obligatorio.');
            if (!password) errors.push('La contrasena es obligatoria.');

            if (errors.length > 0) {
                e.preventDefault();
                showValidationErrors(errors);
            }
        });
    }
});
</script>
