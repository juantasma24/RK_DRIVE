<?php
/**
 * Vista: Error 500 - Error Interno del Servidor
 */
?>
<div class="d-flex flex-column align-items-center justify-content-center text-center" style="min-height:60vh;">
    <div style="font-size:5rem;line-height:1;" class="text-danger mb-3">
        <i class="bi bi-exclamation-triangle"></i>
    </div>
    <h1 class="fw-bold mb-2" style="font-size:3rem;color:var(--text-primary);">500</h1>
    <h4 class="mb-3" style="color:var(--text-secondary);">Error Interno del Servidor</h4>
    <p class="text-muted mb-4" style="max-width:420px;">
        Algo salió mal en el servidor. El equipo técnico ha sido notificado.
        Por favor, intenta de nuevo en unos momentos.
    </p>
    <div class="d-flex gap-2">
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </a>
        <a href="<?= APP_URL ?>/dashboard" class="btn btn-primary">
            <i class="bi bi-house me-2"></i>Ir al Panel
        </a>
    </div>
</div>
