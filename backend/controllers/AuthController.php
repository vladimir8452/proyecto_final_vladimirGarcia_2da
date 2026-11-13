<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    private \RumboAlCielo\Application\Services\AuthService $useCase;
    public function __construct() { $this->useCase = \RumboAlCielo\Infrastructure\Contenedor::authService(); }
    public function login(string $correo, string $contrasena): array {
        $resultado = $this->useCase->login($correo, $contrasena);
        if (!($resultado['success'] ?? false)) return $resultado;
        $usuario = $resultado['usuario'];
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['tipo_cuenta'] = $usuario['tipo_cuenta'];
        $_SESSION['codigo_empleado'] = $usuario['codigo_empleado'];
        unset($resultado['usuario']);
        $resultado['message'] = 'Bienvenido/a, ' . $usuario['nombre'];
        $resultado['redirect'] = $usuario['tipo_cuenta'] === 'ADMIN'
            ? BASE_URL . '/frontend/admin/dashboard.php'
            : BASE_URL . '/frontend/empleado/dashboard.php';
        return $resultado;
    }
    public function logout(): void { $_SESSION = []; session_destroy(); }
}
