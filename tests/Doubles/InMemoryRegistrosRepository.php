<?php
declare(strict_types=1);
namespace RumboAlCielo\Tests\Doubles;
use RumboAlCielo\Domain\Shared\Registro;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
final class InMemoryRegistrosRepository implements RepositorioRegistros {
    private array $registros=[]; private int $siguiente=1;
    public function guardar(Registro $registro): void {
        if ($registro->id()===null) $registro->asignarId($this->siguiente++);
        $this->registros[$registro->id()]=$registro;
    }
    public function buscar(int $id): ?Registro { return $this->registros[$id]??null; }
    public function listar(array $filtros=[]): array {
        return array_values(array_filter($this->registros, function(Registro $r) use($filtros): bool {
            foreach($filtros as $k=>$v) if (($r->atributos()[$k]??null)!=$v) return false;
            return true;
        }));
    }
    public function eliminar(int $id): bool { if(!isset($this->registros[$id])) return false; unset($this->registros[$id]); return true; }
}
