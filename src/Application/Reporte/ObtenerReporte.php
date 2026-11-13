<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Reporte;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
use RumboAlCielo\Domain\Reporte\Reporte;
final class ObtenerReporte {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(int $id): Reporte {
        if($id<1) throw new ReglaDeNegocioException('Identificador inválido.');
        $r=$this->repositorio->buscar($id);
        if(!$r instanceof Reporte) throw new ReglaDeNegocioException('Reporte no encontrado.');
        return $r;
    }
}
