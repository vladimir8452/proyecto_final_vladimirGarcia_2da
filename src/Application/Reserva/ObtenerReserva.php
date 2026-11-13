<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Reserva;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
use RumboAlCielo\Domain\Reserva\Reserva;
final class ObtenerReserva {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(int $id): Reserva {
        if($id<1) throw new ReglaDeNegocioException('Identificador inválido.');
        $r=$this->repositorio->buscar($id);
        if(!$r instanceof Reserva) throw new ReglaDeNegocioException('Reserva no encontrado.');
        return $r;
    }
}
