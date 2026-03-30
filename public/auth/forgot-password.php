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
            <div class="logo-container">
                <i class="bi bi-lock-fill logo-icon"></i>
            </div>
            <h1 class="h4 mb-0">Recuperar Contrasena</h1>
            <p class="text-muted small mb-0">Te enviaremos instrucciones por email</p>
        </div>

        <div class="login-body">

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
                <a href="<?= APP_URL ?>/?page=login" class="small text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesion
                </a>
            </div>

            <?php endif; ?>
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
