<?php
declare(strict_types=1);
require_once __DIR__.'/../src/autoload.php';

use RumboAlCielo\Domain\Ports\PensionRepository;
use RumboAlCielo\Domain\Ports\MenuRepository;
use RumboAlCielo\Domain\Ports\PlatoCatalogRepository;
use RumboAlCielo\Domain\Ports\CalendarioRepository;
use RumboAlCielo\Application\Pension\GestionarInscripcionPension;
use RumboAlCielo\Application\Menu\PublicarMenuSemanal;

$pensionRepo = new class implements PensionRepository {
    public int $reservas=0;
    public function obtenerInscripcion(int $u,int $b,int $a,int $m): ?array { return null; }
    public function familiarActivoPertenece(int $f,int $u): bool { return true; }
    public function guardarInscripcion(int $u,int $b,int $a,int $m): void {}
    public function tarifaPension(): float { return 15.0; }
    public function feriados(string $d,string $h): array { return []; }
    public function reservaExiste(int $u,int $b,string $f): bool { return false; }
    public function crearReservaPension(int $u,int $b,float $t,string $f): bool { $this->reservas++; return true; }
};
$pension = new GestionarInscripcionPension($pensionRepo);
$invalid = $pension->estado(1,0,2099,13);
if (($invalid['success'] ?? true) !== false) throw new RuntimeException('No validó el mes de pensión.');
$inscripcion = $pension->inscribir(1,0,2099,1,'PENSIONADO');
if (!($inscripcion['success'] ?? false) || $pensionRepo->reservas < 1) throw new RuntimeException('No generó reservas automáticas de pensión.');

$menuRepo = new class implements MenuRepository {
    public array $guardados=[];
    public function findByFecha(string $f){} public function listarSemana(string $f): array{return [];}
    public function listarTodos(int $l=30,int $o=0): array{return [];}
    public function guardar(string $f,string $d,int $a): bool{return true;}
    public function eliminar(int $id): bool{return true;} public function findById(int $id){}
    public function guardarMenuConPlatos(array $d): void{$this->guardados[]=$d;}
};
$catalogo = new class implements PlatoCatalogRepository {
    public function listarCatalogo(): array { return [1=>['id'=>1,'nombre'=>'Sopa','tipo'=>'SOPA','activo'=>1],2=>['id'=>2,'nombre'=>'Pollo','tipo'=>'PRINCIPAL','activo'=>1],3=>['id'=>3,'nombre'=>'Fruta','tipo'=>'POSTRE','activo'=>1]]; }
};
$calendario = new class implements CalendarioRepository {
    public function esDiaOperativo(string $f): bool{return true;}
    public function diasOperativosMes(int $a,int $m): int{return 20;}
};
$caso = new PublicarMenuSemanal($menuRepo,$catalogo,$calendario);
$res = $caso->ejecutar(['2099-01-05'=>['sopa_id'=>1,'principal_id'=>2,'postre_id'=>3]],7);
if (count($menuRepo->guardados)!==1 || count($res['errores'])!==0) throw new RuntimeException('No publicó menú mediante caso de uso.');
$bad = $caso->ejecutar(['2099-01-06'=>['sopa_id'=>1,'principal_id'=>0,'postre_id'=>3]],7);
if (count($bad['errores'])!==1) throw new RuntimeException('No validó selección incompleta del menú.');
echo "OK: casos de uso especializados de pensiones y menú semanal.\n";
