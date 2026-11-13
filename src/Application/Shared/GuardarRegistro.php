<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Shared;
use RumboAlCielo\Domain\Shared\Registro;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
final class GuardarRegistro {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(Registro $registro): Registro { $this->repositorio->guardar($registro); return $registro; }
}
