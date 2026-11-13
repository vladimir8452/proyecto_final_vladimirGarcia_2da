<?php
declare(strict_types=1);
namespace RumboAlCielo\Infrastructure;
use RumboAlCielo\Adapters\Http\PlatoController;
use RumboAlCielo\Adapters\Http\ModuloController;
use RumboAlCielo\Adapters\Persistence\PdoPlatoRepository;
use RumboAlCielo\Adapters\Persistence\PdoRepositorioModulo;
use RumboAlCielo\Application\Plato\{ActivarPlato,DesactivarPlato,ListarPlatos,ModificarPlato,RegistrarPlato};
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
/** Raíz de composición: único punto que conoce PDO y las implementaciones concretas. */
final class Contenedor {
    private static function db(): \PDO { require_once __DIR__.'/../../backend/config/database.php'; return \Database::getConnection(); }
    public static function platoController(): PlatoController {
        $r=new PdoPlatoRepository(self::db());
        return new PlatoController(new RegistrarPlato($r),new ModificarPlato($r),new DesactivarPlato($r),new ActivarPlato($r),new ListarPlatos($r));
    }
    public static function moduloController(string $modulo): ModuloController {
        $map=[
            'usuarios'=>['usuarios','RumboAlCielo\\Domain\\Usuario\\Usuario',['nombre','apellidos','celular','correo','contrasena','tipo_cuenta','codigo_empleado','ci','departamento','activo']],
            'familiares'=>['familiares','RumboAlCielo\\Domain\\Familiar\\Familiar',['empleado_titular_id','nombre','parentesco','ci','estado']],
            'pensiones'=>['inscripciones_pension','RumboAlCielo\\Domain\\Pension\\Pension',['usuario_id','anio','mes','estado','beneficiario_id']],
            'menus'=>['menus','RumboAlCielo\\Domain\\Menu\\Menu',['fecha','sopa','plato_principal','postre','descripcion_menu','sopa_id','principal_id','postre_id','publicado','created_by']],
            'reservas'=>['reservas','RumboAlCielo\\Domain\\Reserva\\Reserva',['usuario_id','familiar_id','tipo_beneficiario','modalidad','tarifa','confirmado_por_admin','fecha_reserva','estado']],
            'reportes'=>['reportes_mensuales','RumboAlCielo\\Domain\\Reporte\\Reporte',['anio','mes','codigo_empleado','nombre_empleado','almuerzos_titular','monto_titular','detalle_familiares','almuerzos_familiares','penalizacion_familiares','total_descuento']],
        ];
        if(!isset($map[$modulo])) throw new \InvalidArgumentException('Módulo no reconocido.');
        [$tabla,$entidad,$columnas]=$map[$modulo];
        $repo=new PdoRepositorioModulo(self::db(),$tabla,$entidad,$columnas);
        $singular=['usuarios'=>'Usuario','familiares'=>'Familiar','pensiones'=>'Pension','menus'=>'Menu','reservas'=>'Reserva','reportes'=>'Reporte'][$modulo];
        $namespace='RumboAlCielo\\Application\\'.$singular.'\\';
        $clase=$singular;
        $listar=$namespace.'Listar'.$clase.'s'; $obtener=$namespace.'Obtener'.$clase; $guardar=$namespace.'Guardar'.$clase; $eliminar=$namespace.'Eliminar'.$clase;
        return new ModuloController($entidad,new $listar($repo),new $obtener($repo),new $guardar($repo),new $eliminar($repo));
    }
}
