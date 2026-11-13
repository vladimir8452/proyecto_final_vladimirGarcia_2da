<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Usuario;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
use RumboAlCielo\Domain\Usuario\Usuario;
final class ObtenerUsuario {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(int $id): Usuario {
        if($id<1) throw new ReglaDeNegocioException('Identificador inválido.');
        $r=$this->repositorio->buscar($id);
        if(!$r instanceof Usuario) throw new ReglaDeNegocioException('Usuario no encontrado.');
        return $r;
    }
}
