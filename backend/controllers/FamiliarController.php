<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Familiar.php';
require_once __DIR__ . '/../models/Usuario.php';

class FamiliarController {
    private Familiar $familiarModel;
    private Usuario  $usuarioModel;

    public function __construct() {
        $this->familiarModel = new Familiar();
        $this->usuarioModel  = new Usuario();
    }

    /**
     * Registra un familiar para un titular pensionado.
     * Puede ser invocado por el titular pensionado o por un administrador.
     */
    public function registrar(int $titularId, string $nombre, string $parentesco, string $ci): array {
        $titular = $this->usuarioModel->findById($titularId);
        if (!$titular) {
            return ['success' => false, 'message' => 'Titular no encontrado.'];
        }

        if ($titular['tipo_cuenta'] !== 'PENSIONADO') {
            return ['success' => false, 'message' => 'Solo los empleados pensionados pueden tener familiares registrados.'];
        }

        $nombre = trim($nombre); $ci = trim($ci);
        $parentesco = strtoupper(trim($parentesco));
        if (empty($nombre) || empty($ci) || !in_array($parentesco, ['PADRE','MADRE','CONYUGE','HIJO'], true)) {
            return ['success' => false, 'message' => 'Registra nombre, CI y un parentesco de primer grado (padre, madre, cónyuge o hijo).'];
        }

        if ($this->familiarModel->ciExiste($ci)) return ['success'=>false,'message'=>'El CI ya está registrado en otro familiar.'];

        $id = $this->familiarModel->crear($titularId, $nombre, $parentesco, $ci);
        return ['success' => true, 'message' => 'Familiar registrado correctamente.', 'familiar_id' => $id];
    }

    /**
     * Actualiza el nombre de un familiar.
     */
    public function actualizar(int $titularId, int $familiarId, string $nombre, string $parentesco, string $ci): array {
        $familiar = $this->familiarModel->findById($familiarId);
        if (!$familiar || (int) $familiar['empleado_titular_id'] !== $titularId) {
            return ['success' => false, 'message' => 'Familiar no encontrado o no pertenece a tu cuenta.'];
        }

        $nombre = trim($nombre);
        if (empty($nombre)) {
            return ['success' => false, 'message' => 'El nombre es requerido.'];
        }

        $parentesco = strtoupper(trim($parentesco)); $ci = trim($ci);
        if (!in_array($parentesco, ['PADRE','MADRE','CONYUGE','HIJO'], true) || $ci === '') return ['success'=>false,'message'=>'Parentesco o CI inválido.'];
        if ($this->familiarModel->ciExiste($ci,$familiarId)) return ['success'=>false,'message'=>'El CI ya está registrado en otro familiar.'];
        $this->familiarModel->actualizar($familiarId, $nombre, $parentesco, $ci);
        return ['success' => true, 'message' => 'Familiar actualizado.'];
    }

    /**
     * Desactiva un familiar.
     */
    public function desactivar(int $titularId, int $familiarId): array {
        $familiar = $this->familiarModel->findById($familiarId);
        if (!$familiar || (int) $familiar['empleado_titular_id'] !== $titularId) {
            return ['success' => false, 'message' => 'Familiar no encontrado o no pertenece a tu cuenta.'];
        }

        $this->familiarModel->cambiarEstado($familiarId, 'INACTIVO');
        return ['success' => true, 'message' => 'Familiar desactivado correctamente.'];
    }

    /**
     * Lista todos los familiares de un titular.
     */
    public function listar(int $titularId): array {
        return $this->familiarModel->listarPorTitular($titularId);
    }

    /**
     * Registra familiar desde el panel admin.
     */
    public function registrarDesdeAdmin(int $titularId, string $nombre, string $parentesco, string $ci): array {
        return $this->registrar($titularId, $nombre, $parentesco, $ci);
    }
}
