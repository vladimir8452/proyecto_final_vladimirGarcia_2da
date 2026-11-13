<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Reporte;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
final class ListarReportes {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(array $filtros=[]): array { return $this->repositorio->listar($filtros); }
}
