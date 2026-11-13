<?php
declare(strict_types=1);

/**
 * Suite unitaria ejecutable sin dependencias externas.
 * Ejecutar: php tests/run_unit_tests.php
 * Usa dobles en memoria; no modifica ni necesita la base de datos MySQL.
 */
// Polyfills para entornos CLI sin la extensión mbstring (XAMPP normalmente la incluye).
if (!function_exists('mb_strlen')) { function mb_strlen(string $value, ?string $encoding = null): int { return strlen($value); } }
if (!function_exists('mb_strtolower')) { function mb_strtolower(string $value, ?string $encoding = null): string { return strtolower($value); } }
require_once __DIR__ . '/../src/autoload.php';

use RumboAlCielo\Application\Services\AuthService;
use RumboAlCielo\Application\Services\EmpleadoService;
use RumboAlCielo\Application\Services\FamiliarService;
use RumboAlCielo\Application\Services\ReservaService;
use RumboAlCielo\Application\Services\ReporteService;
use RumboAlCielo\Application\Pension\GestionarInscripcionPension;
use RumboAlCielo\Application\Menu\PublicarMenuSemanal;
use RumboAlCielo\Application\Plato\RegistrarPlato;
use RumboAlCielo\Application\Plato\ModificarPlato;
use RumboAlCielo\Application\Plato\ActivarPlato;
use RumboAlCielo\Application\Plato\DesactivarPlato;
use RumboAlCielo\Application\Plato\ListarPlatos;
use RumboAlCielo\Domain\Ports\UsuarioRepository;
use RumboAlCielo\Domain\Ports\FamiliarRepository;
use RumboAlCielo\Domain\Ports\PensionRepository;
use RumboAlCielo\Domain\Ports\MenuRepository;
use RumboAlCielo\Domain\Ports\PlatoCatalogRepository;
use RumboAlCielo\Domain\Ports\CalendarioRepository;
use RumboAlCielo\Domain\Ports\ReservaRepository;
use RumboAlCielo\Domain\Ports\ConfiguracionRepository;
use RumboAlCielo\Domain\Ports\ReporteRepository;
use RumboAlCielo\Domain\Plato\PlatoRepository;
use RumboAlCielo\Domain\Plato\Plato;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;

if (!defined('HORA_LIMITE_CANCELACION')) define('HORA_LIMITE_CANCELACION', '09:00');
if (!defined('MINIMO_ALMUERZOS_MES')) define('MINIMO_ALMUERZOS_MES', 10);
if (!defined('TARIFA_PENSIONADO')) define('TARIFA_PENSIONADO', 15.00);

final class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    public function test(string $name, callable $fn): void
    {
        try { $fn(); $this->passed++; echo "[OK]   {$name}\n"; }
        catch (Throwable $e) { $this->failed++; echo "[FAIL] {$name}\n       {$e->getMessage()}\n"; }
    }
    public function same(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) throw new RuntimeException(($message ? $message . ': ' : '') . 'esperado ' . var_export($expected, true) . ', obtenido ' . var_export($actual, true));
    }
    public function true(bool $condition, string $message = 'Se esperaba una condición verdadera.'): void
    { if (!$condition) throw new RuntimeException($message); }
    public function contains(string $needle, string $haystack): void
    { $this->true(str_contains($haystack, $needle), "No se encontró '{$needle}' en '{$haystack}'."); }
    public function finish(): never
    {
        echo "\nResultado: {$this->passed} correctas; {$this->failed} fallidas.\n";
        exit($this->failed === 0 ? 0 : 1);
    }
}

final class FakeUsuarios implements UsuarioRepository
{
    public array $items = [];
    public int $nextId = 1;
    public function findById(int $id) { return $this->items[$id] ?? false; }
    public function findByEmail(string $correo) { foreach ($this->items as $u) if (($u['correo'] ?? '') === $correo) return $u; return false; }
    public function correoExiste(string $correo, int $excluirId = 0) { foreach ($this->items as $id=>$u) if ($id !== $excluirId && ($u['correo'] ?? '') === $correo) return true; return false; }
    public function ciExiste(string $ci, int $excluirId = 0) { foreach ($this->items as $id=>$u) if ($id !== $excluirId && ($u['ci'] ?? '') === $ci) return true; return false; }
    public function codigoExiste(string $codigo, int $excluirId = 0) { foreach ($this->items as $id=>$u) if ($id !== $excluirId && ($u['codigo_empleado'] ?? '') === $codigo) return true; return false; }
    public function crear(array $data) { $id=$this->nextId++; $data['id']=$id; $data['activo']=1; $this->items[$id]=$data; return $id; }
    public function actualizar(int $id, array $data) { if(isset($this->items[$id])) $this->items[$id]=array_merge($this->items[$id],$data); }
    public function cambiarEstado(int $id, int $activo) { if(isset($this->items[$id])) $this->items[$id]['activo']=$activo; }
    public function listarEmpleados() { return array_values($this->items); }
}
final class FakeFamiliares implements FamiliarRepository
{
    public array $items=[]; public int $nextId=1;
    public function listarPorTitular(int $titularId) { return array_values(array_filter($this->items,fn($f)=>(int)$f['empleado_titular_id']===$titularId)); }
    public function findById(int $id) { return $this->items[$id]??false; }
    public function ciExiste(string $ci,int $excluirId=0) { foreach($this->items as $id=>$f) if($id!==$excluirId&&$f['ci']===$ci)return true; return false; }
    public function crear(int $titularId,string $nombre,string $parentesco='',string $ci='') { $id=$this->nextId++;$this->items[$id]=['id'=>$id,'empleado_titular_id'=>$titularId,'nombre'=>$nombre,'parentesco'=>$parentesco,'ci'=>$ci,'estado'=>'ACTIVO'];return $id; }
    public function cambiarEstado(int $id,string $estado) { if(isset($this->items[$id]))$this->items[$id]['estado']=$estado; }
    public function actualizar(int $id,string $nombre,string $parentesco='',string $ci='') { if(isset($this->items[$id]))$this->items[$id]=array_merge($this->items[$id],compact('nombre','parentesco','ci')); }
}
final class FakeCalendario implements CalendarioRepository
{
    public bool $operativo=true; public int $dias=20;
    public function esDiaOperativo(string $fecha) { return $this->operativo; }
    public function diasOperativosMes(int $anio,int $mes) { return $this->dias; }
}
final class FakeConfiguracion implements ConfiguracionRepository
{
    public array $valores=[]; public bool $inscrito=false;
    public function obtener(string $clave,string $defecto) { return $this->valores[$clave]??$defecto; }
    public function tieneInscripcionPension(int $usuarioId,int $beneficiarioId,int $anio,int $mes) { return $this->inscrito; }
}
final class FakePension implements PensionRepository
{
    public int $reservas=0; public array $inscripciones=[];
    public function obtenerInscripcion(int $u,int $b,int $a,int $m): ?array { return $this->inscripciones["$u-$b-$a-$m"]??null; }
    public function familiarActivoPertenece(int $f,int $u): bool { return true; }
    public function guardarInscripcion(int $u,int $b,int $a,int $m): void { $this->inscripciones["$u-$b-$a-$m"]=['usuario_id'=>$u,'beneficiario_id'=>$b,'anio'=>$a,'mes'=>$m]; }
    public function tarifaPension(): float { return 15.0; }
    public function feriados(string $d,string $h): array { return []; }
    public function reservaExiste(int $u,int $b,string $f): bool { return false; }
    public function crearReservaPension(int $u,int $b,float $t,string $f): bool { $this->reservas++; return true; }
}
final class FakeMenus implements MenuRepository
{
    public array $guardados=[];
    public function findByFecha(string $f) { return null; }
    public function listarSemana(string $f): array { return []; }
    public function listarTodos(int $limit=30,int $offset=0): array { return []; }
    public function guardar(string $f,string $d,int $a) { $this->guardados[]=['fecha'=>$f,'descripcion'=>$d,'admin'=>$a]; return true; }
    public function eliminar(int $id) { return true; }
    public function findById(int $id) { return null; }
    public function guardarMenuConPlatos(array $datos) { $this->guardados[]=$datos; }
}
final class FakeCatalogoPlatos implements PlatoCatalogRepository
{
    public function listarCatalogo() { return [1=>['id'=>1,'nombre'=>'Sopa de maní','tipo'=>'SOPA','activo'=>1],2=>['id'=>2,'nombre'=>'Silpancho','tipo'=>'PRINCIPAL','activo'=>1],3=>['id'=>3,'nombre'=>'Fruta','tipo'=>'POSTRE','activo'=>1]]; }
}
final class FakeReservas implements ReservaRepository
{
    public array $items=[]; public int $nextId=1;
    public function crearParaTitular(int $u,string $f,string $m='ESPORADICO',float $t=18.00,bool $admin=false) { $id=$this->nextId++;$this->items[$id]=['id'=>$id,'usuario_id'=>$u,'fecha_reserva'=>$f,'modalidad'=>$m,'estado'=>'RESERVADO','tipo_beneficiario'=>'TITULAR','familiar_id'=>0];return $id; }
    public function crearParaFamiliar(int $u,int $familiarId,string $fecha,string $modalidad='ESPORADICO',float $tarifa=18.00,bool $admin=false) { $id=$this->nextId++;$this->items[$id]=['id'=>$id,'usuario_id'=>$u,'fecha_reserva'=>$fecha,'modalidad'=>$modalidad,'estado'=>'RESERVADO','tipo_beneficiario'=>'FAMILIAR','familiar_id'=>$familiarId];return $id; }
    public function crearParaInvitado(int $u,string $f,float $t) { return $this->crearParaTitular($u,$f,'ESPORADICO',$t); }
    public function cancelar(int $id) { if(isset($this->items[$id]))$this->items[$id]['estado']='CANCELADO'; }
    public function findById(int $id) { return $this->items[$id]??false; }
    public function listarPorTitular(int $u,?string $i=null,?string $f=null) { return array_values(array_filter($this->items,fn($r)=>$r['usuario_id']===$u)); }
    public function existeParaTitular(int $u,string $f) { foreach($this->items as $r)if($r['usuario_id']===$u&&$r['fecha_reserva']===$f&&$r['tipo_beneficiario']==='TITULAR')return true;return false; }
    public function existeParaFamiliar(int $familiarId,string $fecha) { foreach($this->items as $r)if($r['familiar_id']===$familiarId&&$r['fecha_reserva']===$fecha)return true;return false; }
    public function contarConsumosTitular(int $u,int $a,int $m) { return 0; }
    public function contarConsumosFamiliar(int $f,int $a,int $m) { return 0; }
    public function listarPorFecha(string $f) { return array_values(array_filter($this->items,fn($r)=>$r['fecha_reserva']===$f)); }
}
final class FakeReportes implements ReporteRepository
{
    public array $guardadas=[]; public array $pensionados=[]; public array $noPensionados=[]; public bool $inscripcion=false;
    public function datosParaReporteMensual(int $a,int $m) { return $this->pensionados; }
    public function datosNoPensionadosMensual(int $a,int $m) { return $this->noPensionados; }
    public function tieneInscripcionPension(int $u,int $b,int $a,int $m) { return $this->inscripcion; }
    public function guardar(array $fila) { $this->guardadas[]=$fila; }
    public function listar(int $a,int $m) { return $this->guardadas; }
}
final class FakePlatoRepo implements PlatoRepository
{
    public array $items=[]; public array $menus=[]; public int $next=1;
    public function guardar(Plato $plato): void { if($plato->id()===null){$plato->asignarId($this->next++);} $this->items[$plato->id()]=$plato; }
    public function buscarPorId(int $id): ?Plato { return $this->items[$id]??null; }
    public function existeConNombreYTipo(string $nombre,string $tipo,?int $excluirId=null): bool { foreach($this->items as $id=>$p)if($id!==$excluirId&&strtolower($p->nombre())===strtolower($nombre)&&$p->tipo()===$tipo)return true;return false; }
    public function listar(?string $tipo=null,bool $soloActivos=false): array { return array_values(array_filter($this->items,fn($p)=>($tipo===null||$p->tipo()===$tipo)&&(!$soloActivos||$p->estaActivo()))); }
    public function estaEnMenuDesde(int $id,string $fechaDesde): bool { return isset($this->menus[$id])&&$this->menus[$id]>=$fechaDesde; }
}

$r = new TestRunner();

// 1. USUARIOS: autenticación, validación y alta de empleado.
$r->test('Usuarios: login correcto y contraseña incorrecta', function() use($r) {
    $repo=new FakeUsuarios(); $hash=password_hash('clave123',PASSWORD_DEFAULT);
    $repo->items[1]=['id'=>1,'correo'=>'ana@test.com','contrasena'=>$hash,'activo'=>1,'nombre'=>'Ana','tipo_cuenta'=>'PENSIONADO','codigo_empleado'=>'E-01'];
    $svc=new AuthService($repo);
    $r->same(true,$svc->login('ana@test.com','clave123')['success']);
    $r->same(false,$svc->login('ana@test.com','incorrecta')['success']);
    $r->same(false,$svc->login('','')['success']);
});
$r->test('Usuarios: registrar empleado y rechazar correo duplicado', function() use($r) {
    $repo=new FakeUsuarios(); $svc=new EmpleadoService($repo);
    $data=['nombre'=>'Luis','apellidos'=>'Pérez','ci'=>'123','departamento'=>'Cocina','correo'=>'luis@test.com','contrasena'=>'clave123','tipo_cuenta'=>'NO_PENSIONADO','codigo_empleado'=>'EMP-1','celular'=>'70000000'];
    $r->same(true,$svc->registrar($data)['success']);
    $r->same(false,$svc->registrar($data)['success']);
    $r->same(false,$svc->registrar(array_merge($data,['correo'=>'correo-invalido']))['success']);
});

// 2. FAMILIARES: autorización del titular, parentesco y pertenencia.
$r->test('Familiares: registrar familiar válido y rechazar titular no pensionado', function() use($r) {
    $usuarios=new FakeUsuarios(); $usuarios->items[1]=['id'=>1,'tipo_cuenta'=>'PENSIONADO']; $usuarios->items[2]=['id'=>2,'tipo_cuenta'=>'NO_PENSIONADO'];
    $familiares=new FakeFamiliares(); $svc=new FamiliarService($familiares,$usuarios);
    $ok=$svc->registrar(1,'  María  ','hijo','CI-1');
    $r->same(true,$ok['success']); $r->same('María',$familiares->items[1]['nombre']);
    $r->same(false,$svc->registrar(2,'Pedro','HIJO','CI-2')['success']);
    $r->same(false,$svc->registrar(1,'Pedro','PRIMO','CI-3')['success']);
});
$r->test('Familiares: impedir editar o desactivar familiar ajeno', function() use($r) {
    $u=new FakeUsuarios();$u->items[1]=['id'=>1,'tipo_cuenta'=>'PENSIONADO'];$u->items[2]=['id'=>2,'tipo_cuenta'=>'PENSIONADO'];
    $f=new FakeFamiliares();$id=$f->crear(1,'Rosa','MADRE','CI-9');$svc=new FamiliarService($f,$u);
    $r->same(false,$svc->actualizar(2,$id,'Rosa','MADRE','CI-9')['success']);
    $r->same(false,$svc->desactivar(2,$id)['success']);
});

// 3. PENSIONES: mes válido, cuenta autorizada e inscripción automática.
$r->test('Pensiones: validar mes y generar reservas de inscripción', function() use($r) {
    $repo=new FakePension();$svc=new GestionarInscripcionPension($repo);
    $r->same(false,$svc->estado(1,0,2099,13)['success']);
    $r->same(false,$svc->inscribir(1,0,2099,1,'NO_PENSIONADO')['success']);
    $fecha=new DateTime('first day of next month');$anio=(int)$fecha->format('Y');$mes=(int)$fecha->format('n');
    $res=$svc->inscribir(1,0,$anio,$mes,'PENSIONADO');
    $r->same(true,$res['success']);$r->true($repo->reservas>0,'La inscripción debe crear reservas automáticas.');
});

// 4. PLATOS: alta, duplicado, cambio de estado y filtros.
$r->test('Platos: CRUD de dominio y reglas de duplicidad/menú vigente', function() use($r) {
    $repo=new FakePlatoRepo();$registrar=new RegistrarPlato($repo);
    $p=$registrar->execute('Chairo','SOPA');
    $r->same('Chairo',$p->nombre());
    try{$registrar->execute('chairo','SOPA');throw new RuntimeException('Aceptó plato duplicado.');}catch(ReglaDeNegocioException $e){}
    (new DesactivarPlato($repo))->execute($p->id(),'2026-11-13');
    $r->same(false,$repo->buscarPorId($p->id())->estaActivo());
    (new ActivarPlato($repo))->execute($p->id());
    $r->same(true,$repo->buscarPorId($p->id())->estaActivo());
    $r->same(1,count((new ListarPlatos($repo))->execute('SOPA',true)));
    $repo->menus[$p->id()]='2099-01-01';
    try{(new DesactivarPlato($repo))->execute($p->id(),'2098-12-31');throw new RuntimeException('Desactivó plato usado en menú.');}catch(ReglaDeNegocioException $e){}
});

// 5. MENÚ: publicar menú completo y rechazar platos incompletos/inactivos.
$r->test('Menú: publicar selección completa y rechazar selección incompleta', function() use($r) {
    $menus=new FakeMenus();$cal=new FakeCalendario();
    $caso=new PublicarMenuSemanal($menus,new FakeCatalogoPlatos(),$cal);
    $fecha=(new DateTime('next monday'))->format('Y-m-d');
    $ok=$caso->ejecutar([$fecha=>['sopa_id'=>1,'principal_id'=>2,'postre_id'=>3]],7);
    $r->same(1,count($menus->guardados));$r->same(0,count($ok['errores']));
    $bad=$caso->ejecutar([$fecha=>['sopa_id'=>1,'principal_id'=>0,'postre_id'=>3]],7);
    $r->same(1,count($bad['errores']));
});

// 6. RESERVAS: validar fecha, usuario, duplicados y crear reserva.
$r->test('Reservas: crear reserva futura y rechazar fecha pasada/duplicada', function() use($r) {
    $reservas=new FakeReservas();$familiares=new FakeFamiliares();$cal=new FakeCalendario();$usuarios=new FakeUsuarios();$menus=new FakeMenus();$config=new FakeConfiguracion();
    $usuarios->items[1]=['id'=>1,'tipo_cuenta'=>'PENSIONADO','activo'=>1,'correo'=>'ana@test.com'];
    $svc=new ReservaService($reservas,$familiares,$cal,$usuarios,$menus,$config);
    $r->same(false,$svc->reservarTitular(1,'2000-01-01')['success']);
    $fecha=(new DateTime('next monday'))->format('Y-m-d');
    $ok=$svc->reservarTitular(1,$fecha);$r->same(true,$ok['success']);
    $r->same(false,$svc->reservarTitular(1,$fecha)['success']);
    $r->same(1,count($reservas->items));
});

// 7. REPORTES: aplicar mínimo de pensión y sumar familiares.
$r->test('Reportes: calcular mínimo pensionado y total de familiares', function() use($r) {
    $repo=new FakeReportes();$repo->pensionados=[['id'=>1,'codigo_empleado'=>'P-1','nombre'=>'Ana','almuerzos_titular'=>8,'monto_reservas'=>60.0,'almuerzos_pension'=>8,'familiares'=>[['id'=>0,'nombre'=>'Invitado','almuerzos'=>2,'monto_reservas'=>20.0]]]];
    $cal=new FakeCalendario();$config=new FakeConfiguracion();$config->valores=['minimo_pension'=>'10','tarifa_pensionado'=>'15'];
    $svc=new ReporteService($repo,$cal,$config);$res=$svc->generarReporteMensual(2099,1);
    $r->same(true,$res['success']);$r->same(1,$res['total_empleados']);
    $r->same(150.0,$res['filas'][0]['monto_titular']);
    $r->same(170.0,$res['filas'][0]['total_descuento']);
    $r->same(1,count($repo->guardadas));
});

$r->finish();
