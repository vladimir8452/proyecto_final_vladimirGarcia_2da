<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Menu;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
final class ListarMenus {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(array $filtros=[]): array { return $this->repositorio->listar($filtros); }
}
