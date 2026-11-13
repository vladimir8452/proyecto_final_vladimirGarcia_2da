<?php
declare(strict_types=1);

namespace RumboAlCielo\Application\Plato;

use RumboAlCielo\Domain\Plato\Plato;
use RumboAlCielo\Domain\Plato\PlatoRepository;
use RumboAlCielo\Domain\Plato\TipoPlato;

final class ListarPlatos
{
    private PlatoRepository $platos;

    public function __construct(PlatoRepository $platos)
    {
        $this->platos = $platos;
    }

    /** @return Plato[] */
    public function execute(?string $tipo = null, bool $soloActivos = false): array
    {
        if ($tipo !== null && $tipo !== '') {
            $tipo = TipoPlato::validar($tipo);
        } else {
            $tipo = null;
        }
        return $this->platos->listar($tipo, $soloActivos);
    }
}
