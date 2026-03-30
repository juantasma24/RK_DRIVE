<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'usuarios')]
class Usuario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $nombre;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(name: 'password_hash', type: 'string', length: 255)]
    private string $passwordHash;

    #[ORM\Column(type: 'string', length: 10)]
    private string $rol = 'cliente';

    #[ORM\Column(name: 'almacenamiento_usado', type: 'bigint', options: ['unsigned' => true, 'default' => 0])]
    private string $almacenamientoUsado = '0';

    #[ORM\Column(name: 'almacenamiento_maximo', type: 'bigint', options: ['unsigned' => true, 'default' => 2147483648])]
    private string $almacenamientoMaximo = '2147483648';

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $activo = true;

    #[ORM\Column(name: 'token_recuperacion', type: 'string', length: 64, nullable: true)]
    private ?string $tokenRecuperacion = null;

    #[ORM\Column(name: 'token_expiracion', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $tokenExpiracion = null;

    #[ORM\Column(name: 'intentos_login', type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    private int $intentosLogin = 0;

    #[ORM\Column(name: 'bloqueado_hasta', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $bloqueadoHasta = null;

    #[ORM\Column(name: 'ultimo_acceso', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $ultimoAcceso = null;

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
    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function setPasswordHash(string $hash): void { $this->passwordHash = $hash; }
    public function getRol(): string { return $this->rol; }
    public function setRol(string $rol): void { $this->rol = $rol; }
    public function getAlmacenamientoUsado(): string { return $this->almacenamientoUsado; }
    public function setAlmacenamientoUsado(string $bytes): void { $this->almacenamientoUsado = $bytes; }
    public function getAlmacenamientoMaximo(): string { return $this->almacenamientoMaximo; }
    public function setAlmacenamientoMaximo(string $bytes): void { $this->almacenamientoMaximo = $bytes; }
    public function isActivo(): bool { return $this->activo; }
    public function setActivo(bool $activo): void { $this->activo = $activo; }
    public function getTokenRecuperacion(): ?string { return $this->tokenRecuperacion; }
    public function setTokenRecuperacion(?string $token): void { $this->tokenRecuperacion = $token; }
    public function getTokenExpiracion(): ?\DateTimeImmutable { return $this->tokenExpiracion; }
    public function setTokenExpiracion(?\DateTimeImmutable $dt): void { $this->tokenExpiracion = $dt; }
    public function getIntentosLogin(): int { return $this->intentosLogin; }
    public function setIntentosLogin(int $n): void { $this->intentosLogin = $n; }
    public function getBloqueadoHasta(): ?\DateTimeImmutable { return $this->bloqueadoHasta; }
    public function setBloqueadoHasta(?\DateTimeImmutable $dt): void { $this->bloqueadoHasta = $dt; }
    public function getUltimoAcceso(): ?\DateTimeImmutable { return $this->ultimoAcceso; }
    public function setUltimoAcceso(?\DateTimeImmutable $dt): void { $this->ultimoAcceso = $dt; }
    public function getFechaCreacion(): \DateTimeImmutable { return $this->fechaCreacion; }
    public function getFechaActualizacion(): \DateTimeImmutable { return $this->fechaActualizacion; }
    public function setFechaActualizacion(\DateTimeImmutable $dt): void { $this->fechaActualizacion = $dt; }

    public function toArray(): array
    {
        return [
            'id'                    => $this->id,
            'nombre'                => $this->nombre,
            'email'                 => $this->email,
            'rol'                   => $this->rol,
            'almacenamiento_usado'  => $this->almacenamientoUsado,
            'almacenamiento_maximo' => $this->almacenamientoMaximo,
            'activo'                => $this->activo,
            'intentos_login'        => $this->intentosLogin,
            'bloqueado_hasta'       => $this->bloqueadoHasta?->format('Y-m-d H:i:s'),
            'ultimo_acceso'         => $this->ultimoAcceso?->format('Y-m-d H:i:s'),
            'fecha_creacion'        => $this->fechaCreacion->format('Y-m-d H:i:s'),
        ];
    }
}
