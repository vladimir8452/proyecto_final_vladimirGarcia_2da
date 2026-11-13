<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Shared;
use RumboAlCielo\Domain\Shared\Registro;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
final class ObtenerRegistro {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(int $id): Registro { if ($id < 1) throw new ReglaDeNegocioException('Identificador inválido.'); return $this->repositorio->buscar($id) ?? throw new ReglaDeNegocioException('Registro no encontrado.'); }
}
