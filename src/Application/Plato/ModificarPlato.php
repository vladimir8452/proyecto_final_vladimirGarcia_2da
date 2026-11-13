<?php
declare(strict_types=1);

namespace RumboAlCielo\Application\Plato;

use RumboAlCielo\Domain\Plato\Plato;
use RumboAlCielo\Domain\Plato\PlatoRepository;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;

final class ModificarPlato
{
    private PlatoRepository $platos;

    public function __construct(PlatoRepository $platos)
    {
        $this->platos = $platos;
    }

    /** @param string $hoy fecha actual (Y-m-d) */
    public function execute(int $id, string $nombre, string $tipo, ?string $descripcion, string $hoy): Plato
    {
        $plato = $this->platos->buscarPorId($id);
        if ($plato === null) {
            throw new ReglaDeNegocioException('El plato no existe.');
        }

        $tipoAnterior = $plato->tipo();
        $plato->modificar($nombre, $tipo, $descripcion); // valida los datos nuevos

        // Cambiar el tipo de un plato que está en un menú vigente lo dejaría inconsistente.
        if ($plato->tipo() !== $tipoAnterior && $this->platos->estaEnMenuDesde($id, $hoy)) {
            throw new ReglaDeNegocioException(
                'No se puede cambiar el tipo: el plato está en un menú de hoy o de una fecha futura.'
            );
        }

        if ($this->platos->existeConNombreYTipo($plato->nombre(), $plato->tipo(), $id)) {
            throw new ReglaDeNegocioException('Ya existe un plato con ese nombre y tipo.');
        }

        $this->platos->guardar($plato);
        return $plato;
    }
}
