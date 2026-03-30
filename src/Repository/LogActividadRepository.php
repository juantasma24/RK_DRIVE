<?php

namespace App\Repository;

use App\Entity\LogActividad;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManager;

class LogActividadRepository
{
    public function __construct(private EntityManager $em) {}

    public function save(LogActividad $log): void
    {
        $this->em->persist($log);
        $this->em->flush();
    }

    /**
     * Logs con nombre de usuario para admin (DBAL, soporta filtros dinámicos)
     */
    public function getConUsuario(array $filters = [], int $limit = 100): array
    {
        $sql = "SELECT l.*, u.nombre AS usuario_nombre, u.email AS usuario_email
                FROM logs_actividad l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['accion'])) {
            $sql .= " AND l.accion = :accion";
            $params['accion'] = $filters['accion'];
        }
        if (!empty($filters['usuario_id'])) {
            $sql .= " AND l.usuario_id = :usuario_id";
            $params['usuario_id'] = $filters['usuario_id'];
        }
        if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
            $sql .= " AND DATE(l.fecha) BETWEEN :fecha_inicio AND :fecha_fin";
            $params['fecha_inicio'] = $filters['fecha_inicio'];
            $params['fecha_fin']    = $filters['fecha_fin'];
        }

        $sql .= " ORDER BY l.fecha DESC LIMIT " . (int)$limit;

        return $this->em->getConnection()->executeQuery($sql, $params)->fetchAllAssociative();
    }

    public function getByUsuario(Usuario $usuario, int $limit = 100): array
    {
        return $this->em->createQueryBuilder()
            ->select('l')
            ->from(LogActividad::class, 'l')
            ->where('l.usuario = :u')
            ->setParameter('u', $usuario)
            ->orderBy('l.fecha', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
