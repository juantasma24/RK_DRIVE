<?php
/**
 * Vista: Error 404 - Pagina no encontrada
 *
 * @var string $message Mensaje de error personalizado
 */
$skipLayout = true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 404 - Pagina no encontrada | RK Marketing Drive</title>
    <link rel="stylesheet" href="<?= defined('APP_URL') ? APP_URL : '' ?>/public/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= defined('APP_URL') ? APP_URL : '' ?>/public/vendor/bootstrap-icons/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= defined('APP_URL') ? APP_URL : '' ?>/public/css/style.css">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #0d0d0d;
            background-image: radial-gradient(circle, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 32px 32px;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <span class="error-icon bi bi-compass"></span>
        <span class="error-code">404</span>
        <h2 class="h4 mb-3" style="color:#e8e8e8;font-family:'Gill Sans','Gill Sans MT',Calibri,'Trebuchet MS',sans-serif;">
            Pagina no encontrada
        </h2>
        <p class="mb-4" style="color:#888888;font-size:0.9rem;">
            <?= sanitize($message ?? 'Lo sentimos, la pagina que buscas no existe o ha sido movida.') ?>
        </p>
        <div class="d-grid gap-2" style="max-width:280px;margin:0 auto;">
            <?php if (isAuthenticated()): ?>
            <a href="<?= APP_URL ?>/dashboard" class="btn btn-primary btn-lg">
                <i class="bi bi-house me-2"></i>Ir al Panel Principal
            </a>
            <?php else: ?>
            <a href="<?= APP_URL ?>/login" class="btn btn-primary btn-lg">
                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesion
            </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
