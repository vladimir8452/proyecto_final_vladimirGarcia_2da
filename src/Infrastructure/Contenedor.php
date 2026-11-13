<?php
declare(strict_types=1);
namespace RumboAlCielo\Infrastructure;
use RumboAlCielo\Adapters\Http\PlatoController;
use RumboAlCielo\Adapters\Persistence\PdoPlatoRepository;
use RumboAlCielo\Application\Plato\{ActivarPlato,DesactivarPlato,ListarPlatos,ModificarPlato,RegistrarPlato};
/** Raíz de composición: único punto que conoce PDO y las implementaciones concretas. */
final class Contenedor {
    private static function db(): \PDO { require_once __DIR__.'/../../backend/config/database.php'; return \Database::getConnection(); }
    public static function platoController(): PlatoController {
        $r=new PdoPlatoRepository(self::db());
        return new PlatoController(new RegistrarPlato($r),new ModificarPlato($r),new DesactivarPlato($r),new ActivarPlato($r),new ListarPlatos($r));
    }


    // Composición de los flujos especializados que conservan las reglas del sistema existente.
    public static function familiarService(): \RumboAlCielo\Application\Services\FamiliarService {
        require_once __DIR__.'/../../backend/models/Familiar.php'; require_once __DIR__.'/../../backend/models/Usuario.php';
        return new \RumboAlCielo\Application\Services\FamiliarService(new \Familiar(), new \Usuario());
    }
    public static function empleadoService(): \RumboAlCielo\Application\Services\EmpleadoService {
        require_once __DIR__.'/../../backend/models/Usuario.php';
        return new \RumboAlCielo\Application\Services\EmpleadoService(new \Usuario());
    }
    public static function authService(): \RumboAlCielo\Application\Services\AuthService {
        require_once __DIR__.'/../../backend/models/Usuario.php'; require_once __DIR__.'/../../backend/config/session.php';
        return new \RumboAlCielo\Application\Services\AuthService(new \Usuario());
    }
    public static function menuService(): \RumboAlCielo\Application\Services\MenuService {
        require_once __DIR__.'/../../backend/models/Menu.php'; require_once __DIR__.'/../../backend/models/Calendario.php';
        return new \RumboAlCielo\Application\Services\MenuService(new \Menu(), new \Calendario());
    }
    public static function reservaService(): \RumboAlCielo\Application\Services\ReservaService {
        foreach(['Reserva','Familiar','Calendario','Usuario','Menu','Configuracion'] as $m) require_once __DIR__.'/../../backend/models/'.$m.'.php';
        return new \RumboAlCielo\Application\Services\ReservaService(new \Reserva(),new \Familiar(),new \Calendario(),new \Usuario(),new \Menu(),new \Configuracion());
    }
    public static function reporteService(): \RumboAlCielo\Application\Services\ReporteService {
        foreach(['Reporte','Calendario','Configuracion'] as $m) require_once __DIR__.'/../../backend/models/'.$m.'.php';
        return new \RumboAlCielo\Application\Services\ReporteService(new \Reporte(),new \Calendario(),new \Configuracion());
    }
    public static function pensionController(): \RumboAlCielo\Adapters\Http\PensionController {
        $repo=new \RumboAlCielo\Adapters\Persistence\PdoPensionRepository(self::db());
        $useCase=new \RumboAlCielo\Application\Pension\GestionarInscripcionPension($repo);
        return new \RumboAlCielo\Adapters\Http\PensionController($useCase);
    }
    public static function publicarMenuSemanal(): \RumboAlCielo\Application\Menu\PublicarMenuSemanal {
        require_once __DIR__.'/../../backend/models/Menu.php'; require_once __DIR__.'/../../backend/models/Calendario.php'; require_once __DIR__.'/../../backend/models/PlatoCatalogo.php';
        return new \RumboAlCielo\Application\Menu\PublicarMenuSemanal(new \Menu(),new \PlatoCatalogo(),new \Calendario());
    }
}
