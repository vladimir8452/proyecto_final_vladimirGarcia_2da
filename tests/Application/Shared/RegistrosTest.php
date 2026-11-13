<?php
declare(strict_types=1);
namespace RumboAlCielo\Tests\Application\Shared;
use PHPUnit\Framework\TestCase;
use RumboAlCielo\Application\Shared\{GuardarRegistro,ListarRegistros,ObtenerRegistro};
use RumboAlCielo\Domain\Shared\{Registro,RepositorioRegistros,ReglaDeNegocioException};
use RumboAlCielo\Domain\Usuario\Usuario;
final class RegistrosTest extends TestCase {
    public function testGuardarAsignaIdYListarDevuelveEntidad(): void {
        $repo=new MemoriaRegistros(); $caso=new GuardarRegistro($repo); $u=new Usuario(null,['nombre'=>'Ana','correo'=>'ana@test.bo']);
        $caso->execute($u); self::assertSame(1,$u->id()); self::assertCount(1,(new ListarRegistros($repo))->execute());
    }
    public function testObtenerInexistenteFallaConReglaDeNegocio(): void {
        $this->expectException(ReglaDeNegocioException::class); (new ObtenerRegistro(new MemoriaRegistros()))->execute(99);
    }
}
final class MemoriaRegistros implements RepositorioRegistros {
    private array $datos=[]; private int $seq=1;
    public function guardar(Registro $registro): void { if($registro->id()===null)$registro->asignarId($this->seq++); $this->datos[$registro->id()]=$registro; }
    public function buscar(int $id): ?Registro { return $this->datos[$id]??null; }
    public function listar(array $filtros=[]): array { return array_values($this->datos); }
}
