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
            <div class="logo-container">
                <i class="bi bi-cloud-arrow-up logo-icon"></i>
            </div>
            <h1 class="h4 mb-0">RK Marketing Drive</h1>
            <p class="text-muted small mb-0">Plataforma de Gestion de Archivos</p>
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

            <form method="POST" action="<?= APP_URL ?>/login" id="loginForm">
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
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label small" for="remember">Recordarme</label>
                    </div>
                    <a href="<?= APP_URL ?>/forgot-password" class="small text-decoration-none">Olvide mi contrasena</a>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesion
                    </button>
                </div>
            </form>
        </div>

        <div class="login-footer">
            <p class="text-muted small mb-0">
                <i class="bi bi-shield-check me-1"></i>
                Conexion segura. Sus datos estan protegidos.
            </p>
        </div>
    </div>

    <div class="login-info mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="bi bi-info-circle me-2"></i>Credenciales de Demo</h5>
                        <p class="card-text small mb-0">
                            <strong>Admin:</strong> admin@rksolutions.com / password<br>
                            <strong>Cliente:</strong> cliente1@empresaabc.com / password
                        </p>
                    </div>
                </div>
            </div>
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
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
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
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.logo-icon {
    font-size: 2.5rem;
}

.login-body {
    padding: 2rem;
}

.login-footer {
    padding: 1rem 2rem;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

.login-info .card {
    border: none;
    border-radius: 1rem;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
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
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });
    }

    // Form validation
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Por favor, completa todos los campos.');
            }
        });
    }
});
</script>