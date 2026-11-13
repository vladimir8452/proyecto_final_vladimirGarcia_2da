<?php
declare(strict_types=1);

namespace RumboAlCielo\Application\Plato;

use RumboAlCielo\Domain\Plato\PlatoRepository;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;

final class DesactivarPlato
{
    private PlatoRepository $platos;

    public function __construct(PlatoRepository $platos)
    {
        $this->platos = $platos;
    }

    /** @param string $hoy fecha actual (Y-m-d) */
    public function execute(int $id, string $hoy): void
    {
        $plato = $this->platos->buscarPorId($id);
        if ($plato === null) {
            throw new ReglaDeNegocioException('El plato no existe.');
        }

        if ($this->platos->estaEnMenuDesde($id, $hoy)) {
            throw new ReglaDeNegocioException(
                'No se puede desactivar: el plato está en un menú de hoy o de una fecha futura.'
            );
        }

        $plato->desactivar();
        $this->platos->guardar($plato);
    }
}
