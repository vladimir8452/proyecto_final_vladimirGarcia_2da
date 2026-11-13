<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Familiar;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
final class ListarFamiliars {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(array $filtros=[]): array { return $this->repositorio->listar($filtros); }
}
