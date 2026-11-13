<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Familiar;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
use RumboAlCielo\Domain\Familiar\Familiar;
final class ObtenerFamiliar {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(int $id): Familiar {
        if($id<1) throw new ReglaDeNegocioException('Identificador inválido.');
        $r=$this->repositorio->buscar($id);
        if(!$r instanceof Familiar) throw new ReglaDeNegocioException('Familiar no encontrado.');
        return $r;
    }
}
