<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'configuracion_limpieza')]
class ConfiguracionLimpieza
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'dias_conservacion', type: 'integer', options: ['unsigned' => true, 'default' => 30])]
    private int $diasConservacion = 30;

    #[ORM\Column(name: 'dias_inactividad', type: 'integer', options: ['unsigned' => true, 'default' => 90])]
    private int $diasInactividad = 90;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $activa = true;

    #[ORM\Column(name: 'ultima_ejecucion', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $ultimaEjecucion = null;

    #[ORM\Column(name: 'proxima_ejecucion', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $proximaEjecucion = null;

    #[ORM\Column(name: 'fecha_creacion', type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeImmutable $fechaCreacion;

    #[ORM\Column(name: 'fecha_actualizacion', type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeImmutable $fechaActualizacion;

    public function __construct()
    {
        $this->fechaCreacion = new \DateTimeImmutable();
        $this->fechaActualizacion = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getDiasConservacion(): int { return $this->diasConservacion; }
    public function setDiasConservacion(int $dias): void { $this->diasConservacion = $dias; }
    public function getDiasInactividad(): int { return $this->diasInactividad; }
    public function setDiasInactividad(int $dias): void { $this->diasInactividad = $dias; }
    public function isActiva(): bool { return $this->activa; }
    public function setActiva(bool $activa): void { $this->activa = $activa; }
    public function getUltimaEjecucion(): ?\DateTimeImmutable { return $this->ultimaEjecucion; }
    public function setUltimaEjecucion(?\DateTimeImmutable $dt): void { $this->ultimaEjecucion = $dt; }
    public function getProximaEjecucion(): ?\DateTimeImmutable { return $this->proximaEjecucion; }
    public function setProximaEjecucion(?\DateTimeImmutable $dt): void { $this->proximaEjecucion = $dt; }
    public function getFechaCreacion(): \DateTimeImmutable { return $this->fechaCreacion; }
    public function getFechaActualizacion(): \DateTimeImmutable { return $this->fechaActualizacion; }
    public function setFechaActualizacion(\DateTimeImmutable $dt): void { $this->fechaActualizacion = $dt; }

    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'dias_conservacion'  => $this->diasConservacion,
            'dias_inactividad'   => $this->diasInactividad,
            'activa'             => $this->activa,
            'ultima_ejecucion'   => $this->ultimaEjecucion?->format('Y-m-d H:i:s'),
            'proxima_ejecucion'  => $this->proximaEjecucion?->format('Y-m-d H:i:s'),
            'fecha_creacion'     => $this->fechaCreacion->format('Y-m-d H:i:s'),
            'fecha_actualizacion'=> $this->fechaActualizacion->format('Y-m-d H:i:s'),
        ];
    }
}
