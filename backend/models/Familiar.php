<?php
require_once __DIR__ . '/../../src/autoload.php';
use RumboAlCielo\Domain\Ports\FamiliarRepository;
require_once __DIR__ . '/../config/database.php';

class Familiar implements FamiliarRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /** Lista los familiares activos de un titular */
    public function listarPorTitular(int $titularId): array {
        $stmt = $this->db->prepare(
            'SELECT * FROM familiares
             WHERE empleado_titular_id = :tid
             ORDER BY nombre ASC'
        );
        $stmt->execute([':tid' => $titularId]);
        return $stmt->fetchAll();
    }

    /** Cuenta cuántos familiares ACTIVOS tiene un titular */
    public function contarActivosPorTitular(int $titularId): int {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM familiares
             WHERE empleado_titular_id = :tid AND estado = \'ACTIVO\''
        );
        $stmt->execute([':tid' => $titularId]);
        return (int) $stmt->fetchColumn();
    }

    /** Obtiene un familiar por ID */
    public function findById(int $id): array|false {
        $stmt = $this->db->prepare('SELECT * FROM familiares WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }


    public function ciExiste(string $ci, int $excluirId = 0): bool {
        $stmt=$this->db->prepare('SELECT COUNT(*) FROM familiares WHERE ci=:ci AND id<>:id');
        $stmt->execute([':ci'=>$ci,':id'=>$excluirId]); return (int)$stmt->fetchColumn()>0;
    }

    /** Crea un familiar */
    public function crear(int $titularId, string $nombre, string $parentesco = '', string $ci = ''): int {
        $stmt = $this->db->prepare(
            'INSERT INTO familiares (empleado_titular_id, nombre, parentesco, ci, estado)
             VALUES (:tid, :nombre, :parentesco, :ci, \'ACTIVO\')'
        );
        $stmt->execute([
            ':tid' => $titularId, ':nombre' => $nombre,
            ':parentesco' => $parentesco !== '' ? $parentesco : null,
            ':ci' => $ci !== '' ? $ci : null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Cambia el estado de un familiar */
    public function cambiarEstado(int $id, string $estado): bool {
        $stmt = $this->db->prepare(
            'UPDATE familiares SET estado = :estado WHERE id = :id'
        );
        $stmt->execute([':estado' => $estado, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** Actualiza el nombre de un familiar */
    public function actualizar(int $id, string $nombre, string $parentesco = '', string $ci = ''): bool {
        $stmt = $this->db->prepare(
            'UPDATE familiares SET nombre = :nombre, parentesco = :parentesco, ci = :ci WHERE id = :id'
        );
        $stmt->execute([
            ':nombre' => $nombre, ':id' => $id,
            ':parentesco' => $parentesco !== '' ? $parentesco : null,
            ':ci' => $ci !== '' ? $ci : null,
        ]);
        return $stmt->rowCount() > 0;
    }
}
