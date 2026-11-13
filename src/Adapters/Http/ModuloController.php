<?php
declare(strict_types=1);
namespace RumboAlCielo\Adapters\Http;
use RumboAlCielo\Domain\Shared\Registro;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
/** Adaptador HTTP genérico: convierte entrada HTTP a casos de uso y oculta datos sensibles. */
final class ModuloController {
    public function __construct(private string $entidad, private object $listar, private object $obtener, private object $guardar, private object $eliminar) {}
    public function listar(array $filtros=[]): array { return $this->responder(fn()=>['success'=>true,'data'=>array_map([$this,'serializar'],$this->listar->execute($filtros))]); }
    public function obtener(int $id): array { return $this->responder(fn()=>['success'=>true,'data'=>$this->serializar($this->obtener->execute($id))]); }
    public function guardar(array $datos, ?int $id=null): array { return $this->responder(function() use($datos,$id){ return ['success'=>true,'data'=>$this->serializar($this->guardar->execute($datos,$id))]; }); }
    public function eliminar(int $id): array { return $this->responder(function() use($id){ $this->eliminar->execute($id); return ['success'=>true,'message'=>'Registro eliminado correctamente.']; }); }
    private function serializar(Registro $r): array { $a=['id'=>$r->id()]+$r->atributos(); unset($a['contrasena']); return $a; }
    private function responder(callable $fn): array { try{return $fn();}catch(ReglaDeNegocioException $e){return ['success'=>false,'message'=>$e->getMessage()];} }
}
