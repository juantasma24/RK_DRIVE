<?php

namespace App\Repository;

use App\Entity\ConfiguracionLimpieza;
use Doctrine\ORM\EntityManager;

class ConfiguracionLimpiezaRepository
{
    public function __construct(private EntityManager $em) {}

    public function getConfig(): ?ConfiguracionLimpieza
    {
        return $this->em->getRepository(ConfiguracionLimpieza::class)
            ->findOneBy([], ['id' => 'DESC']);
    }

    public function save(ConfiguracionLimpieza $config): void
    {
        $this->em->persist($config);
        $this->em->flush();
    }
}
