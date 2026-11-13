<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    private Usuario $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
    }

    /**
     * Autentica al usuario con correo y contraseña.
     * @return array ['success'=>bool, 'message'=>string, 'redirect'=>string]
     */
    public function login(string $correo, string $contrasena): array {
        $correo = trim($correo);
        $identificador = filter_var($correo, FILTER_VALIDATE_EMAIL) ? strtolower($correo) : $correo;

        if (empty($correo) || empty($contrasena)) {
            return ['success' => false, 'message' => 'Completa todos los campos.'];
        }

        $usuario = $this->usuarioModel->findByEmail($identificador);

        if (!$usuario) {
            return ['success' => false, 'message' => 'Credenciales incorrectas.'];
        }

        if (!(bool) $usuario['activo']) {
            return ['success' => false, 'message' => 'Tu cuenta está suspendida o no autorizada. Contacta al administrador.'];
        }

        if (!password_verify($contrasena, $usuario['contrasena'])) {
            return ['success' => false, 'message' => 'Credenciales incorrectas.'];
        }

        // Establecer sesión (se regenera el ID para evitar fijación de sesión)
        session_regenerate_id(true);
        $_SESSION['usuario_id']      = $usuario['id'];
        $_SESSION['nombre']          = $usuario['nombre'];
        $_SESSION['tipo_cuenta']     = $usuario['tipo_cuenta'];
        $_SESSION['codigo_empleado'] = $usuario['codigo_empleado'];

        $redirect = match ($usuario['tipo_cuenta']) {
            'ADMIN'  => BASE_URL . '/frontend/admin/dashboard.php',
            default  => BASE_URL . '/frontend/empleado/dashboard.php',
        };

        return ['success' => true, 'message' => 'Bienvenido/a, ' . $usuario['nombre'], 'redirect' => $redirect];
    }

    /** Cierra la sesión activa */
    public function logout(): void {
        $_SESSION = [];
        session_destroy();
    }
}
