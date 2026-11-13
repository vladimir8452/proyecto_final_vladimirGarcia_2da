<?php
declare(strict_types=1);
namespace RumboAlCielo\Adapters\Persistence;
use PDO;
use RumboAlCielo\Domain\Shared\Registro;
use RumboAlCielo\Domain\Shared\RepositorioRegistros;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;

/** Adaptador SQL genérico limitado a tablas/columnas declaradas en el contenedor. */
final class PdoRepositorioModulo implements RepositorioRegistros {
    public function __construct(private PDO $db, private string $tabla, private string $entidad, private array $columnas) {
        if (!preg_match('/^[a-z_]+$/', $tabla) || !class_exists($entidad)) throw new \InvalidArgumentException('Configuración de repositorio inválida.');
    }
    public function guardar(Registro $registro): void {
        if (!$registro instanceof $this->entidad) throw new \InvalidArgumentException('Entidad incompatible con el repositorio.');
        $datos = array_intersect_key($registro->atributos(), array_flip($this->columnas));
        if (!$datos) throw new ReglaDeNegocioException('No hay datos válidos para guardar.');
        foreach ($datos as $col => $_) if (!preg_match('/^[a-z_]+$/', $col)) throw new \InvalidArgumentException('Columna inválida.');
        if ($registro->id() === null) {
            $cols = array_keys($datos); $sql = 'INSERT INTO '.$this->tabla.' ('.implode(',', $cols).') VALUES (:'.implode(',:', $cols).')';
            $st=$this->db->prepare($sql); foreach($datos as $k=>$v) $st->bindValue(':'.$k,$v); $st->execute(); $registro->asignarId((int)$this->db->lastInsertId());
        } else {
            $sets=[]; foreach(array_keys($datos) as $k) $sets[]="$k=:$k";
            $st=$this->db->prepare('UPDATE '.$this->tabla.' SET '.implode(',',$sets).' WHERE id=:__id');
            foreach($datos as $k=>$v) $st->bindValue(':'.$k,$v); $st->bindValue(':__id',$registro->id(),PDO::PARAM_INT); $st->execute();
            if ($st->rowCount()===0 && !$this->buscar($registro->id())) throw new ReglaDeNegocioException('Registro no encontrado.');
        }
    }
    public function buscar(int $id): ?Registro {
        $st=$this->db->prepare('SELECT * FROM '.$this->tabla.' WHERE id=:id LIMIT 1'); $st->execute([':id'=>$id]); $r=$st->fetch();
        return $r ? new $this->entidad((int)$r['id'], array_intersect_key($r,array_flip($this->columnas))) : null;
    }
    public function eliminar(int $id): bool {
        if ($id < 1) throw new ReglaDeNegocioException('Identificador inválido.');
        $st=$this->db->prepare('DELETE FROM '.$this->tabla.' WHERE id=:id');
        $st->execute([':id'=>$id]);
        return $st->rowCount()>0;
    }
    public function listar(array $filtros=[]): array {
        $where=[]; $params=[];
        foreach($filtros as $k=>$v) { if (!in_array($k,$this->columnas,true)) continue; $where[]="$k=:$k"; $params[':'.$k]=$v; }
        $sql='SELECT * FROM '.$this->tabla.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY id DESC LIMIT 500';
        $st=$this->db->prepare($sql); $st->execute($params); $salida=[];
        foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r) $salida[]=new $this->entidad((int)$r['id'],array_intersect_key($r,array_flip($this->columnas)));
        return $salida;
    }
}
