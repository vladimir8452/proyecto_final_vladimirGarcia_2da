<?php
declare(strict_types=1);
require_once __DIR__.'/../src/autoload.php';
require_once __DIR__.'/Doubles/InMemoryRegistrosRepository.php';
use RumboAlCielo\Tests\Doubles\InMemoryRegistrosRepository;
use RumboAlCielo\Application\Usuario\GuardarUsuario;
use RumboAlCielo\Application\Familiar\GuardarFamiliar;
use RumboAlCielo\Application\Pension\GuardarPension;
use RumboAlCielo\Application\Menu\GuardarMenu;
use RumboAlCielo\Application\Reserva\GuardarReserva;
use RumboAlCielo\Application\Reporte\GuardarReporte;
$repo=new InMemoryRegistrosRepository();
$casos=[
 [new GuardarUsuario($repo),['nombre'=>'Ana','correo'=>'ana@example.test','contrasena'=>'demo123','tipo_cuenta'=>'ADMIN']],
 [new GuardarFamiliar($repo),['empleado_titular_id'=>1,'nombre'=>'Luis','estado'=>'ACTIVO']],
 [new GuardarPension($repo),['usuario_id'=>1,'anio'=>2027,'mes'=>1,'estado'=>'ACTIVA','beneficiario_id'=>0]],
 [new GuardarMenu($repo),['fecha'=>'2027-01-04','created_by'=>1,'descripcion_menu'=>'Almuerzo']],
 [new GuardarReserva($repo),['fecha_reserva'=>'2027-01-04','tipo_beneficiario'=>'TITULAR','modalidad'=>'PENSIONADO']],
 [new GuardarReporte($repo),['anio'=>2027,'mes'=>1,'codigo_empleado'=>'EMP-1','nombre_empleado'=>'Ana']],
];
foreach($casos as [$caso,$datos]) { $e=$caso->execute($datos); if(!$e->id()) throw new RuntimeException('No se asignó ID'); }
if(count($repo->listar())!==6) throw new RuntimeException('No se guardaron los seis módulos');
$usuario=$repo->buscar(1)->atributos();
if(!password_verify('demo123',$usuario['contrasena'])) throw new RuntimeException('La contraseña no se guardó con hash');
try { new RumboAlCielo\Domain\Pension\Pension(null,['usuario_id'=>1,'anio'=>2027,'mes'=>13]); throw new RuntimeException('Se aceptó mes inválido'); }
catch(RumboAlCielo\Domain\Shared\ReglaDeNegocioException $e) {}
echo "OK: 6 módulos, persistencia en memoria, validación de dominio y hash de contraseña.\n";
