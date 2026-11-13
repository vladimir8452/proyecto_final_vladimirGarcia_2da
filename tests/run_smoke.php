<?php
declare(strict_types=1);
require_once __DIR__.'/../src/autoload.php';
use RumboAlCielo\Domain\Usuario\Usuario;
use RumboAlCielo\Domain\Pension\Pension;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
$usuario = new Usuario(1, ['nombre'=>'Ana','correo'=>'ana@example.test','contrasena'=>password_hash('demo123', PASSWORD_DEFAULT)]);
if (!password_verify('demo123', $usuario->atributos()['contrasena'])) throw new RuntimeException('Hash de contraseña inválido.');
try { new Pension(null,['usuario_id'=>1,'anio'=>2027,'mes'=>13]); throw new RuntimeException('Se aceptó mes inválido.'); }
catch (ReglaDeNegocioException $e) {}
foreach ([Usuario::class, RumboAlCielo\Domain\Familiar\Familiar::class, Pension::class, RumboAlCielo\Domain\Menu\Menu::class, RumboAlCielo\Domain\Reserva\Reserva::class, RumboAlCielo\Domain\Reporte\Reporte::class] as $clase) {
    if (!class_exists($clase)) throw new RuntimeException('Falta entidad: '.$clase);
}
echo "OK: entidades de los siete módulos, validación de pensión y hash de contraseña.\n";
