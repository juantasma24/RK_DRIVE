<?php
/**
 * Controlador de Administración
 *
 * @package RKMarketingDrive
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class AdminController {

    public function __construct() {
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
        $usuarios = em()->getConnection()->executeQuery(
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
        )->fetchAllAssociative();

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

        if (!in_array($rol, ['cliente', 'admin'])) $rol = 'cliente';

        $repo = new \App\Repository\UsuarioRepository(em());
        if ($repo->findByEmail($email)) {
            setFlash('error', 'Ya existe un usuario con ese email.');
            redirect('/?page=admin/users');
        }

        $validacion = validatePassword($password);
        if (!$validacion['valid']) {
            setFlash('error', implode(' ', $validacion['errors']));
            redirect('/?page=admin/users');
        }

        $usuario = new \App\Entity\Usuario();
        $usuario->setNombre(sanitizeString($nombre));
        $usuario->setEmail($email);
        $usuario->setPasswordHash(hashPassword($password));
        $usuario->setRol($rol);
        $usuario->setAlmacenamientoMaximo((string)($storageGB * 1024 * 1024 * 1024));

        $repo->save($usuario);

        logActivity(getCurrentUserId(), 'admin_user_create', "Usuario creado: {$nombre} ({$email})", 'usuario', $usuario->getId());
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

        if (!in_array($rol, ['cliente', 'admin'])) $rol = 'cliente';

        $existing = (new \App\Repository\UsuarioRepository(em()))->findByEmail($email);
        if ($existing && $existing->getId() !== $id) {
            setFlash('error', 'Ese email ya está en uso.');
            redirect('/?page=admin/users');
        }

        $usuario = em()->find(\App\Entity\Usuario::class, $id);
        if (!$usuario) {
            setFlash('error', 'Usuario no encontrado.');
            redirect('/?page=admin/users');
        }

        $usuario->setNombre(sanitizeString($nombre));
        $usuario->setEmail($email);
        $usuario->setRol($rol);
        $usuario->setAlmacenamientoMaximo((string)($storageGB * 1024 * 1024 * 1024));
        $usuario->setFechaActualizacion(new \DateTimeImmutable());
        em()->flush();

        logActivity(getCurrentUserId(), 'admin_user_edit', "Usuario editado: ID {$id}", 'usuario', $id);
        setFlash('success', 'Usuario actualizado correctamente.');
        redirect('/?page=admin/users');
    }

    private function toggleUser($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=admin/users');
        }
        requireCSRFToken();

        if ($id === getCurrentUserId()) {
            setFlash('error', 'No puedes desactivar tu propia cuenta.');
            redirect('/?page=admin/users');
        }

        $usuario = em()->find(\App\Entity\Usuario::class, $id);
        if (!$usuario) {
            setFlash('error', 'Usuario no encontrado.');
            redirect('/?page=admin/users');
        }

        $nuevoEstado = !$usuario->isActivo();
        $usuario->setActivo($nuevoEstado);
        $usuario->setFechaActualizacion(new \DateTimeImmutable());
        em()->flush();

        $accion = $nuevoEstado ? 'activado' : 'desactivado';
        logActivity(getCurrentUserId(), 'admin_user_toggle', "Usuario {$accion}: {$usuario->getNombre()}", 'usuario', $id);
        setFlash('success', "Usuario \"{$usuario->getNombre()}\" {$accion}.");
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

        $usuario = em()->find(\App\Entity\Usuario::class, $id);
        if (!$usuario) {
            setFlash('error', 'Usuario no encontrado.');
            redirect('/?page=admin/users');
        }

        // Eliminar archivos físicos antes de borrar el registro (CASCADE borra la BD)
        $archivos = em()->getConnection()->executeQuery(
            "SELECT ruta_fisica FROM archivos WHERE usuario_id = :id",
            ['id' => $id]
        )->fetchAllAssociative();

        foreach ($archivos as $archivo) {
            if (file_exists($archivo['ruta_fisica'])) unlink($archivo['ruta_fisica']);
        }

        $nombre = $usuario->getNombre();
        em()->remove($usuario);
        em()->flush();

        logActivity(getCurrentUserId(), 'admin_user_delete', "Usuario eliminado: {$nombre}", 'usuario', $id);
        setFlash('success', "Usuario \"{$nombre}\" eliminado permanentemente.");
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
        $archivos = em()->getConnection()->executeQuery(
            "SELECT a.id, a.nombre_original, a.extension, a.tamano_bytes,
                    a.en_papelera, a.fecha_subida, a.fecha_eliminacion,
                    c.nombre AS carpeta_nombre,
                    u.nombre AS usuario_nombre, u.email AS usuario_email
             FROM archivos a
             INNER JOIN carpetas c ON a.carpeta_id = c.id
             INNER JOIN usuarios u ON a.usuario_id = u.id
             ORDER BY a.fecha_subida DESC
             LIMIT 200"
        )->fetchAllAssociative();

        $totalSize = em()->getConnection()->executeQuery(
            "SELECT COALESCE(SUM(tamano_bytes), 0) AS total FROM archivos WHERE en_papelera = 0"
        )->fetchAssociative();

        return [
            'view'      => 'admin/files',
            'archivos'  => $archivos,
            'totalSize' => (int)$totalSize['total'],
        ];
    }

    private function deleteFile($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=admin/files');
        }
        requireCSRFToken();

        $archivo = em()->find(\App\Entity\Archivo::class, $id);
        if (!$archivo) {
            setFlash('error', 'Archivo no encontrado.');
            redirect('/?page=admin/files');
        }

        $rutaFisica     = $archivo->getRutaFisica();
        $nombreOriginal = $archivo->getNombreOriginal();

        if (file_exists($rutaFisica)) unlink($rutaFisica);

        em()->remove($archivo);
        em()->flush();

        logActivity(getCurrentUserId(), 'admin_file_delete', "Archivo eliminado: {$nombreOriginal}", 'archivo', $id);
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

        $logs = em()->getConnection()->executeQuery($sql, $params)->fetchAllAssociative();

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
                        em()->getConnection()->executeStatement(
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

        $rows = em()->getConnection()->executeQuery(
            "SELECT clave, valor, descripcion FROM configuracion_sistema ORDER BY id"
        )->fetchAllAssociative();

        $configMap = [];
        foreach ($rows as $row) {
            $configMap[$row['clave']] = $row;
        }

        $limpieza = em()->getConnection()->executeQuery(
            "SELECT * FROM configuracion_limpieza LIMIT 1"
        )->fetchAssociative();

        return [
            'view'     => 'admin/settings',
            'config'   => $configMap,
            'limpieza' => $limpieza,
        ];
    }
}
