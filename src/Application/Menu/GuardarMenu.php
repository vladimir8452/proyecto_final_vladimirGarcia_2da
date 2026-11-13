<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Menu;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Menu\Menu;
/** Caso de uso: registrar o actualizar un menu. */
final class GuardarMenu {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(array $datos, ?int $id=null): Menu {
        $entidad=new Menu($id,$datos);
        $this->repositorio->guardar($entidad);
        return $entidad;
    }
}
