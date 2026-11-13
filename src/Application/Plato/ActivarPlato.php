<?php
declare(strict_types=1);

namespace RumboAlCielo\Application\Plato;

use RumboAlCielo\Domain\Plato\PlatoRepository;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;

final class ActivarPlato
{
    private PlatoRepository $platos;

    public function __construct(PlatoRepository $platos)
    {
        $this->platos = $platos;
    }

    public function execute(int $id): void
    {
        $plato = $this->platos->buscarPorId($id);
        if ($plato === null) {
            throw new ReglaDeNegocioException('El plato no existe.');
        }
        $plato->activar();
        $this->platos->guardar($plato);
    }
}
