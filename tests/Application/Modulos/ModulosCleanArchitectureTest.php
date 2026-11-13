<?php
declare(strict_types=1);
namespace RumboAlCielo\Tests\Application\Modulos;
use PHPUnit\Framework\TestCase;
use RumboAlCielo\Tests\Doubles\InMemoryRegistrosRepository;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
use RumboAlCielo\Domain\Usuario\Usuario;
use RumboAlCielo\Domain\Familiar\Familiar;
use RumboAlCielo\Domain\Pension\Pension;
use RumboAlCielo\Domain\Menu\Menu;
use RumboAlCielo\Domain\Reserva\Reserva;
use RumboAlCielo\Domain\Reporte\Reporte;
use RumboAlCielo\Application\Usuario\GuardarUsuario;
use RumboAlCielo\Application\Familiar\GuardarFamiliar;
use RumboAlCielo\Application\Pension\GuardarPension;
use RumboAlCielo\Application\Menu\GuardarMenu;
use RumboAlCielo\Application\Reserva\GuardarReserva;
use RumboAlCielo\Application\Reporte\GuardarReporte;
final class ModulosCleanArchitectureTest extends TestCase {
    public function testAllSixModulesCanBeCreatedThroughUseCases(): void {
        $repo=new InMemoryRegistrosRepository();
        $casos=[
            [new GuardarUsuario($repo),['nombre'=>'Ana','correo'=>'ana@example.test','contrasena'=>'hash','tipo_cuenta'=>'ADMIN']],
            [new GuardarFamiliar($repo),['empleado_titular_id'=>1,'nombre'=>'Luis','estado'=>'ACTIVO']],
            [new GuardarPension($repo),['usuario_id'=>1,'anio'=>2027,'mes'=>1,'estado'=>'ACTIVA','beneficiario_id'=>0]],
            [new GuardarMenu($repo),['fecha'=>'2027-01-04','created_by'=>1,'descripcion_menu'=>'Almuerzo']],
            [new GuardarReserva($repo),['fecha_reserva'=>'2027-01-04','tipo_beneficiario'=>'TITULAR','modalidad'=>'PENSIONADO']],
            [new GuardarReporte($repo),['anio'=>2027,'mes'=>1,'codigo_empleado'=>'EMP-1','nombre_empleado'=>'Ana']],
        ];
        foreach($casos as [$caso,$datos]) self::assertGreaterThan(0,$caso->execute($datos)->id());
        self::assertCount(6,$repo->listar());
    }
    public function testInvalidDataIsRejectedByDomain(): void {
        $this->expectException(ReglaDeNegocioException::class);
        new Pension(null,['usuario_id'=>1,'anio'=>2027,'mes'=>13]);
    }
    public function testModuleEntitiesAreIndependentOfInfrastructure(): void {
        foreach([Usuario::class,Familiar::class,Pension::class,Menu::class,Reserva::class,Reporte::class] as $class) {
            $ref=new \ReflectionClass($class);
            self::assertFalse($ref->hasProperty('db'));
        }
    }
}
