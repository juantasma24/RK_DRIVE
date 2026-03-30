<?php
/**
 * Controlador de Usuario
 *
 * @package RKMarketingDrive
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class UsuarioController {

    public function __construct() {
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

        $usuario = em()->find(\App\Entity\Usuario::class, $userId);

        $actividad = em()->getConnection()->executeQuery(
            "SELECT accion, descripcion, fecha FROM logs_actividad
             WHERE usuario_id = :id ORDER BY fecha DESC LIMIT 10",
            ['id' => $userId]
        )->fetchAllAssociative();

        return [
            'view'      => 'profile/index',
            'usuario'   => $usuario->toArray(),
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
        $existing = (new \App\Repository\UsuarioRepository(em()))->findByEmail($email);
        if ($existing && $existing->getId() !== $userId) {
            setFlash('error', 'Ese email ya está en uso por otro usuario.');
            return;
        }

        $usuario = em()->find(\App\Entity\Usuario::class, $userId);
        $usuario->setNombre(sanitizeString($nombre));
        $usuario->setEmail($email);
        $usuario->setFechaActualizacion(new \DateTimeImmutable());
        em()->flush();

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

        $result = changePassword($userId, $actual, $nueva);

        if ($result['success']) {
            setFlash('success', 'Contraseña actualizada correctamente.');
            redirect('/?page=profile');
        } else {
            $msg = !empty($result['errors']) ? implode(' ', $result['errors']) : $result['error'];
            setFlash('error', $msg);
        }
    }
}
