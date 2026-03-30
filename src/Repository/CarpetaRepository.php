<?php

namespace App\Repository;

use App\Entity\Carpeta;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManager;

class CarpetaRepository
{
    public function __construct(private EntityManager $em) {}

    public function find(int $id): ?Carpeta
    {
        return $this->em->find(Carpeta::class, $id);
    }

    public function findActiveByUsuario(Usuario $usuario): array
    {
        return $this->em->getRepository(Carpeta::class)
            ->findBy(['usuario' => $usuario, 'activa' => true], ['fechaCreacion' => 'DESC']);
    }

    public function countActiveByUsuario(Usuario $usuario): int
    {
        return (int)$this->em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Carpeta::class, 'c')
            ->where('c.usuario = :u AND c.activa = true')
            ->setParameter('u', $usuario)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function belongsToUsuario(int $carpetaId, int $usuarioId): bool
    {
        $result = $this->em->createQueryBuilder()
            ->select('c.id')
            ->from(Carpeta::class, 'c')
            ->where('c.id = :cid AND IDENTITY(c.usuario) = :uid')
            ->setParameter('cid', $carpetaId)
            ->setParameter('uid', $usuarioId)
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }

    public function save(Carpeta $carpeta): void
    {
        $this->em->persist($carpeta);
        $this->em->flush();
    }

    public function delete(Carpeta $carpeta): void
    {
        $this->em->remove($carpeta);
        $this->em->flush();
    }

    /**
     * Carpetas con stats de archivos por usuario (DBAL)
     */
    public function getConStatsByUsuario(int $usuarioId): array
    {
        $sql = "SELECT c.*,
                       COUNT(a.id) AS total_archivos,
                       COALESCE(SUM(a.tamano_bytes), 0) AS tamano_total
                FROM carpetas c
                LEFT JOIN archivos a ON c.id = a.carpeta_id AND a.en_papelera = 0
                WHERE c.usuario_id = :uid AND c.activa = 1
                GROUP BY c.id
                ORDER BY c.fecha_creacion DESC";

        return $this->em->getConnection()->executeQuery($sql, ['uid' => $usuarioId])->fetchAllAssociative();
    }
}
