<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Pension;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
use RumboAlCielo\Domain\Pension\Pension;
final class ObtenerPension {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(int $id): Pension {
        if($id<1) throw new ReglaDeNegocioException('Identificador inválido.');
        $r=$this->repositorio->buscar($id);
        if(!$r instanceof Pension) throw new ReglaDeNegocioException('Pension no encontrado.');
        return $r;
    }
}
