<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'logs_actividad')]
class LogActividad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'usuario_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Usuario $usuario = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $accion;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(name: 'entidad_tipo', type: 'string', length: 50, nullable: true)]
    private ?string $entidadTipo = null;

    #[ORM\Column(name: 'entidad_id', type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $entidadId = null;

    #[ORM\Column(name: 'ip_address', type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(name: 'user_agent', type: 'text', nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(name: 'datos_adicionales', type: 'json', nullable: true)]
    private ?array $datosAdicionales = null;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeImmutable $fecha;

    public function __construct()
    {
        $this->fecha = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUsuario(): ?Usuario { return $this->usuario; }
    public function setUsuario(?Usuario $usuario): void { $this->usuario = $usuario; }
    public function getAccion(): string { return $this->accion; }
    public function setAccion(string $accion): void { $this->accion = $accion; }
    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $descripcion): void { $this->descripcion = $descripcion; }
    public function getEntidadTipo(): ?string { return $this->entidadTipo; }
    public function setEntidadTipo(?string $tipo): void { $this->entidadTipo = $tipo; }
    public function getEntidadId(): ?int { return $this->entidadId; }
    public function setEntidadId(?int $id): void { $this->entidadId = $id; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function setIpAddress(?string $ip): void { $this->ipAddress = $ip; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function setUserAgent(?string $ua): void { $this->userAgent = $ua; }
    public function getDatosAdicionales(): ?array { return $this->datosAdicionales; }
    public function setDatosAdicionales(?array $datos): void { $this->datosAdicionales = $datos; }
    public function getFecha(): \DateTimeImmutable { return $this->fecha; }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'usuario_id'        => $this->usuario?->getId(),
            'accion'            => $this->accion,
            'descripcion'       => $this->descripcion,
            'entidad_tipo'      => $this->entidadTipo,
            'entidad_id'        => $this->entidadId,
            'ip_address'        => $this->ipAddress,
            'datos_adicionales' => $this->datosAdicionales,
            'fecha'             => $this->fecha->format('Y-m-d H:i:s'),
        ];
    }
}
