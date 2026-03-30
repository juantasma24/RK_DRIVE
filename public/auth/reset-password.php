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
            <div class="logo-container">
                <i class="bi bi-key-fill logo-icon"></i>
            </div>
            <h1 class="h4 mb-0">Nueva Contrasena</h1>
            <p class="text-muted small mb-0">Elige una contrasena segura</p>
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

            <form method="POST" action="<?= APP_URL ?>/?page=reset-password&token=<?= urlencode($token) ?>">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= sanitize($token) ?>">

                <div class="mb-3">
                    <label for="password" class="form-label">Nueva Contrasena</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               required placeholder="Min. 8 caracteres" autofocus>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Minimo 8 caracteres, una mayuscula, un numero y un simbolo.</div>
                </div>

                <div class="mb-4">
                    <label for="password_confirm" class="form-label">Confirmar Contrasena</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                               required placeholder="Repite la contrasena">
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Guardar nueva contrasena
                    </button>
                </div>
            </form>

            <div class="text-center mt-3">
                <a href="<?= APP_URL ?>/?page=login" class="small text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesion
                </a>
            </div>
        </div>

        <div class="login-footer">
            <p class="text-muted small mb-0">
                <i class="bi bi-shield-check me-1"></i>
                Conexion segura. Sus datos estan protegidos.
            </p>
        </div>
    </div>
</div>

<style>
.login-container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem 1rem;
}
.login-card {
    width: 100%;
    max-width: 400px;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    overflow: hidden;
}
.login-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
    text-align: center;
    color: white;
}
.logo-container {
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}
.logo-icon { font-size: 2.5rem; }
.login-body { padding: 2rem; }
.login-footer {
    padding: 1rem 2rem;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
}
.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.25rem rgba(102,126,234,0.25);
}
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%);
}
</style>

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
