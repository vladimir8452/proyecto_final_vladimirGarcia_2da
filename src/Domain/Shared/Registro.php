<?php
declare(strict_types=1);
namespace RumboAlCielo\Domain\Shared;

/** Base para entidades persistentes. No conoce SQL, PDO ni HTTP. */
abstract class Registro
{
    protected ?int $id;
    /** @var array<string,mixed> */
    protected array $atributos;
    public function __construct(?int $id, array $atributos) {
        if ($id !== null && $id < 1) throw new ReglaDeNegocioException('Identificador inválido.');
        $this->id = $id; $this->atributos = $atributos;
    }
    public function id(): ?int { return $this->id; }
    public function atributos(): array { return $this->atributos; }
    public function asignarId(int $id): void {
        if ($this->id !== null || $id < 1) throw new \LogicException('No se puede asignar el identificador.');
        $this->id = $id;
    }
}
