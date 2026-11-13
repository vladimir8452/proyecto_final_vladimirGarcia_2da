<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Usuario;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Usuario\Usuario;
/** Caso de uso: registrar o actualizar un usuario. */
final class GuardarUsuario {
    public function __construct(private RepositorioRegistros $repositorio) {}
    public function execute(array $datos, ?int $id=null): Usuario {
        if (isset($datos['contrasena']) && trim((string)$datos['contrasena']) !== '') {
            $info=password_get_info((string)$datos['contrasena']);
            if (($info['algo'] ?? null) === null || ($info['algo'] ?? 0) === 0) $datos['contrasena']=password_hash((string)$datos['contrasena'], PASSWORD_DEFAULT);
        }
        $entidad=new Usuario($id,$datos);
        $this->repositorio->guardar($entidad);
        return $entidad;
    }
}
