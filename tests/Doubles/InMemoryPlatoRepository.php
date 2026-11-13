<?php
declare(strict_types=1);

namespace RumboAlCielo\Tests\Doubles;

use RumboAlCielo\Domain\Plato\Plato;
use RumboAlCielo\Domain\Plato\PlatoRepository;

/** Repositorio en memoria para probar los casos de uso sin base de datos. */
final class InMemoryPlatoRepository implements PlatoRepository
{
    /** @var array<int, Plato> */
    private array $platos = [];
    private int $siguienteId = 1;
    /** @var array<int, string> platoId => fecha del menú más lejano que lo usa */
    private array $usoEnMenus = [];

    public function guardar(Plato $plato): void
    {
        if ($plato->id() === null) {
            $plato->asignarId($this->siguienteId++);
        }
        $this->platos[$plato->id()] = $plato;
    }

    public function buscarPorId(int $id): ?Plato
    {
        return $this->platos[$id] ?? null;
    }

    public function existeConNombreYTipo(string $nombre, string $tipo, ?int $excluirId = null): bool
    {
        foreach ($this->platos as $p) {
            if ($p->id() !== $excluirId
                && $p->tipo() === $tipo
                && mb_strtolower($p->nombre()) === mb_strtolower($nombre)) {
                return true;
            }
        }
        return false;
    }

    public function listar(?string $tipo = null, bool $soloActivos = false): array
    {
        return array_values(array_filter(
            $this->platos,
            fn(Plato $p) => ($tipo === null || $p->tipo() === $tipo) && (!$soloActivos || $p->estaActivo())
        ));
    }

    public function estaEnMenuDesde(int $platoId, string $fechaDesde): bool
    {
        return isset($this->usoEnMenus[$platoId]) && $this->usoEnMenus[$platoId] >= $fechaDesde;
    }

    /** Ayuda de prueba: simula que el plato figura en un menú de esa fecha. */
    public function simularUsoEnMenu(int $platoId, string $fecha): void
    {
        $this->usoEnMenus[$platoId] = $fecha;
    }
}
