<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Reserva;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Reserva\Reserva;
/** Caso de uso: registrar o actualizar un reserva. */
final class GuardarReserva {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(array $datos, ?int $id=null): Reserva {
        $entidad=new Reserva($id,$datos);
        $this->repositorio->guardar($entidad);
        return $entidad;
    }
}
