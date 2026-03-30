<?php

namespace App\Repository;

use App\Entity\Usuario;
use Doctrine\ORM\EntityManager;

class UsuarioRepository
{
    public function __construct(private EntityManager $em) {}

    public function find(int $id): ?Usuario
    {
        return $this->em->find(Usuario::class, $id);
    }

    public function findByEmail(string $email): ?Usuario
    {
        return $this->em->getRepository(Usuario::class)
            ->findOneBy(['email' => $email]);
    }

    public function findAllClientes(): array
    {
        return $this->em->getRepository(Usuario::class)
            ->findBy(['rol' => 'cliente'], ['fechaCreacion' => 'DESC']);
    }

    public function save(Usuario $usuario): void
    {
        $this->em->persist($usuario);
        $this->em->flush();
    }

    public function delete(Usuario $usuario): void
    {
        $this->em->remove($usuario);
        $this->em->flush();
    }

    /**
     * Clientes con estadísticas de carpetas y archivos (DBAL)
     */
    public function getClientesConStats(): array
    {
        $sql = "SELECT u.id, u.nombre, u.email, u.almacenamiento_usado, u.almacenamiento_maximo,
                       u.ultimo_acceso, u.fecha_creacion, u.activo,
                       COUNT(DISTINCT c.id) AS total_carpetas,
                       COUNT(a.id) AS total_archivos
                FROM usuarios u
                LEFT JOIN carpetas c ON u.id = c.usuario_id AND c.activa = 1
                LEFT JOIN archivos a ON u.id = a.usuario_id AND a.en_papelera = 0
                WHERE u.rol = 'cliente'
                GROUP BY u.id
                ORDER BY u.fecha_creacion DESC";

        return $this->em->getConnection()->executeQuery($sql)->fetchAllAssociative();
    }

    /**
     * Todos los usuarios con filtros (DBAL)
     */
    public function getFiltered(array $filters = []): array
    {
        $sql = "SELECT id, nombre, email, rol, almacenamiento_usado, almacenamiento_maximo,
                       activo, ultimo_acceso, fecha_creacion
                FROM usuarios WHERE 1=1";
        $params = [];

        if (isset($filters['rol'])) {
            $sql .= " AND rol = :rol";
            $params['rol'] = $filters['rol'];
        }
        if (isset($filters['activo'])) {
            $sql .= " AND activo = :activo";
            $params['activo'] = $filters['activo'] ? 1 : 0;
        }
        if (isset($filters['search'])) {
            $sql .= " AND (nombre LIKE :search OR email LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY fecha_creacion DESC";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return $this->em->getConnection()->executeQuery($sql, $params)->fetchAllAssociative();
    }
}
