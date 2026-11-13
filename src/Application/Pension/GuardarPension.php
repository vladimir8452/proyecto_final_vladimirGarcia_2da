<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Pension;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Pension\Pension;
/** Caso de uso: registrar o actualizar un pension. */
final class GuardarPension {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(array $datos, ?int $id=null): Pension {
        $entidad=new Pension($id,$datos);
        $this->repositorio->guardar($entidad);
        return $entidad;
    }
}
