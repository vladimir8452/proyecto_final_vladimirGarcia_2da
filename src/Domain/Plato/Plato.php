<?php
declare(strict_types=1);

namespace RumboAlCielo\Domain\Plato;

use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;

/**
 * Entidad Plato: elemento del catálogo (sopa, principal o postre)
 * que luego se combina en un Menú. No depende de PDO ni del framework.
 */
final class Plato
{
    private const MAX_NOMBRE      = 180;
    private const MAX_DESCRIPCION = 255;

    private ?int $id;
    private string $nombre;
    private string $tipo;
    private ?string $descripcion;
    private bool $activo;

    public function __construct(
        ?int $id,
        string $nombre,
        string $tipo,
        ?string $descripcion = null,
        bool $activo = true
    ) {
        $this->id = $id;
        $this->aplicarDatos($nombre, $tipo, $descripcion);
        $this->activo = $activo;
    }

    /** Crea un plato nuevo (todavía sin id), activo por defecto. */
    public static function nuevo(string $nombre, string $tipo, ?string $descripcion = null): self
    {
        return new self(null, $nombre, $tipo, $descripcion, true);
    }

    public function modificar(string $nombre, string $tipo, ?string $descripcion): void
    {
        $this->aplicarDatos($nombre, $tipo, $descripcion);
    }

    public function desactivar(): void
    {
        $this->activo = false;
    }

    public function activar(): void
    {
        $this->activo = true;
    }

    /** Lo usa el repositorio al insertar el plato por primera vez. */
    public function asignarId(int $id): void
    {
        if ($this->id !== null) {
            throw new \LogicException('El plato ya tiene un id asignado.');
        }
        $this->id = $id;
    }

    public function id(): ?int            { return $this->id; }
    public function nombre(): string      { return $this->nombre; }
    public function tipo(): string        { return $this->tipo; }
    public function descripcion(): ?string { return $this->descripcion; }
    public function estaActivo(): bool    { return $this->activo; }

    private function aplicarDatos(string $nombre, string $tipo, ?string $descripcion): void
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new ReglaDeNegocioException('El nombre del plato es obligatorio.');
        }
        if (mb_strlen($nombre) > self::MAX_NOMBRE) {
            throw new ReglaDeNegocioException('El nombre del plato no puede superar los 180 caracteres.');
        }

        $descripcion = $descripcion === null ? null : trim($descripcion);
        if ($descripcion === '') {
            $descripcion = null;
        }
        if ($descripcion !== null && mb_strlen($descripcion) > self::MAX_DESCRIPCION) {
            throw new ReglaDeNegocioException('La descripción no puede superar los 255 caracteres.');
        }

        $this->nombre      = $nombre;
        $this->tipo        = TipoPlato::validar($tipo);
        $this->descripcion = $descripcion;
    }
}
