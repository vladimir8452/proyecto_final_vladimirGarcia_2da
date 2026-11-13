<?php
declare(strict_types=1);

namespace RumboAlCielo\Adapters\Persistence;

use PDO;
use RumboAlCielo\Domain\Plato\Plato;
use RumboAlCielo\Domain\Plato\PlatoRepository;

/** Adaptador: implementa el puerto PlatoRepository sobre MySQL con PDO. */
final class PdoPlatoRepository implements PlatoRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function guardar(Plato $plato): void
    {
        if ($plato->id() === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO platos (nombre, tipo, descripcion, activo)
                 VALUES (:nombre, :tipo, :descripcion, :activo)'
            );
            $stmt->execute([
                ':nombre'      => $plato->nombre(),
                ':tipo'        => $plato->tipo(),
                ':descripcion' => $plato->descripcion(),
                ':activo'      => $plato->estaActivo() ? 1 : 0,
            ]);
            $plato->asignarId((int) $this->db->lastInsertId());
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE platos
             SET nombre = :nombre, tipo = :tipo, descripcion = :descripcion, activo = :activo
             WHERE id = :id'
        );
        $stmt->execute([
            ':nombre'      => $plato->nombre(),
            ':tipo'        => $plato->tipo(),
            ':descripcion' => $plato->descripcion(),
            ':activo'      => $plato->estaActivo() ? 1 : 0,
            ':id'          => $plato->id(),
        ]);
    }

    public function buscarPorId(int $id): ?Plato
    {
        $stmt = $this->db->prepare('SELECT * FROM platos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $this->hidratar($fila) : null;
    }

    public function existeConNombreYTipo(string $nombre, string $tipo, ?int $excluirId = null): bool
    {
        $sql    = 'SELECT 1 FROM platos WHERE nombre = :nombre AND tipo = :tipo';
        $params = [':nombre' => $nombre, ':tipo' => $tipo];
        if ($excluirId !== null) {
            $sql .= ' AND id <> :excluir';
            $params[':excluir'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function listar(?string $tipo = null, bool $soloActivos = false): array
    {
        $condiciones = [];
        $params      = [];
        if ($tipo !== null) {
            $condiciones[] = 'tipo = :tipo';
            $params[':tipo'] = $tipo;
        }
        if ($soloActivos) {
            $condiciones[] = 'activo = 1';
        }
        $where = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';

        $stmt = $this->db->prepare(
            "SELECT * FROM platos{$where}
             ORDER BY FIELD(tipo, 'SOPA', 'PRINCIPAL', 'POSTRE'), nombre"
        );
        $stmt->execute($params);

        return array_map(fn(array $fila) => $this->hidratar($fila), $stmt->fetchAll());
    }

    public function estaEnMenuDesde(int $platoId, string $fechaDesde): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM menus
             WHERE fecha >= :desde AND publicado = 1
               AND (sopa_id = :a OR principal_id = :b OR postre_id = :c)
             LIMIT 1'
        );
        $stmt->execute([':desde' => $fechaDesde, ':a' => $platoId, ':b' => $platoId, ':c' => $platoId]);
        return (bool) $stmt->fetchColumn();
    }

    private function hidratar(array $fila): Plato
    {
        return new Plato(
            (int) $fila['id'],
            $fila['nombre'],
            $fila['tipo'],
            $fila['descripcion'],
            (bool) $fila['activo']
        );
    }
}
