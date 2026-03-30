<?php
/**
 * Vista: Error 404 - Pagina no encontrada
 * Se renderiza DENTRO del layout (header.php + footer.php).
 *
 * @var string $message Mensaje de error personalizado
 */
?>
<div class="rk-404-wrap">
    <div class="rk-404-card">
        <span class="rk-404-icon bi bi-compass"></span>
        <span class="rk-404-code">404</span>
        <h2 class="h4 mb-3">Pagina no encontrada</h2>
        <p class="mb-4">
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
</div>
