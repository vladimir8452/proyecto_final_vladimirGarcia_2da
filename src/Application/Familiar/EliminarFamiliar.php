<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Familiar;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
final class EliminarFamiliar {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(int $id): void {
        if($id<1 || !$this->repositorio->eliminar($id)) throw new ReglaDeNegocioException('Familiar no encontrado.');
    }
}
