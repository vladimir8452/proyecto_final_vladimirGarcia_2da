<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Reporte;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Reporte\Reporte;
/** Caso de uso: registrar o actualizar un reporte. */
final class GuardarReporte {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(array $datos, ?int $id=null): Reporte {
        $entidad=new Reporte($id,$datos);
        $this->repositorio->guardar($entidad);
        return $entidad;
    }
}
