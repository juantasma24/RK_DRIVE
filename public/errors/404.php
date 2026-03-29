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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .error-card {
            max-width: 500px;
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: #667eea;
            line-height: 1;
            margin-bottom: 1rem;
        }
        .error-icon {
            font-size: 5rem;
            color: #764ba2;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="bi bi-question-circle"></i>
        </div>
        <h1 class="error-code">404</h1>
        <h2 class="h4 mb-3">Pagina no encontrada</h2>
        <p class="text-muted mb-4">
            <?= sanitize($message ?? 'Lo sentimos, la pagina que buscas no existe o ha sido movida.') ?>
        </p>
        <div class="d-grid gap-2">
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