<?php
require_once __DIR__ . '/../config/database.php';

class Menu {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /** Obtiene el menú de una fecha específica */
    public function findByFecha(string $fecha): array|false {
        $stmt = $this->db->prepare(
            'SELECT m.*, u.nombre AS publicado_por
             FROM menus m
             JOIN usuarios u ON u.id = m.created_by
             WHERE m.fecha = :fecha AND m.publicado = 1
             LIMIT 1'
        );
        $stmt->execute([':fecha' => $fecha]);
        return $stmt->fetch();
    }

    /** Lista los menús de una semana (7 días desde la fecha dada) */
    public function listarSemana(string $fechaInicio): array {
        $dt  = new DateTime($fechaInicio);
        $dt->modify('+6 days');
        $fechaFin = $dt->format('Y-m-d');
        $stmt = $this->db->prepare(
            'SELECT m.*, u.nombre AS publicado_por
             FROM menus m
             JOIN usuarios u ON u.id = m.created_by
             WHERE m.fecha BETWEEN :inicio AND :fin AND m.publicado = 1
             ORDER BY m.fecha ASC'
        );
        $stmt->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
        return $stmt->fetchAll();
    }

    /** Lista todos los menús (para admin) */
    public function listarTodos(int $limit = 30, int $offset = 0): array {
        $stmt = $this->db->prepare(
            'SELECT m.*, u.nombre AS publicado_por
             FROM menus m
             JOIN usuarios u ON u.id = m.created_by
             ORDER BY m.fecha DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Crea o actualiza un menú para una fecha */
    public function guardar(string $fecha, string $descripcion, int $adminId): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO menus (fecha, descripcion_menu, publicado, created_by)
             VALUES (:fecha, :desc, 1, :admin)
             ON DUPLICATE KEY UPDATE
               descripcion_menu = VALUES(descripcion_menu),
               created_by = VALUES(created_by)'
        );
        $stmt->execute([
            ':fecha' => $fecha,
            ':desc'  => $descripcion,
            ':admin' => $adminId,
        ]);
        return true;
    }

    /** Elimina un menú por ID */
    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare('DELETE FROM menus WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** Obtiene menú por ID */
    public function findById(int $id): array|false {
        $stmt = $this->db->prepare('SELECT * FROM menus WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}
