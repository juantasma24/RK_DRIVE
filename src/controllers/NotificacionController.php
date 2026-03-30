<?php
/**
 * Controlador de Notificaciones
 *
 * @package RKMarketingDrive
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class NotificacionController {

    public function __construct() {
        requireAuth();
    }

    public function index() {
        $userId = getCurrentUserId();

        // Marcar todas como leídas al abrir la página
        em()->getConnection()->executeStatement(
            "UPDATE notificaciones SET leida = 1, fecha_lectura = NOW()
             WHERE usuario_id = :uid AND leida = 0",
            ['uid' => $userId]
        );

        $notificaciones = em()->getConnection()->executeQuery(
            "SELECT * FROM notificaciones WHERE usuario_id = :uid ORDER BY fecha_creacion DESC LIMIT 50",
            ['uid' => $userId]
        )->fetchAllAssociative();

        return [
            'view'           => 'notifications/index',
            'notificaciones' => $notificaciones,
        ];
    }
}
