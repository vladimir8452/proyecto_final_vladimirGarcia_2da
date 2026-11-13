<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Menu;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
final class EliminarMenu {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(int $id): void {
        if($id<1 || !$this->repositorio->eliminar($id)) throw new ReglaDeNegocioException('Menu no encontrado.');
    }
}
