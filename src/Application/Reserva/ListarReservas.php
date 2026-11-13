<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Reserva;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
final class ListarReservas {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(array $filtros=[]): array { return $this->repositorio->listar($filtros); }
}
