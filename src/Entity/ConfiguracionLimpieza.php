<?php
/**
 * RK Marketing Drive - Clase ConfiguracionLimpieza
 *
 * Modelo para la gestion de la configuracion de limpieza automatica.
 *
 * @package RKMarketingDrive
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('APP_STARTED')) {
    die('Acceso directo no permitido');
}

class ConfiguracionLimpieza {
    private $db;
    private $id;
    private $dias_conservacion;
    private $dias_inactividad;
    private $activa;
    private $ultima_ejecucion;
    private $proxima_ejecucion;
    private $fecha_creacion;
    private $fecha_actualizacion;

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
     * Obtiene la configuracion actual
     *
     * @return ConfiguracionLimpieza|null
     */
    public function getConfig() {
        $sql = "SELECT * FROM configuracion_limpieza ORDER BY id DESC LIMIT 1";
        $data = $this->db->fetchOne($sql);

        if ($data) {
            return $this->hydrate($data);
        }

        return null;
    }

    /**
     * Actualiza la configuracion
     *
     * @param array $data Datos a actualizar
     * @return bool
     */
    public function update($data) {
        $config = $this->getConfig();

        if (!$config) {
            // Crear configuracion por defecto
            return $this->create($data);
        }

        $allowedFields = ['dias_conservacion', 'dias_inactividad', 'activa'];
        $updates = [];
        $params = ['id' => $config->id];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'dias_conservacion':
                    case 'dias_inactividad':
                        $updates[] = "{$field} = :{$field}";
                        $params[$field] = (int)$data[$field];
                        break;
                    case 'activa':
                        $updates[] = "{$field} = :{$field}";
                        $params[$field] = $data[$field] ? 1 : 0;
                        break;
                }
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE configuracion_limpieza SET " . implode(', ', $updates) . " WHERE id = :id";
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Crea la configuracion inicial
     *
     * @param array $data Datos de la configuracion
     * @return bool
     */
    private function create($data) {
        $sql = "INSERT INTO configuracion_limpieza
                (dias_conservacion, dias_inactividad, activa)
                VALUES (:dias_conservacion, :dias_inactividad, :activa)";

        return $this->db->insert($sql, [
            'dias_conservacion' => $data['dias_conservacion'] ?? DEFAULT_CONSERVATION_DAYS,
            'dias_inactividad' => $data['dias_inactividad'] ?? 90,
            'activa' => isset($data['activa']) ? ($data['activa'] ? 1 : 0) : 1
        ]) > 0;
    }

    //=============================================================================
    // EJECUCION DE LIMPIEZA
    //=============================================================================

    /**
     * Ejecuta la limpieza automatica
     *
     * @return array Resultado de la limpieza
     */
    public function ejecutarLimpieza() {
        $config = $this->getConfig();

        if (!$config || !$config->activa) {
            return [
                'success' => false,
                'message' => 'La limpieza automatica no esta activa.'
            ];
        }

        $resultados = [
            'archivos_eliminados' => 0,
            'espacio_liberado' => 0,
            'errores' => []
        ];

        try {
            // 1. Eliminar archivos de la papelera con mas de X dias
            $archivosEliminados = $this->eliminarArchivosPapelera($config->dias_conservacion);
            $resultados['archivos_eliminados'] = $archivosEliminados['count'];
            $resultados['espacio_liberado'] = $archivosEliminados['size'];

            // 2. Alertar sobre usuarios inactivos
            $usuariosInactivos = $this->alertarUsuariosInactivos($config->dias_inactividad);
            $resultados['usuarios_inactivos'] = $usuariosInactivos;

            // 3. Actualizar ultima ejecucion
            $this->actualizarUltimaEjecucion();

            // 4. Registrar en log
            $log = new LogActividad();
            $log->logLimpieza($resultados['archivos_eliminados'], $resultados['espacio_liberado']);

            // 5. Notificar a administradores
            $notificacion = new Notificacion();
            $notificacion->notifyAdmins(
                'limpieza',
                "Limpieza automatica ejecutada. Archivos eliminados: {$resultados['archivos_eliminados']}, Espacio liberado: " . formatFileSize($resultados['espacio_liberado'])
            );

            $resultados['success'] = true;
            $resultados['message'] = 'Limpieza automatica ejecutada correctamente.';

        } catch (Exception $e) {
            $resultados['success'] = false;
            $resultados['message'] = 'Error al ejecutar la limpieza automatica.';
            $resultados['errores'][] = $e->getMessage();

            logMessage('error', 'Error en limpieza automatica', ['error' => $e->getMessage()]);
        }

        return $resultados;
    }

    /**
     * Elimina archivos de la papelera con mas de X dias
     *
     * @param int $dias Dias de conservacion
     * @return array
     */
    private function eliminarArchivosPapelera($dias) {
        // Obtener archivos a eliminar
        $sql = "SELECT id, ruta_fisica, tamano_bytes, nombre_original, usuario_id
                FROM archivos
                WHERE en_papelera = 1
                AND fecha_eliminacion < DATE_SUB(NOW(), INTERVAL :dias DAY)";

        $archivos = $this->db->fetchAll($sql, ['dias' => $dias]);

        $count = 0;
        $size = 0;
        $archivoModel = new Archivo();

        foreach ($archivos as $archivo) {
            // Eliminar archivo fisico
            if (file_exists($archivo['ruta_fisica'])) {
                unlink($archivo['ruta_fisica']);
            }

            // Eliminar de base de datos
            $sql = "DELETE FROM archivos WHERE id = :id";
            $this->db->execute($sql, ['id' => $archivo['id']]);

            // Actualizar almacenamiento del usuario
            $usuario = new Usuario();
            $usuario->updateStorage($archivo['usuario_id'], $archivo['tamano_bytes'], false);

            $count++;
            $size += $archivo['tamano_bytes'];

            // Log individual
            logActivity(
                $archivo['usuario_id'],
                'file_auto_delete',
                "Archivo eliminado automaticamente: {$archivo['nombre_original']}",
                'archivo',
                $archivo['id']
            );
        }

        return ['count' => $count, 'size' => $size];
    }

    /**
     * Alerta sobre usuarios inactivos
     *
     * @param int $dias Dias de inactividad
     * @return int Numero de usuarios alertados
     */
    private function alertarUsuariosInactivos($dias) {
        // Obtener usuarios inactivos
        $sql = "SELECT id, nombre, email, ultimo_acceso
                FROM usuarios
                WHERE rol = 'cliente'
                AND activo = 1
                AND ultimo_acceso < DATE_SUB(NOW(), INTERVAL :dias DAY)";

        $usuarios = $this->db->fetchAll($sql, ['dias' => $dias]);

        $notificacion = new Notificacion();
        $count = 0;

        foreach ($usuarios as $usuario) {
            // Crear notificacion
            $notificacion->create([
                'usuario_id' => $usuario['id'],
                'tipo' => 'alerta',
                'titulo' => 'Cuenta inactiva',
                'mensaje' => "Tu cuenta lleva mas de {$dias} dias sin actividad. Por favor, accede a la plataforma para mantener tus archivos."
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Actualiza la fecha de ultima ejecucion
     */
    private function actualizarUltimaEjecucion() {
        $sql = "UPDATE configuracion_limpieza
                SET ultima_ejecucion = NOW(),
                    proxima_ejecucion = DATE_ADD(NOW(), INTERVAL dias_conservacion DAY)";
        $this->db->execute($sql);
    }

    /**
     * Verifica si es momento de ejecutar la limpieza
     *
     * @return bool
     */
    public function debeEjecutar() {
        $config = $this->getConfig();

        if (!$config || !$config->activa) {
            return false;
        }

        // Si nunca se ha ejecutado
        if (empty($config->ultima_ejecucion)) {
            return true;
        }

        // Verificar si ha pasado el tiempo de conservacion
        $ultimaEjecucion = new DateTime($config->ultima_ejecucion);
        $ahora = new DateTime();
        $diferencia = $ahora->diff($ultimaEjecucion);

        return $diferencia->days >= $config->dias_conservacion;
    }

    //=============================================================================
    // ESTADISTICAS
    //=============================================================================

    /**
     * Obtiene estadisticas de limpieza
     *
     * @return array
     */
    public function getEstadisticas() {
        // Archivos en papelera
        $sql = "SELECT COUNT(*) as total, COALESCE(SUM(tamano_bytes), 0) as tamano
                FROM archivos WHERE en_papelera = 1";
        $papelera = $this->db->fetchOne($sql);

        // Archivos proximos a expirar
        $sql = "SELECT COUNT(*) as total, COALESCE(SUM(tamano_bytes), 0) as tamano
                FROM archivos
                WHERE en_papelera = 1
                AND fecha_eliminacion < DATE_ADD(NOW(), INTERVAL 7 DAY)";
        $proximosExpirar = $this->db->fetchOne($sql);

        // Espacio total usado
        $sql = "SELECT COALESCE(SUM(almacenamiento_usado), 0) as total FROM usuarios WHERE rol = 'cliente'";
        $espacioTotal = $this->db->fetchOne($sql);

        // Usuarios inactivos
        $sql = "SELECT COUNT(*) as total FROM usuarios
                WHERE rol = 'cliente' AND activo = 1
                AND ultimo_acceso < DATE_SUB(NOW(), INTERVAL 90 DAY)";
        $usuariosInactivos = $this->db->fetchOne($sql);

        return [
            'archivos_papelera' => (int)$papelera['total'],
            'tamano_papelera' => (int)$papelera['tamano'],
            'proximos_expirar' => (int)$proximosExpirar['total'],
            'tamano_proximos_expirar' => (int)$proximosExpirar['tamano'],
            'espacio_total_usado' => (int)$espacioTotal['total'],
            'usuarios_inactivos' => (int)$usuariosInactivos['total'],
            'config' => $this->getConfig() ? $this->getConfig()->toArray() : null
        ];
    }

    //=============================================================================
    // METODOS AUXILIARES
    //=============================================================================

    /**
     * Hidrata el objeto con datos de la base de datos
     *
     * @param array $data Datos
     * @return ConfiguracionLimpieza
     */
    private function hydrate($data) {
        $config = new self();
        $config->id = (int)$data['id'];
        $config->dias_conservacion = (int)$data['dias_conservacion'];
        $config->dias_inactividad = (int)$data['dias_inactividad'];
        $config->activa = (bool)$data['activa'];
        $config->ultima_ejecucion = $data['ultima_ejecucion'];
        $config->proxima_ejecucion = $data['proxima_ejecucion'];
        $config->fecha_creacion = $data['fecha_creacion'];
        $config->fecha_actualizacion = $data['fecha_actualizacion'];

        return $config;
    }

    //=============================================================================
    // GETTERS
    //=============================================================================

    public function getId() { return $this->id; }
    public function getDiasConservacion() { return $this->dias_conservacion; }
    public function getDiasInactividad() { return $this->dias_inactividad; }
    public function getActiva() { return $this->activa; }
    public function getUltimaEjecucion() { return $this->ultima_ejecucion; }
    public function getProximaEjecucion() { return $this->proxima_ejecucion; }
    public function getFechaCreacion() { return $this->fecha_creacion; }
    public function getFechaActualizacion() { return $this->fecha_actualizacion; }

    /**
     * Convierte el objeto a array
     *
     * @return array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'dias_conservacion' => $this->dias_conservacion,
            'dias_inactividad' => $this->dias_inactividad,
            'activa' => $this->activa,
            'ultima_ejecucion' => $this->ultima_ejecucion,
            'proxima_ejecucion' => $this->proxima_ejecucion,
            'fecha_creacion' => $this->fecha_creacion,
            'fecha_actualizacion' => $this->fecha_actualizacion
        ];
    }
}