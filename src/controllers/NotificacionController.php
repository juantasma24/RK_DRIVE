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

    private $db;

    public function __construct() {
        $this->db = db();
        requireAuth();
    }

    public function index() {
        $userId = getCurrentUserId();

        // Marcar todas como leídas al abrir la página
        $this->db->execute(
            "UPDATE notificaciones SET leida = 1, fecha_lectura = NOW()
             WHERE usuario_id = :uid AND leida = 0",
            ['uid' => $userId]
        );

        $notificaciones = $this->db->fetchAll(
            "SELECT * FROM notificaciones WHERE usuario_id = :uid ORDER BY fecha_creacion DESC LIMIT 50",
            ['uid' => $userId]
        );

        return [
            'view'           => 'notifications/index',
            'notificaciones' => $notificaciones,
        ];
    }
}
