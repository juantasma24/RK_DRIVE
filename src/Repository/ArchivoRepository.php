<?php

namespace App\Repository;

use App\Entity\Archivo;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManager;

class ArchivoRepository
{
    public function __construct(private EntityManager $em) {}

    public function find(int $id): ?Archivo
    {
        return $this->em->find(Archivo::class, $id);
    }

    public function findActiveByUsuario(Usuario $usuario): array
    {
        return $this->em->getRepository(Archivo::class)
            ->findBy(['usuario' => $usuario, 'enPapelera' => false], ['fechaSubida' => 'DESC']);
    }

    public function findTrashByUsuario(Usuario $usuario): array
    {
        return $this->em->getRepository(Archivo::class)
            ->findBy(['usuario' => $usuario, 'enPapelera' => true], ['fechaEliminacion' => 'DESC']);
    }

    public function save(Archivo $archivo): void
    {
        $this->em->persist($archivo);
        $this->em->flush();
    }

    public function delete(Archivo $archivo): void
    {
        $this->em->remove($archivo);
        $this->em->flush();
    }

    /**
     * Archivos con info de carpeta y usuario para admin (DBAL)
     */
    public function getConInfoCompleta(array $filters = []): array
    {
        $sql = "SELECT a.*, c.nombre AS carpeta_nombre, u.nombre AS usuario_nombre, u.email AS usuario_email
                FROM archivos a
                INNER JOIN carpetas c ON a.carpeta_id = c.id
                INNER JOIN usuarios u ON a.usuario_id = u.id
                WHERE 1=1";
        $params = [];

        if (isset($filters['en_papelera'])) {
            $sql .= " AND a.en_papelera = :en_papelera";
            $params['en_papelera'] = $filters['en_papelera'] ? 1 : 0;
        }
        if (isset($filters['usuario_id'])) {
            $sql .= " AND a.usuario_id = :usuario_id";
            $params['usuario_id'] = $filters['usuario_id'];
        }

        $sql .= " ORDER BY a.fecha_subida DESC";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return $this->em->getConnection()->executeQuery($sql, $params)->fetchAllAssociative();
    }

    /**
     * Archivos de un cliente para el panel admin, con filtros opcionales (DBAL)
     *
     * Filtros soportados:
     *   en_papelera  bool   — activos (false) o en papelera (true). Sin filtro: ambos.
     *   carpeta_id   int    — limitar a una carpeta específica
     *   extension    string — p.ej. 'pdf', 'jpg'
     *   busqueda     string — búsqueda parcial en nombre_original
     *   limit        int    — máximo de resultados
     */
    public function findByUsuarioId(int $usuarioId, array $filters = []): array
    {
        $sql = "SELECT a.*, c.nombre AS carpeta_nombre
                FROM archivos a
                INNER JOIN carpetas c ON a.carpeta_id = c.id
                WHERE a.usuario_id = :usuario_id";
        $params = ['usuario_id' => $usuarioId];

        if (isset($filters['en_papelera'])) {
            $sql .= " AND a.en_papelera = :en_papelera";
            $params['en_papelera'] = $filters['en_papelera'] ? 1 : 0;
        }

        if (!empty($filters['carpeta_id'])) {
            $sql .= " AND a.carpeta_id = :carpeta_id";
            $params['carpeta_id'] = (int)$filters['carpeta_id'];
        }

        if (!empty($filters['extension'])) {
            $sql .= " AND a.extension = :extension";
            $params['extension'] = strtolower($filters['extension']);
        }

        if (!empty($filters['busqueda'])) {
            $sql .= " AND a.nombre_original LIKE :busqueda";
            $params['busqueda'] = '%' . $filters['busqueda'] . '%';
        }

        $sql .= " ORDER BY a.fecha_subida DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return $this->em->getConnection()->executeQuery($sql, $params)->fetchAllAssociative();
    }

    /**
     * Archivos recientes de un usuario con nombre de carpeta (DBAL)
     */
    public function getRecientesByUsuario(int $usuarioId, int $limit = 10): array
    {
        $sql = "SELECT a.*, c.nombre AS carpeta_nombre
                FROM archivos a
                INNER JOIN carpetas c ON a.carpeta_id = c.id
                WHERE a.usuario_id = :uid AND a.en_papelera = 0
                ORDER BY a.fecha_subida DESC
                LIMIT " . (int)$limit;

        return $this->em->getConnection()->executeQuery($sql, ['uid' => $usuarioId])->fetchAllAssociative();
    }
}
