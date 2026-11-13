<?php
require_once __DIR__ . '/../../src/autoload.php';
use RumboAlCielo\Domain\Ports\UsuarioRepository;
require_once __DIR__ . '/../config/database.php';

class Usuario implements UsuarioRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /** Busca un usuario por correo para login */
    public function findByEmail(string $correo): array|false {
        $stmt = $this->db->prepare(
            'SELECT * FROM usuarios WHERE correo = :email OR ci = :ci LIMIT 1'
        );
        $stmt->execute([':email' => $correo, ':ci' => $correo]);
        return $stmt->fetch();
    }

    /** Busca un usuario por ID */
    public function findById(int $id): array|false {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, apellidos, celular, correo, ci, departamento, tipo_cuenta, codigo_empleado, activo
             FROM usuarios WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /** Lista todos los empleados (no admins) con paginación opcional */
    public function listarEmpleados(): array {
        $stmt = $this->db->query(
            'SELECT id, nombre, apellidos, celular, correo, ci, departamento, tipo_cuenta, codigo_empleado, activo, created_at
             FROM usuarios
             WHERE tipo_cuenta IN (\'PENSIONADO\',\'NO_PENSIONADO\')
             ORDER BY nombre ASC'
        );
        return $stmt->fetchAll();
    }

    /** Crea un nuevo usuario */
    public function crear(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, apellidos, celular, correo, ci, departamento, contrasena, tipo_cuenta, codigo_empleado, activo)
             VALUES (:nombre, :apellidos, :celular, :correo, :ci, :departamento, :contrasena, :tipo_cuenta, :codigo_empleado, 1)'
        );
        $stmt->execute([
            ':nombre'          => $data['nombre'],
            ':apellidos'       => $data['apellidos'] ?? null,
            ':ci'              => $data['ci'] ?? null,
            ':departamento'    => $data['departamento'] ?? null,
            ':celular'         => $data['celular'] ?? null,
            ':correo'          => $data['correo'],
            ':contrasena'      => password_hash($data['contrasena'], PASSWORD_BCRYPT),
            ':tipo_cuenta'     => $data['tipo_cuenta'],
            ':codigo_empleado' => $data['codigo_empleado'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Actualiza datos de un usuario */
    public function actualizar(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];

        foreach (['nombre', 'apellidos', 'celular', 'correo', 'ci', 'departamento', 'tipo_cuenta', 'codigo_empleado'] as $campo) {
            if (array_key_exists($campo, $data)) {
                $fields[] = "$campo = :$campo";
                $params[":$campo"] = $data[$campo];
            }
        }

        if (!empty($data['contrasena'])) {
            $fields[] = 'contrasena = :contrasena';
            $params[':contrasena'] = password_hash($data['contrasena'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) return false;

        $sql = 'UPDATE usuarios SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /** Activa o desactiva un usuario */
    public function cambiarEstado(int $id, int $activo): bool {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET activo = :activo WHERE id = :id'
        );
        $stmt->execute([':activo' => $activo, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** Verifica si un correo ya existe (excluyendo un ID opcional) */
    public function correoExiste(string $correo, int $excluirId = 0): bool {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM usuarios WHERE correo = :correo AND id != :id'
        );
        $stmt->execute([':correo' => $correo, ':id' => $excluirId]);
        return (int) $stmt->fetchColumn() > 0;
    }


    /** Verifica si un CI ya existe */
    public function ciExiste(string $ci, int $excluirId = 0): bool {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM usuarios WHERE ci = :ci AND id != :id');
        $stmt->execute([':ci' => $ci, ':id' => $excluirId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Verifica si un código de empleado ya existe */
    public function codigoExiste(string $codigo, int $excluirId = 0): bool {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM usuarios WHERE codigo_empleado = :codigo AND id != :id'
        );
        $stmt->execute([':codigo' => $codigo, ':id' => $excluirId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
