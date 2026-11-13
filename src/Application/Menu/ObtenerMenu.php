<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Menu;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
use RumboAlCielo\Domain\Menu\Menu;
final class ObtenerMenu {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(int $id): Menu {
        if($id<1) throw new ReglaDeNegocioException('Identificador inválido.');
        $r=$this->repositorio->buscar($id);
        if(!$r instanceof Menu) throw new ReglaDeNegocioException('Menu no encontrado.');
        return $r;
    }
}
