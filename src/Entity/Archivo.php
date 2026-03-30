<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'archivos')]
class Archivo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Carpeta::class)]
    #[ORM\JoinColumn(name: 'carpeta_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Carpeta $carpeta;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'usuario_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Usuario $usuario;

    #[ORM\Column(name: 'nombre_original', type: 'string', length: 255)]
    private string $nombreOriginal;

    #[ORM\Column(name: 'nombre_fisico', type: 'string', length: 255)]
    private string $nombreFisico;

    #[ORM\Column(name: 'tipo_mime', type: 'string', length: 100)]
    private string $tipoMime;

    #[ORM\Column(type: 'string', length: 10)]
    private string $extension;

    #[ORM\Column(name: 'tamano_bytes', type: 'bigint', options: ['unsigned' => true])]
    private string $tamanoBytes;

    #[ORM\Column(name: 'ruta_fisica', type: 'string', length: 500)]
    private string $rutaFisica;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(name: 'en_papelera', type: 'boolean', options: ['default' => false])]
    private bool $enPapelera = false;

    #[ORM\Column(name: 'fecha_subida', type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeImmutable $fechaSubida;

    #[ORM\Column(name: 'fecha_eliminacion', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaEliminacion = null;

    #[ORM\Column(name: 'fecha_expiracion', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaExpiracion = null;

    #[ORM\Column(name: 'fecha_actualizacion', type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeImmutable $fechaActualizacion;

    public function __construct()
    {
        $this->fechaSubida = new \DateTimeImmutable();
        $this->fechaActualizacion = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getCarpeta(): Carpeta { return $this->carpeta; }
    public function setCarpeta(Carpeta $carpeta): void { $this->carpeta = $carpeta; }
    public function getUsuario(): Usuario { return $this->usuario; }
    public function setUsuario(Usuario $usuario): void { $this->usuario = $usuario; }
    public function getNombreOriginal(): string { return $this->nombreOriginal; }
    public function setNombreOriginal(string $nombre): void { $this->nombreOriginal = $nombre; }
    public function getNombreFisico(): string { return $this->nombreFisico; }
    public function setNombreFisico(string $nombre): void { $this->nombreFisico = $nombre; }
    public function getTipoMime(): string { return $this->tipoMime; }
    public function setTipoMime(string $mime): void { $this->tipoMime = $mime; }
    public function getExtension(): string { return $this->extension; }
    public function setExtension(string $ext): void { $this->extension = $ext; }
    public function getTamanoBytes(): string { return $this->tamanoBytes; }
    public function setTamanoBytes(string $bytes): void { $this->tamanoBytes = $bytes; }
    public function getRutaFisica(): string { return $this->rutaFisica; }
    public function setRutaFisica(string $ruta): void { $this->rutaFisica = $ruta; }
    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $descripcion): void { $this->descripcion = $descripcion; }
    public function isEnPapelera(): bool { return $this->enPapelera; }
    public function setEnPapelera(bool $en): void { $this->enPapelera = $en; }
    public function getFechaSubida(): \DateTimeImmutable { return $this->fechaSubida; }
    public function getFechaEliminacion(): ?\DateTimeImmutable { return $this->fechaEliminacion; }
    public function setFechaEliminacion(?\DateTimeImmutable $dt): void { $this->fechaEliminacion = $dt; }
    public function getFechaExpiracion(): ?\DateTimeImmutable { return $this->fechaExpiracion; }
    public function setFechaExpiracion(?\DateTimeImmutable $dt): void { $this->fechaExpiracion = $dt; }
    public function getFechaActualizacion(): \DateTimeImmutable { return $this->fechaActualizacion; }
    public function setFechaActualizacion(\DateTimeImmutable $dt): void { $this->fechaActualizacion = $dt; }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'carpeta_id'       => $this->carpeta->getId(),
            'usuario_id'       => $this->usuario->getId(),
            'nombre_original'  => $this->nombreOriginal,
            'nombre_fisico'    => $this->nombreFisico,
            'tipo_mime'        => $this->tipoMime,
            'extension'        => $this->extension,
            'tamano_bytes'     => $this->tamanoBytes,
            'ruta_fisica'      => $this->rutaFisica,
            'descripcion'      => $this->descripcion,
            'en_papelera'      => $this->enPapelera,
            'fecha_subida'     => $this->fechaSubida->format('Y-m-d H:i:s'),
            'fecha_eliminacion'=> $this->fechaEliminacion?->format('Y-m-d H:i:s'),
            'fecha_expiracion' => $this->fechaExpiracion?->format('Y-m-d H:i:s'),
        ];
    }
}
