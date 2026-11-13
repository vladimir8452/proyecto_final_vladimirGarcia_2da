<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Services;
use RumboAlCielo\Domain\Ports\UsuarioRepository;
class AuthService {
    private UsuarioRepository $usuarioModel;

    public function __construct(UsuarioRepository $usuarioModel) { $this->usuarioModel=$usuarioModel; }

    /**
     * Autentica al usuario con correo y contraseña.
     * @return array Resultado de autenticación con datos mínimos del usuario; la sesión se gestiona en el adaptador HTTP.
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

        return ['success' => true, 'message' => 'Credenciales verificadas.', 'usuario' => ['id'=>(int)$usuario['id'],'nombre'=>$usuario['nombre'],'tipo_cuenta'=>$usuario['tipo_cuenta'],'codigo_empleado'=>$usuario['codigo_empleado']]];
    }

}
