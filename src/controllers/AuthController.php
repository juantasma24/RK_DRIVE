<?php
/**
 * Controlador de Autenticacion
 *
 * Maneja login, logout, recuperacion y restablecimiento de contrasena.
 *
 * @package RKMarketingDrive
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class AuthController {

    // =========================================================================
    // LOGIN
    // =========================================================================

    public function login() {
        $errors = [];
        $email  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Verificar CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!verifyCSRFToken($csrfToken)) {
                $errors[] = 'Token de seguridad invalido. Recarga la pagina e intenta de nuevo.';
            } else {
                $email    = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $remember = isset($_POST['remember']);

                // Validaciones basicas
                if (empty($email) || empty($password)) {
                    $errors[] = 'Por favor, completa todos los campos.';
                } else {
                    $result = authenticateUser($email, $password);

                    if ($result['success']) {
                        loginUser($result['user']);

                        // Redirigir segun rol
                        if ($result['user']['rol'] === 'admin') {
                            redirect('/?page=dashboard');
                        } elseif ($result['user']['rol'] === 'trabajador') {
                            redirect('/?page=worker/clients');
                        } else {
                            redirect('/?page=dashboard');
                        }
                    } else {
                        $errors[] = $result['error'];
                    }
                }
            }
        }

        return [
            'view'   => 'auth/login',
            'errors' => $errors,
            'email'  => $email,
        ];
    }

    // =========================================================================
    // LOGOUT
    // =========================================================================

    public function logout() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=dashboard');
        }
        requireCSRFToken();
        logoutUser();
        setFlash('success', 'Has cerrado sesion correctamente.');
        redirect('/?page=login');
    }

    // =========================================================================
    // FORGOT PASSWORD
    // =========================================================================

    public function forgotPassword() {
        $errors  = [];
        $success = false;
        $email   = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!verifyCSRFToken($csrfToken)) {
                $errors[] = 'Token de seguridad invalido. Recarga la pagina e intenta de nuevo.';
            } else {
                $email = trim($_POST['email'] ?? '');

                if (empty($email)) {
                    $errors[] = 'Por favor, ingresa tu correo electronico.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'El correo electronico no es valido.';
                } else {
                    $result = generatePasswordResetToken($email);

                    if ($result['success']) {
                        // En produccion aqui se enviaria el email con el token
                        // Por ahora solo mostramos mensaje de exito
                        $success = true;
                    } else {
                        $errors[] = $result['error'];
                    }
                }
            }
        }

        return [
            'view'    => 'auth/forgot-password',
            'errors'  => $errors,
            'success' => $success,
            'email'   => $email,
        ];
    }

    // =========================================================================
    // RESET PASSWORD
    // =========================================================================

    public function resetPassword() {
        $errors  = [];
        $success = false;
        $token   = $_GET['token'] ?? '';

        // Verificar que el token es valido antes de mostrar el formulario
        $user = null;
        if (!empty($token)) {
            $user = verifyPasswordResetToken($token);
        }

        if (empty($token) || !$user) {
            setFlash('error', 'El enlace de recuperacion es invalido o ha expirado.');
            redirect('/?page=forgot-password');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!verifyCSRFToken($csrfToken)) {
                $errors[] = 'Token de seguridad invalido. Recarga la pagina e intenta de nuevo.';
            } else {
                $password        = $_POST['password'] ?? '';
                $passwordConfirm = $_POST['password_confirm'] ?? '';

                if (empty($password) || empty($passwordConfirm)) {
                    $errors[] = 'Por favor, completa todos los campos.';
                } elseif ($password !== $passwordConfirm) {
                    $errors[] = 'Las contrasenas no coinciden.';
                } else {
                    $result = resetPasswordWithToken($token, $password);

                    if ($result['success']) {
                        $success = true;
                        setFlash('success', 'Contrasena actualizada correctamente. Ya puedes iniciar sesion.');
                        redirect('/?page=login');
                    } else {
                        if (!empty($result['errors'])) {
                            $errors = array_merge($errors, $result['errors']);
                        } else {
                            $errors[] = $result['error'];
                        }
                    }
                }
            }
        }

        return [
            'view'    => 'auth/reset-password',
            'errors'  => $errors,
            'success' => $success,
            'token'   => $token,
        ];
    }

    // =========================================================================
    // REGISTER (creacion de cuenta - solo para uso futuro o admin)
    // =========================================================================

    public function register() {
        // El registro publico esta deshabilitado en esta plataforma.
        // Los usuarios son creados por el administrador.
        setFlash('info', 'El registro de usuarios lo gestiona el administrador.');
        redirect('/?page=login');
    }
}
