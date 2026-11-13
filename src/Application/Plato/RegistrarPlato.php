<?php
declare(strict_types=1);

namespace RumboAlCielo\Application\Plato;

use RumboAlCielo\Domain\Plato\Plato;
use RumboAlCielo\Domain\Plato\PlatoRepository;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;

final class RegistrarPlato
{
    private PlatoRepository $platos;

    public function __construct(PlatoRepository $platos)
    {
        $this->platos = $platos;
    }

    public function execute(string $nombre, string $tipo, ?string $descripcion = null): Plato
    {
        $plato = Plato::nuevo($nombre, $tipo, $descripcion); // valida nombre y tipo

        if ($this->platos->existeConNombreYTipo($plato->nombre(), $plato->tipo())) {
            throw new ReglaDeNegocioException('Ya existe un plato con ese nombre y tipo.');
        }

        $this->platos->guardar($plato);
        return $plato;
    }
}
