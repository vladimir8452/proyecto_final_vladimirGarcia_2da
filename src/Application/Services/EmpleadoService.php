<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Services;
use RumboAlCielo\Domain\Ports\UsuarioRepository;
class EmpleadoService {
    private UsuarioRepository $usuarioModel;

    public function __construct(UsuarioRepository $usuarioModel) { $this->usuarioModel=$usuarioModel; }

    /**
     * Registra un nuevo empleado (admin).
     */
    public function registrar(array $data): array {
        $data['nombre']         = trim($data['nombre'] ?? '');
        $data['apellidos']      = trim($data['apellidos'] ?? '');
        $data['ci']             = trim($data['ci'] ?? '');
        $data['departamento']   = trim($data['departamento'] ?? '');
        $data['correo']         = strtolower(trim($data['correo'] ?? ''));
        $data['contrasena']     = $data['contrasena'] ?? '';
        $data['tipo_cuenta']    = $data['tipo_cuenta'] ?? '';
        $data['codigo_empleado']= trim($data['codigo_empleado'] ?? '');
        $data['celular']        = trim($data['celular'] ?? '');

        // Validaciones
        if (empty($data['nombre']) || empty($data['apellidos']) || empty($data['ci']) || empty($data['departamento'])) {
            return ['success' => false, 'message' => 'Nombre, apellido, CI y departamento son obligatorios.'];
        }
        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Correo electrónico inválido.'];
        }
        if (strlen($data['contrasena']) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.'];
        }
        if (!in_array($data['tipo_cuenta'], ['PENSIONADO', 'NO_PENSIONADO'], true)) {
            return ['success' => false, 'message' => 'Tipo de cuenta inválido.'];
        }
        if (empty($data['codigo_empleado'])) {
            return ['success' => false, 'message' => 'El código de empleado es requerido.'];
        }
        if ($this->usuarioModel->correoExiste($data['correo'])) {
            return ['success' => false, 'message' => 'El correo ya está registrado.'];
        }
        if ($this->usuarioModel->ciExiste($data['ci'])) {
            return ['success' => false, 'message' => 'El CI ya se encuentra registrado.'];
        }
        if ($this->usuarioModel->codigoExiste($data['codigo_empleado'])) {
            return ['success' => false, 'message' => 'El código de empleado ya está en uso.'];
        }

        $id = $this->usuarioModel->crear($data);
        return ['success' => true, 'message' => 'Empleado registrado correctamente.', 'usuario_id' => $id];
    }

    /**
     * Actualiza datos de un empleado.
     */
    public function actualizar(int $id, array $data): array {
        if (isset($data['correo'])) {
            $data['correo'] = strtolower(trim($data['correo']));
            if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Correo inválido.'];
            }
            if ($this->usuarioModel->correoExiste($data['correo'], $id)) {
                return ['success' => false, 'message' => 'El correo ya está en uso.'];
            }
        }
        if (isset($data['ci']) && $this->usuarioModel->ciExiste(trim($data['ci']), $id)) {
            return ['success' => false, 'message' => 'El CI ya está registrado.'];
        }
        if (isset($data['codigo_empleado']) && $this->usuarioModel->codigoExiste($data['codigo_empleado'], $id)) {
            return ['success' => false, 'message' => 'El código de empleado ya está en uso.'];
        }

        $this->usuarioModel->actualizar($id, $data);
        return ['success' => true, 'message' => 'Empleado actualizado correctamente.'];
    }

    /**
     * Activa o desactiva un empleado.
     */
    public function cambiarEstado(int $id, int $activo): array {
        $this->usuarioModel->cambiarEstado($id, $activo);
        $msg = $activo ? 'Empleado activado.' : 'Empleado desactivado.';
        return ['success' => true, 'message' => $msg];
    }

    /**
     * Lista todos los empleados.
     */
    public function listar(): array {
        return $this->usuarioModel->listarEmpleados();
    }

    /**
     * Obtiene un empleado por ID.
     */
    public function obtener(int $id): array|false {
        return $this->usuarioModel->findById($id);
    }
}
