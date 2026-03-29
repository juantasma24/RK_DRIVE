<?php
/**
 * Controlador de Administración
 *
 * Gestiona usuarios, archivos, logs y configuración del sistema.
 *
 * @package RKMarketingDrive
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class AdminController {

    private $db;

    public function __construct() {
        $this->db = db();
        requireAuth('admin');
    }

    // =========================================================================
    // GESTIÓN DE USUARIOS
    // =========================================================================

    public function users($action, $id) {
        switch ($action) {
            case 'create': return $this->createUser();
            case 'edit':   return $this->editUser((int)$id);
            case 'toggle': return $this->toggleUser((int)$id);
            case 'delete': return $this->deleteUser((int)$id);
            default:       return $this->listUsers();
        }
    }

    private function listUsers() {
        $usuarios = $this->db->fetchAll(
            "SELECT u.id, u.nombre, u.email, u.rol, u.activo,
                    u.almacenamiento_usado, u.almacenamiento_maximo,
                    u.ultimo_acceso, u.fecha_creacion,
                    COUNT(DISTINCT c.id) AS total_carpetas,
                    COUNT(DISTINCT a.id) AS total_archivos
             FROM usuarios u
             LEFT JOIN carpetas c ON u.id = c.usuario_id AND c.activa = 1
             LEFT JOIN archivos a ON u.id = a.usuario_id AND a.en_papelera = 0
             GROUP BY u.id
             ORDER BY u.fecha_creacion DESC"
        );

        return ['view' => 'admin/users', 'usuarios' => $usuarios];
    }

    private function createUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=admin/users');
        }
        requireCSRFToken();

        $nombre    = trim($_POST['nombre'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $rol       = $_POST['rol'] ?? 'cliente';
        $storageGB = (int)($_POST['storage_gb'] ?? 2);

        if (empty($nombre) || empty($email) || empty($password)) {
            setFlash('error', 'Nombre, email y contraseña son obligatorios.');
            redirect('/?page=admin/users');
        }

        $email = sanitizeEmail($email);
        if (!$email) {
            setFlash('error', 'Email no válido.');
            redirect('/?page=admin/users');
        }

        if (!in_array($rol, ['cliente', 'admin'])) {
            $rol = 'cliente';
        }

        $existe = $this->db->fetchOne(
            "SELECT id FROM usuarios WHERE email = :email LIMIT 1",
            ['email' => $email]
        );
        if ($existe) {
            setFlash('error', 'Ya existe un usuario con ese email.');
            redirect('/?page=admin/users');
        }

        $validacion = validatePassword($password);
        if (!$validacion['valid']) {
            setFlash('error', implode(' ', $validacion['errors']));
            redirect('/?page=admin/users');
        }

        $storageBytes = $storageGB * 1024 * 1024 * 1024;

        $userId = $this->db->insert(
            "INSERT INTO usuarios (nombre, email, password_hash, rol, almacenamiento_maximo, activo)
             VALUES (:nombre, :email, :hash, :rol, :storage, 1)",
            [
                'nombre'  => sanitizeString($nombre),
                'email'   => $email,
                'hash'    => hashPassword($password),
                'rol'     => $rol,
                'storage' => $storageBytes,
            ]
        );

        logActivity(getCurrentUserId(), 'admin_user_create', "Usuario creado: {$nombre} ({$email})", 'usuario', $userId);
        setFlash('success', "Usuario \"{$nombre}\" creado correctamente.");
        redirect('/?page=admin/users');
    }

    private function editUser($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=admin/users');
        }
        requireCSRFToken();

        $nombre    = trim($_POST['nombre'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $rol       = $_POST['rol'] ?? 'cliente';
        $storageGB = (int)($_POST['storage_gb'] ?? 2);

        if (empty($nombre) || empty($email)) {
            setFlash('error', 'Nombre y email son obligatorios.');
            redirect('/?page=admin/users');
        }

        $email = sanitizeEmail($email);
        if (!$email) {
            setFlash('error', 'Email no válido.');
            redirect('/?page=admin/users');
        }

        if (!in_array($rol, ['cliente', 'admin'])) {
            $rol = 'cliente';
        }

        $existe = $this->db->fetchOne(
            "SELECT id FROM usuarios WHERE email = :email AND id != :id LIMIT 1",
            ['email' => $email, 'id' => $id]
        );
        if ($existe) {
            setFlash('error', 'Ese email ya está en uso.');
            redirect('/?page=admin/users');
        }

        $storageBytes = $storageGB * 1024 * 1024 * 1024;

        $this->db->execute(
            "UPDATE usuarios SET nombre = :nombre, email = :email, rol = :rol,
             almacenamiento_maximo = :storage WHERE id = :id",
            [
                'nombre'  => sanitizeString($nombre),
                'email'   => $email,
                'rol'     => $rol,
                'storage' => $storageBytes,
                'id'      => $id,
            ]
        );

        logActivity(getCurrentUserId(), 'admin_user_edit', "Usuario editado: ID {$id}", 'usuario', $id);
        setFlash('success', 'Usuario actualizado correctamente.');
        redirect('/?page=admin/users');
    }

    private function toggleUser($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=admin/users');
        }
        requireCSRFToken();

        // No permitir desactivar al propio admin
        if ($id === getCurrentUserId()) {
            setFlash('error', 'No puedes desactivar tu propia cuenta.');
            redirect('/?page=admin/users');
        }

        $usuario = $this->db->fetchOne(
            "SELECT id, nombre, activo FROM usuarios WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
        if (!$usuario) {
            setFlash('error', 'Usuario no encontrado.');
            redirect('/?page=admin/users');
        }

        $nuevoEstado = $usuario['activo'] ? 0 : 1;
        $this->db->execute(
            "UPDATE usuarios SET activo = :activo WHERE id = :id",
            ['activo' => $nuevoEstado, 'id' => $id]
        );

        $accion = $nuevoEstado ? 'activado' : 'desactivado';
        logActivity(getCurrentUserId(), 'admin_user_toggle', "Usuario {$accion}: {$usuario['nombre']}", 'usuario', $id);
        setFlash('success', "Usuario \"{$usuario['nombre']}\" {$accion}.");
        redirect('/?page=admin/users');
    }

    private function deleteUser($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=admin/users');
        }
        requireCSRFToken();

        if ($id === getCurrentUserId()) {
            setFlash('error', 'No puedes eliminar tu propia cuenta.');
            redirect('/?page=admin/users');
        }

        $usuario = $this->db->fetchOne(
            "SELECT nombre FROM usuarios WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
        if (!$usuario) {
            setFlash('error', 'Usuario no encontrado.');
            redirect('/?page=admin/users');
        }

        // Eliminar archivos físicos del usuario
        $archivos = $this->db->fetchAll(
            "SELECT ruta_fisica FROM archivos WHERE usuario_id = :id",
            ['id' => $id]
        );
        foreach ($archivos as $archivo) {
            if (file_exists($archivo['ruta_fisica'])) {
                unlink($archivo['ruta_fisica']);
            }
        }

        $this->db->execute("DELETE FROM usuarios WHERE id = :id", ['id' => $id]);

        logActivity(getCurrentUserId(), 'admin_user_delete', "Usuario eliminado: {$usuario['nombre']}", 'usuario', $id);
        setFlash('success', "Usuario \"{$usuario['nombre']}\" eliminado permanentemente.");
        redirect('/?page=admin/users');
    }

    // =========================================================================
    // GESTIÓN DE ARCHIVOS
    // =========================================================================

    public function files($action, $id) {
        switch ($action) {
            case 'delete': return $this->deleteFile((int)$id);
            default:       return $this->listFiles();
        }
    }

    private function listFiles() {
        $archivos = $this->db->fetchAll(
            "SELECT a.id, a.nombre_original, a.extension, a.tamano_bytes,
                    a.en_papelera, a.fecha_subida, a.fecha_eliminacion,
                    c.nombre AS carpeta_nombre,
                    u.nombre AS usuario_nombre, u.email AS usuario_email
             FROM archivos a
             INNER JOIN carpetas c ON a.carpeta_id = c.id
             INNER JOIN usuarios u ON a.usuario_id = u.id
             ORDER BY a.fecha_subida DESC
             LIMIT 200"
        );

        $totalSize = $this->db->fetchOne(
            "SELECT COALESCE(SUM(tamano_bytes), 0) AS total FROM archivos WHERE en_papelera = 0"
        );

        return [
            'view'       => 'admin/files',
            'archivos'   => $archivos,
            'totalSize'  => (int)$totalSize['total'],
        ];
    }

    private function deleteFile($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=admin/files');
        }
        requireCSRFToken();

        $archivo = $this->db->fetchOne(
            "SELECT nombre_original, ruta_fisica FROM archivos WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
        if (!$archivo) {
            setFlash('error', 'Archivo no encontrado.');
            redirect('/?page=admin/files');
        }

        if (file_exists($archivo['ruta_fisica'])) {
            unlink($archivo['ruta_fisica']);
        }

        $this->db->execute("DELETE FROM archivos WHERE id = :id", ['id' => $id]);

        logActivity(getCurrentUserId(), 'admin_file_delete', "Archivo eliminado: {$archivo['nombre_original']}", 'archivo', $id);
        setFlash('success', "Archivo eliminado correctamente.");
        redirect('/?page=admin/files');
    }

    // =========================================================================
    // LOGS DE ACTIVIDAD
    // =========================================================================

    public function logs() {
        $filtroAccion  = sanitizeString($_GET['accion'] ?? '');
        $filtroUsuario = sanitizeString($_GET['usuario'] ?? '');

        $sql    = "SELECT l.id, l.accion, l.descripcion, l.entidad_tipo,
                          l.ip_address, l.fecha,
                          u.nombre AS usuario_nombre, u.email AS usuario_email
                   FROM logs_actividad l
                   LEFT JOIN usuarios u ON l.usuario_id = u.id
                   WHERE 1=1";
        $params = [];

        if (!empty($filtroAccion)) {
            $sql .= " AND l.accion LIKE :accion";
            $params['accion'] = '%' . $filtroAccion . '%';
        }
        if (!empty($filtroUsuario)) {
            $sql .= " AND (u.nombre LIKE :usuario OR u.email LIKE :usuario)";
            $params['usuario'] = '%' . $filtroUsuario . '%';
        }

        $sql .= " ORDER BY l.fecha DESC LIMIT 300";

        $logs = $this->db->fetchAll($sql, $params);

        return [
            'view'          => 'admin/logs',
            'logs'          => $logs,
            'filtroAccion'  => $filtroAccion,
            'filtroUsuario' => $filtroUsuario,
        ];
    }

    // =========================================================================
    // CONFIGURACIÓN DEL SISTEMA
    // =========================================================================

    public function settings() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requireCSRFToken();

            $campos = ['max_file_size', 'max_storage_client', 'max_folders_client',
                       'login_attempts', 'lockout_time', 'session_lifetime'];

            foreach ($campos as $clave) {
                if (isset($_POST[$clave])) {
                    $valor = (int)$_POST[$clave];
                    if ($valor > 0) {
                        $this->db->execute(
                            "UPDATE configuracion_sistema SET valor = :valor WHERE clave = :clave",
                            ['valor' => $valor, 'clave' => $clave]
                        );
                    }
                }
            }

            logActivity(getCurrentUserId(), 'admin_settings', 'Configuración del sistema actualizada', 'sistema', null);
            setFlash('success', 'Configuración guardada correctamente.');
            redirect('/?page=admin/settings');
        }

        $config = $this->db->fetchAll("SELECT clave, valor, descripcion FROM configuracion_sistema ORDER BY id");
        $configMap = [];
        foreach ($config as $row) {
            $configMap[$row['clave']] = $row;
        }

        $limpieza = $this->db->fetchOne("SELECT * FROM configuracion_limpieza LIMIT 1");

        return [
            'view'     => 'admin/settings',
            'config'   => $configMap,
            'limpieza' => $limpieza,
        ];
    }
}
