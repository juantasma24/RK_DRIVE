<?php
/**
 * Controlador de Trabajadores
 * Acceso de solo lectura + permisos opcionales de edición/eliminación por archivo de cliente.
 *
 * @package RKMarketingDrive
 */

if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class TrabajadorController {

    public function __construct() {
        requireAuth('trabajador');
    }

    public function clients($action, $id) {
        switch ($action) {
            case 'view':     return $this->viewClientFiles((int)$id);
            case 'download': return $this->downloadClientFile((int)$id);
            case 'edit':     return $this->editClientFile((int)$id);
            case 'delete':   return $this->deleteClientFile((int)$id);
            default:         return $this->listClients();
        }
    }

    // =========================================================================
    // LISTAR CLIENTES
    // =========================================================================

    private function listClients() {
        $clientes = em()->getConnection()->executeQuery(
            "SELECT u.id, u.nombre, u.email, u.activo,
                    u.ultimo_acceso,
                    COUNT(DISTINCT c.id) AS total_carpetas,
                    COUNT(DISTINCT a.id) AS total_archivos
             FROM usuarios u
             LEFT JOIN carpetas c ON u.id = c.usuario_id AND c.activa = 1
             LEFT JOIN archivos a ON u.id = a.usuario_id AND a.en_papelera = 0
             WHERE u.rol = 'cliente'
             GROUP BY u.id
             ORDER BY u.nombre ASC"
        )->fetchAllAssociative();

        return [
            'view'         => 'worker/clients',
            'clientes'     => $clientes,
            'puedeEditar'  => canEditFiles(),
            'puedeEliminar'=> canDeleteFiles(),
        ];
    }

    // =========================================================================
    // VER ARCHIVOS DE UN CLIENTE
    // =========================================================================

    private function viewClientFiles($usuarioId) {
        $usuario = em()->find(\App\Entity\Usuario::class, $usuarioId);
        if (!$usuario || $usuario->getRol() !== 'cliente') {
            setFlash('error', 'Cliente no encontrado.');
            redirect('/?page=worker/clients');
        }

        $filters = [];

        $enPapelera = $_GET['en_papelera'] ?? '';
        if ($enPapelera === '1') {
            $filters['en_papelera'] = true;
        } elseif ($enPapelera === '0') {
            $filters['en_papelera'] = false;
        }

        if (!empty($_GET['carpeta_id']))  $filters['carpeta_id'] = (int)$_GET['carpeta_id'];
        if (!empty($_GET['extension']))   $filters['extension']  = sanitizeString($_GET['extension']);
        if (!empty($_GET['busqueda']))    $filters['busqueda']   = sanitizeString($_GET['busqueda']);

        $repo    = new \App\Repository\ArchivoRepository(em());
        $archivos = $repo->findByUsuarioId($usuarioId, $filters);

        $carpetas = em()->getConnection()->executeQuery(
            "SELECT id, nombre FROM carpetas WHERE usuario_id = :uid AND activa = 1 ORDER BY nombre ASC",
            ['uid' => $usuarioId]
        )->fetchAllAssociative();

        return [
            'view'            => 'worker/client_files',
            'usuario'         => $usuario,
            'archivos'        => $archivos,
            'carpetas'        => $carpetas,
            'filters'         => $filters,
            'enPapeleraFiltro'=> $enPapelera,
            'puedeEditar'     => canEditFiles(),
            'puedeEliminar'   => canDeleteFiles(),
        ];
    }

    // =========================================================================
    // DESCARGAR ARCHIVO
    // =========================================================================

    private function downloadClientFile($archivoId) {
        $archivo = em()->find(\App\Entity\Archivo::class, $archivoId);
        if (!$archivo) {
            setFlash('error', 'Archivo no encontrado.');
            redirect('/?page=worker/clients');
        }

        if ($archivo->getUsuario()->getRol() !== 'cliente') {
            setFlash('error', 'Archivo no encontrado.');
            redirect('/?page=worker/clients');
        }

        $rutaFisica = $archivo->getRutaFisica();
        if (!file_exists($rutaFisica)) {
            setFlash('error', 'El archivo físico no existe en el servidor.');
            redirect('/?page=worker/clients&action=view&id=' . $archivo->getUsuario()->getId());
        }

        logActivity(
            getCurrentUserId(),
            'worker_file_download',
            "Trabajador descargó archivo: {$archivo->getNombreOriginal()} (cliente ID {$archivo->getUsuario()->getId()})",
            'archivo',
            $archivoId
        );

        $mimePermitidos = json_decode(ALLOWED_MIME_TYPES, true);
        $mimeSeguro = in_array($archivo->getTipoMime(), $mimePermitidos)
            ? $archivo->getTipoMime()
            : 'application/octet-stream';

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeSeguro);
        header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($archivo->getNombreOriginal()));
        header('Content-Length: ' . $archivo->getTamanoBytes());
        header('Cache-Control: no-cache');
        readfile($rutaFisica);
        exit;
    }

    // =========================================================================
    // EDITAR METADATOS (requiere permiso)
    // =========================================================================

    private function editClientFile($archivoId) {
        if (!canEditFiles()) {
            setFlash('error', 'No tienes permiso para editar archivos.');
            redirect('/?page=worker/clients');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=worker/clients');
        }
        requireCSRFToken();

        $archivo = em()->find(\App\Entity\Archivo::class, $archivoId);
        if (!$archivo) {
            setFlash('error', 'Archivo no encontrado.');
            redirect('/?page=worker/clients');
        }

        $nuevoNombre = trim($_POST['nombre_original'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        if (strlen($descripcion) > 500) {
            $descripcion = substr($descripcion, 0, 500);
        }
        $usuarioId   = $archivo->getUsuario()->getId();

        if (empty($nuevoNombre)) {
            setFlash('error', 'El nombre no puede estar vacío.');
            redirect('/?page=worker/clients&action=view&id=' . $usuarioId);
        }

        $nuevoNombre = sanitizeString($nuevoNombre);
        $extActual   = $archivo->getExtension();
        if (!str_ends_with(strtolower($nuevoNombre), '.' . $extActual)) {
            $nuevoNombre .= '.' . $extActual;
        }

        $archivo->setNombreOriginal($nuevoNombre);
        $archivo->setDescripcion($descripcion !== '' ? $descripcion : null);
        $archivo->setFechaActualizacion(new \DateTimeImmutable());
        em()->flush();

        logActivity(
            getCurrentUserId(),
            'worker_file_edit',
            "Trabajador editó metadatos de archivo ID {$archivoId} (cliente ID {$usuarioId})",
            'archivo',
            $archivoId
        );

        setFlash('success', 'Archivo actualizado correctamente.');
        redirect('/?page=worker/clients&action=view&id=' . $usuarioId);
    }

    // =========================================================================
    // ELIMINAR ARCHIVO (requiere permiso)
    // =========================================================================

    private function deleteClientFile($archivoId) {
        if (!canDeleteFiles()) {
            setFlash('error', 'No tienes permiso para eliminar archivos.');
            redirect('/?page=worker/clients');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/?page=worker/clients');
        }
        requireCSRFToken();

        $archivo = em()->find(\App\Entity\Archivo::class, $archivoId);
        if (!$archivo) {
            setFlash('error', 'Archivo no encontrado.');
            redirect('/?page=worker/clients');
        }

        $usuarioId      = $archivo->getUsuario()->getId();
        $rutaFisica     = $archivo->getRutaFisica();
        $nombreOriginal = $archivo->getNombreOriginal();
        $tamano         = $archivo->getTamanoBytes();

        if (file_exists($rutaFisica)) {
            unlink($rutaFisica);
        }

        em()->getConnection()->executeStatement(
            "UPDATE usuarios SET almacenamiento_usado = (
                SELECT COALESCE(SUM(tamano_bytes), 0)
                FROM archivos WHERE usuario_id = :uid AND en_papelera = 0
             ) WHERE id = :uid",
            ['uid' => $usuarioId]
        );

        em()->remove($archivo);
        em()->flush();

        logActivity(
            getCurrentUserId(),
            'worker_file_delete',
            "Trabajador eliminó archivo: {$nombreOriginal} (cliente ID {$usuarioId})",
            'archivo',
            $archivoId
        );

        setFlash('success', "Archivo \"{$nombreOriginal}\" eliminado permanentemente.");
        redirect('/?page=worker/clients&action=view&id=' . $usuarioId);
    }
}
