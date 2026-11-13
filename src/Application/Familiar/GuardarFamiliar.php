<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Familiar;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Familiar\Familiar;
/** Caso de uso: registrar o actualizar un familiar. */
final class GuardarFamiliar {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(array $datos, ?int $id=null): Familiar {
        $entidad=new Familiar($id,$datos);
        $this->repositorio->guardar($entidad);
        return $entidad;
    }
}
