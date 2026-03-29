<?php
/**
 * RK Marketing Drive - Clase Archivo
 *
 * Modelo para la gestion de archivos multimedia.
 *
 * @package RKMarketingDrive
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class Archivo {
    private $db;
    private $id;
    private $carpeta_id;
    private $usuario_id;
    private $nombre_original;
    private $nombre_fisico;
    private $tipo_mime;
    private $extension;
    private $tamano_bytes;
    private $ruta_fisica;
    private $descripcion;
    private $en_papelera;
    private $fecha_subida;
    private $fecha_eliminacion;
    private $fecha_expiracion;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = db();
    }

    //=============================================================================
    // METODOS CRUD
    //=============================================================================

    /**
     * Busca un archivo por su ID
     *
     * @param int $id ID del archivo
     * @return Archivo|null
     */
    public function findById($id) {
        $sql = "SELECT * FROM archivos WHERE id = :id LIMIT 1";
        $data = $this->db->fetchOne($sql, ['id' => $id]);

        if ($data) {
            return $this->hydrate($data);
        }

        return null;
    }

    /**
     * Sube un nuevo archivo
     *
     * @param array $file Archivo de $_FILES
     * @param int $usuarioId ID del usuario
     * @param int $carpetaId ID de la carpeta
     * @param string $descripcion Descripcion opcional
     * @return array Resultado con 'success' y 'id' o 'error'
     */
    public function upload($file, $usuarioId, $carpetaId, $descripcion = null) {
        // Validar archivo
        $validation = validateUploadedFile($file);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }

        // Verificar que la carpeta pertenece al usuario
        $carpeta = new Carpeta();
        if (!$carpeta->belongsToUser($carpetaId, $usuarioId)) {
            return [
                'success' => false,
                'error' => 'La carpeta no pertenece al usuario.'
            ];
        }

        // Verificar espacio disponible
        $usuario = new Usuario();
        if (!$usuario->hasSpace($usuarioId, $file['size'])) {
            return [
                'success' => false,
                'error' => 'No tienes suficiente espacio de almacenamiento.'
            ];
        }

        // Generar ruta segura
        $extension = $validation['extension'];
        $nombreOriginal = sanitizeFilename($file['name']);
        $nombreFisico = bin2hex(random_bytes(16)) . '.' . $extension;
        $rutaFisica = generateSecurePath($usuarioId, $nombreFisico);

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $rutaFisica)) {
            return [
                'success' => false,
                'error' => 'Error al subir el archivo.'
            ];
        }

        // Insertar en base de datos
        $sql = "INSERT INTO archivos
                (carpeta_id, usuario_id, nombre_original, nombre_fisico, tipo_mime,
                 extension, tamano_bytes, ruta_fisica, descripcion)
                VALUES
                (:carpeta_id, :usuario_id, :nombre_original, :nombre_fisico, :tipo_mime,
                 :extension, :tamano_bytes, :ruta_fisica, :descripcion)";

        try {
            $archivoId = $this->db->insert($sql, [
                'carpeta_id' => $carpetaId,
                'usuario_id' => $usuarioId,
                'nombre_original' => $nombreOriginal,
                'nombre_fisico' => $nombreFisico,
                'tipo_mime' => $validation['mime'],
                'extension' => $extension,
                'tamano_bytes' => $file['size'],
                'ruta_fisica' => $rutaFisica,
                'descripcion' => $descripcion ? sanitizeString($descripcion) : null
            ]);

            // Actualizar almacenamiento del usuario
            $usuario->updateStorage($usuarioId, $file['size'], true);

            // Registrar actividad
            logActivity(
                $usuarioId,
                'file_upload',
                "Archivo subido: {$nombreOriginal}",
                'archivo',
                $archivoId
            );

            // Crear notificacion
            $notificacion = new Notificacion();
            $notificacion->create([
                'usuario_id' => $usuarioId,
                'tipo' => 'subida',
                'titulo' => 'Archivo subido',
                'mensaje' => "El archivo '{$nombreOriginal}' se ha subido correctamente."
            ]);

            return [
                'success' => true,
                'id' => $archivoId,
                'nombre' => $nombreOriginal
            ];
        } catch (Exception $e) {
            // Eliminar archivo fisico si fallo la insercion
            if (file_exists($rutaFisica)) {
                unlink($rutaFisica);
            }

            logMessage('error', 'Error al subir archivo', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Error al guardar el archivo en la base de datos.'
            ];
        }
    }

    /**
     * Actualiza la descripcion de un archivo
     *
     * @param int $id ID del archivo
     * @param string $descripcion Nueva descripcion
     * @return bool
     */
    public function update($id, $descripcion) {
        $sql = "UPDATE archivos SET descripcion = :descripcion WHERE id = :id";
        return $this->db->execute($sql, [
            'descripcion' => sanitizeString($descripcion),
            'id' => $id
        ]) > 0;
    }

    /**
     * Mueve un archivo a la papelera (soft delete)
     *
     * @param int $id ID del archivo
     * @param int $usuarioId ID del usuario (para verificacion)
     * @return array Resultado
     */
    public function moveToTrash($id, $usuarioId) {
        $archivo = $this->findById($id);

        if (!$archivo) {
            return ['success' => false, 'error' => 'Archivo no encontrado.'];
        }

        // Verificar propietario
        if ($archivo->usuario_id != $usuarioId && !isAdmin()) {
            return ['success' => false, 'error' => 'No tienes permisos para este archivo.'];
        }

        // Actualizar en base de datos
        $sql = "UPDATE archivos
                SET en_papelera = 1, fecha_eliminacion = NOW()
                WHERE id = :id";

        if ($this->db->execute($sql, ['id' => $id]) > 0) {
            // Registrar actividad
            logActivity(
                $usuarioId,
                'file_delete',
                "Archivo movido a papelera: {$archivo->nombre_original}",
                'archivo',
                $id
            );

            return ['success' => true, 'message' => 'Archivo movido a papelera.'];
        }

        return ['success' => false, 'error' => 'Error al mover el archivo a la papelera.'];
    }

    /**
     * Restaura un archivo de la papelera
     *
     * @param int $id ID del archivo
     * @param int $usuarioId ID del usuario (para verificacion)
     * @return array Resultado
     */
    public function restore($id, $usuarioId) {
        $archivo = $this->findById($id);

        if (!$archivo) {
            return ['success' => false, 'error' => 'Archivo no encontrado.'];
        }

        // Verificar propietario
        if ($archivo->usuario_id != $usuarioId && !isAdmin()) {
            return ['success' => false, 'error' => 'No tienes permisos para este archivo.'];
        }

        // Actualizar en base de datos
        $sql = "UPDATE archivos
                SET en_papelera = 0, fecha_eliminacion = NULL
                WHERE id = :id";

        if ($this->db->execute($sql, ['id' => $id]) > 0) {
            // Registrar actividad
            logActivity(
                $usuarioId,
                'file_restore',
                "Archivo restaurado: {$archivo->nombre_original}",
                'archivo',
                $id
            );

            return ['success' => true, 'message' => 'Archivo restaurado correctamente.'];
        }

        return ['success' => false, 'error' => 'Error al restaurar el archivo.'];
    }

    /**
     * Elimina un archivo permanentemente
     *
     * @param int $id ID del archivo
     * @param int $usuarioId ID del usuario (para verificacion)
     * @return array Resultado
     */
    public function forceDelete($id, $usuarioId) {
        $archivo = $this->findById($id);

        if (!$archivo) {
            return ['success' => false, 'error' => 'Archivo no encontrado.'];
        }

        // Verificar propietario
        if ($archivo->usuario_id != $usuarioId && !isAdmin()) {
            return ['success' => false, 'error' => 'No tienes permisos para este archivo.'];
        }

        // Eliminar archivo fisico
        if (file_exists($archivo->ruta_fisica)) {
            unlink($archivo->ruta_fisica);

            // Intentar eliminar directorio vacio
            $dir = dirname($archivo->ruta_fisica);
            if (is_dir($dir) && count(scandir($dir)) == 2) {
                @rmdir($dir);
            }
        }

        // Eliminar de base de datos
        $sql = "DELETE FROM archivos WHERE id = :id";

        if ($this->db->execute($sql, ['id' => $id]) > 0) {
            // Registrar actividad
            logActivity(
                $usuarioId,
                'file_permanent_delete',
                "Archivo eliminado permanentemente: {$archivo->nombre_original}",
                'archivo',
                $id
            );

            return ['success' => true, 'message' => 'Archivo eliminado permanentemente.'];
        }

        return ['success' => false, 'error' => 'Error al eliminar el archivo.'];
    }

    //=============================================================================
    // METODOS DE CONSULTA
    //=============================================================================

    /**
     * Obtiene los archivos de una carpeta
     *
     * @param int $carpetaId ID de la carpeta
     * @param bool $includeTrash Incluir archivos en papelera
     * @return array
     */
    public function getByCarpeta($carpetaId, $includeTrash = false) {
        $sql = "SELECT * FROM archivos WHERE carpeta_id = :carpeta_id";

        if (!$includeTrash) {
            $sql .= " AND en_papelera = 0";
        }

        $sql .= " ORDER BY fecha_subida DESC";

        return $this->db->fetchAll($sql, ['carpeta_id' => $carpetaId]);
    }

    /**
     * Obtiene los archivos de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @param bool $includeTrash Incluir archivos en papelera
     * @return array
     */
    public function getByUsuario($usuarioId, $includeTrash = false) {
        $sql = "SELECT * FROM archivos WHERE usuario_id = :usuario_id";

        if (!$includeTrash) {
            $sql .= " AND en_papelera = 0";
        }

        $sql .= " ORDER BY fecha_subida DESC";

        return $this->db->fetchAll($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * Obtiene los archivos en papelera de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @return array
     */
    public function getTrashByUsuario($usuarioId) {
        $sql = "SELECT * FROM archivos
                WHERE usuario_id = :usuario_id AND en_papelera = 1
                ORDER BY fecha_eliminacion DESC";

        return $this->db->fetchAll($sql, ['usuario_id' => $usuarioId]);
    }

    /**
     * Busca archivos por nombre
     *
     * @param int $usuarioId ID del usuario
     * @param string $search Termino de busqueda
     * @return array
     */
    public function search($usuarioId, $search) {
        $sql = "SELECT * FROM archivos
                WHERE usuario_id = :usuario_id
                AND en_papelera = 0
                AND (nombre_original LIKE :search OR descripcion LIKE :search)
                ORDER BY fecha_subida DESC";

        return $this->db->fetchAll($sql, [
            'usuario_id' => $usuarioId,
            'search' => '%' . $search . '%'
        ]);
    }

    /**
     * Cuenta los archivos de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @param bool $includeTrash Incluir papelera
     * @return int
     */
    public function countByUsuario($usuarioId, $includeTrash = false) {
        $sql = "SELECT COUNT(*) as total FROM archivos WHERE usuario_id = :usuario_id";

        if (!$includeTrash) {
            $sql .= " AND en_papelera = 0";
        }

        $result = $this->db->fetchOne($sql, ['usuario_id' => $usuarioId]);
        return (int)$result['total'];
    }

    /**
     * Obtiene el tamano total de archivos de un usuario
     *
     * @param int $usuarioId ID del usuario
     * @param bool $includeTrash Incluir papelera
     * @return int Bytes
     */
    public function getTotalSizeByUsuario($usuarioId, $includeTrash = false) {
        $sql = "SELECT COALESCE(SUM(tamano_bytes), 0) as total
                FROM archivos
                WHERE usuario_id = :usuario_id";

        if (!$includeTrash) {
            $sql .= " AND en_papelera = 0";
        }

        $result = $this->db->fetchOne($sql, ['usuario_id' => $usuarioId]);
        return (int)$result['total'];
    }

    /**
     * Obtiene archivos proximos a expirar
     *
     * @param int $days Dias hasta expiracion
     * @return array
     */
    public function getExpiringSoon($days = 7) {
        $sql = "SELECT * FROM archivos
                WHERE fecha_expiracion IS NOT NULL
                AND fecha_expiracion <= DATE_ADD(NOW(), INTERVAL :days DAY)
                AND en_papelera = 0
                ORDER BY fecha_expiracion ASC";

        return $this->db->fetchAll($sql, ['days' => $days]);
    }

    //=============================================================================
    // METODOS DE DESCARGA
    //=============================================================================

    /**
     * Sirve un archivo para descarga
     *
     * @param int $id ID del archivo
     * @param int $usuarioId ID del usuario (para verificacion)
     * @return void
     */
    public function download($id, $usuarioId) {
        $archivo = $this->findById($id);

        if (!$archivo) {
            http_response_code(404);
            die('Archivo no encontrado.');
        }

        // Verificar propietario o admin
        if ($archivo->usuario_id != $usuarioId && !isAdmin()) {
            http_response_code(403);
            die('No tienes permisos para descargar este archivo.');
        }

        // Verificar que el archivo existe
        if (!file_exists($archivo->ruta_fisica)) {
            http_response_code(404);
            die('Archivo fisico no encontrado.');
        }

        // Registrar descarga
        logActivity(
            $usuarioId,
            'file_download',
            "Archivo descargado: {$archivo->nombre_original}",
            'archivo',
            $id
        );

        // Headers para descarga
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $archivo->tipo_mime);
        header('Content-Disposition: attachment; filename="' . $archivo->nombre_original . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $archivo->tamano_bytes);

        // Leer archivo
        readfile($archivo->ruta_fisica);
        exit;
    }

    //=============================================================================
    // METODOS AUXILIARES
    //=============================================================================

    /**
     * Hidrata el objeto con datos de la base de datos
     *
     * @param array $data Datos
     * @return Archivo
     */
    private function hydrate($data) {
        $archivo = new self();
        $archivo->id = (int)$data['id'];
        $archivo->carpeta_id = (int)$data['carpeta_id'];
        $archivo->usuario_id = (int)$data['usuario_id'];
        $archivo->nombre_original = $data['nombre_original'];
        $archivo->nombre_fisico = $data['nombre_fisico'];
        $archivo->tipo_mime = $data['tipo_mime'];
        $archivo->extension = $data['extension'];
        $archivo->tamano_bytes = (int)$data['tamano_bytes'];
        $archivo->ruta_fisica = $data['ruta_fisica'];
        $archivo->descripcion = $data['descripcion'];
        $archivo->en_papelera = (bool)$data['en_papelera'];
        $archivo->fecha_subida = $data['fecha_subida'];
        $archivo->fecha_eliminacion = $data['fecha_eliminacion'];
        $archivo->fecha_expiracion = $data['fecha_expiracion'];

        return $archivo;
    }

    //=============================================================================
    // GETTERS
    //=============================================================================

    public function getId() { return $this->id; }
    public function getCarpetaId() { return $this->carpeta_id; }
    public function getUsuarioId() { return $this->usuario_id; }
    public function getNombreOriginal() { return $this->nombre_original; }
    public function getNombreFisico() { return $this->nombre_fisico; }
    public function getTipoMime() { return $this->tipo_mime; }
    public function getExtension() { return $this->extension; }
    public function getTamanoBytes() { return $this->tamano_bytes; }
    public function getRutaFisica() { return $this->ruta_fisica; }
    public function getDescripcion() { return $this->descripcion; }
    public function getEnPapelera() { return $this->en_papelera; }
    public function getFechaSubida() { return $this->fecha_subida; }
    public function getFechaEliminacion() { return $this->fecha_eliminacion; }
    public function getFechaExpiracion() { return $this->fecha_expiracion; }

    /**
     * Convierte el objeto a array
     *
     * @return array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'carpeta_id' => $this->carpeta_id,
            'usuario_id' => $this->usuario_id,
            'nombre_original' => $this->nombre_original,
            'nombre_fisico' => $this->nombre_fisico,
            'tipo_mime' => $this->tipo_mime,
            'extension' => $this->extension,
            'tamano_bytes' => $this->tamano_bytes,
            'ruta_fisica' => $this->ruta_fisica,
            'descripcion' => $this->descripcion,
            'en_papelera' => $this->en_papelera,
            'fecha_subida' => $this->fecha_subida,
            'fecha_eliminacion' => $this->fecha_eliminacion,
            'fecha_expiracion' => $this->fecha_expiracion
        ];
    }
}