<?php
/**
 * Controlador de Usuario
 *
 * Gestiona el perfil y configuración del usuario autenticado.
 *
 * @package RKMarketingDrive
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class UsuarioController {

    private $db;

    public function __construct() {
        $this->db = db();
        requireAuth();
    }

    public function profile() {
        $userId = getCurrentUserId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'actualizar_perfil') {
                $this->actualizarPerfil($userId);
            } elseif ($accion === 'cambiar_password') {
                $this->cambiarPassword($userId);
            }
        }

        $usuario = $this->db->fetchOne(
            "SELECT id, nombre, email, rol, almacenamiento_usado, almacenamiento_maximo,
                    ultimo_acceso, fecha_creacion
             FROM usuarios WHERE id = :id LIMIT 1",
            ['id' => $userId]
        );

        $actividad = $this->db->fetchAll(
            "SELECT accion, descripcion, fecha FROM logs_actividad
             WHERE usuario_id = :id ORDER BY fecha DESC LIMIT 10",
            ['id' => $userId]
        );

        return [
            'view'      => 'profile/index',
            'usuario'   => $usuario,
            'actividad' => $actividad,
        ];
    }

    private function actualizarPerfil($userId) {
        requireCSRFToken();

        $nombre = trim($_POST['nombre'] ?? '');
        $email  = trim($_POST['email'] ?? '');

        if (empty($nombre) || empty($email)) {
            setFlash('error', 'Nombre y email son obligatorios.');
            return;
        }

        $email = sanitizeEmail($email);
        if (!$email) {
            setFlash('error', 'El email no es válido.');
            return;
        }

        // Verificar email único
        $existe = $this->db->fetchOne(
            "SELECT id FROM usuarios WHERE email = :email AND id != :id LIMIT 1",
            ['email' => $email, 'id' => $userId]
        );
        if ($existe) {
            setFlash('error', 'Ese email ya está en uso por otro usuario.');
            return;
        }

        $this->db->execute(
            "UPDATE usuarios SET nombre = :nombre, email = :email WHERE id = :id",
            ['nombre' => sanitizeString($nombre), 'email' => $email, 'id' => $userId]
        );

        $_SESSION['user_name']  = sanitizeString($nombre);
        $_SESSION['user_email'] = $email;

        logActivity($userId, 'profile_update', 'Perfil actualizado', 'usuario', $userId);
        setFlash('success', 'Perfil actualizado correctamente.');
        redirect('/?page=profile');
    }

    private function cambiarPassword($userId) {
        requireCSRFToken();

        $actual    = $_POST['password_actual'] ?? '';
        $nueva     = $_POST['password_nueva'] ?? '';
        $confirmar = $_POST['password_confirmar'] ?? '';

        if (empty($actual) || empty($nueva) || empty($confirmar)) {
            setFlash('error', 'Completa todos los campos de contraseña.');
            return;
        }

        if ($nueva !== $confirmar) {
            setFlash('error', 'La nueva contraseña y su confirmación no coinciden.');
            return;
        }

        $row = $this->db->fetchOne(
            "SELECT password_hash FROM usuarios WHERE id = :id LIMIT 1",
            ['id' => $userId]
        );

        if (!verifyPassword($actual, $row['password_hash'])) {
            setFlash('error', 'La contraseña actual es incorrecta.');
            return;
        }

        $validacion = validatePassword($nueva);
        if (!$validacion['valid']) {
            setFlash('error', implode(' ', $validacion['errors']));
            return;
        }

        $this->db->execute(
            "UPDATE usuarios SET password_hash = :hash WHERE id = :id",
            ['hash' => hashPassword($nueva), 'id' => $userId]
        );

        logActivity($userId, 'password_change', 'Contraseña cambiada', 'usuario', $userId);
        setFlash('success', 'Contraseña actualizada correctamente.');
        redirect('/?page=profile');
    }
}
