<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'notificaciones')]
class Notificacion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'usuario_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Usuario $usuario;

    #[ORM\Column(type: 'string', length: 20)]
    private string $tipo;

    #[ORM\Column(type: 'string', length: 255)]
    private string $titulo;

    #[ORM\Column(type: 'text')]
    private string $mensaje;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $leida = false;

    #[ORM\Column(name: 'fecha_creacion', type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeImmutable $fechaCreacion;

    #[ORM\Column(name: 'fecha_lectura', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaLectura = null;

    public function __construct()
    {
        $this->fechaCreacion = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUsuario(): Usuario { return $this->usuario; }
    public function setUsuario(Usuario $usuario): void { $this->usuario = $usuario; }
    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): void { $this->tipo = $tipo; }
    public function getTitulo(): string { return $this->titulo; }
    public function setTitulo(string $titulo): void { $this->titulo = $titulo; }
    public function getMensaje(): string { return $this->mensaje; }
    public function setMensaje(string $mensaje): void { $this->mensaje = $mensaje; }
    public function isLeida(): bool { return $this->leida; }
    public function setLeida(bool $leida): void { $this->leida = $leida; }
    public function getFechaCreacion(): \DateTimeImmutable { return $this->fechaCreacion; }
    public function getFechaLectura(): ?\DateTimeImmutable { return $this->fechaLectura; }
    public function setFechaLectura(?\DateTimeImmutable $dt): void { $this->fechaLectura = $dt; }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'usuario_id'    => $this->usuario->getId(),
            'tipo'          => $this->tipo,
            'titulo'        => $this->titulo,
            'mensaje'       => $this->mensaje,
            'leida'         => $this->leida,
            'fecha_creacion'=> $this->fechaCreacion->format('Y-m-d H:i:s'),
            'fecha_lectura' => $this->fechaLectura?->format('Y-m-d H:i:s'),
        ];
    }
}
