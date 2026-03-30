<?php

namespace App\Repository;

use App\Entity\Notificacion;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManager;

class NotificacionRepository
{
    public function __construct(private EntityManager $em) {}

    public function find(int $id): ?Notificacion
    {
        return $this->em->find(Notificacion::class, $id);
    }

    public function findByUsuario(Usuario $usuario, bool $soloNoLeidas = false, int $limit = 50): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('n')
            ->from(Notificacion::class, 'n')
            ->where('n.usuario = :u')
            ->setParameter('u', $usuario)
            ->orderBy('n.fechaCreacion', 'DESC')
            ->setMaxResults($limit);

        if ($soloNoLeidas) {
            $qb->andWhere('n.leida = false');
        }

        return $qb->getQuery()->getResult();
    }

    public function countUnreadByUsuario(Usuario $usuario): int
    {
        return (int)$this->em->createQueryBuilder()
            ->select('COUNT(n.id)')
            ->from(Notificacion::class, 'n')
            ->where('n.usuario = :u AND n.leida = false')
            ->setParameter('u', $usuario)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllAsRead(Usuario $usuario): void
    {
        $this->em->createQueryBuilder()
            ->update(Notificacion::class, 'n')
            ->set('n.leida', 'true')
            ->set('n.fechaLectura', ':now')
            ->where('n.usuario = :u AND n.leida = false')
            ->setParameter('u', $usuario)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function save(Notificacion $notificacion): void
    {
        $this->em->persist($notificacion);
        $this->em->flush();
    }

    public function delete(Notificacion $notificacion): void
    {
        $this->em->remove($notificacion);
        $this->em->flush();
    }

    public function deleteAllByUsuario(Usuario $usuario): void
    {
        $this->em->createQueryBuilder()
            ->delete(Notificacion::class, 'n')
            ->where('n.usuario = :u')
            ->setParameter('u', $usuario)
            ->getQuery()
            ->execute();
    }
}
