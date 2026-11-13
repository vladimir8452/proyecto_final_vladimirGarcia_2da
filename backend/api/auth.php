<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../controllers/AuthController.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$controller = new AuthController();

switch ($action) {
    case 'login':
        $correo    = $_POST['correo']    ?? '';
        $contrasena = $_POST['contrasena'] ?? '';
        echo json_encode($controller->login($correo, $contrasena));
        break;

    case 'logout':
        $controller->logout();
        echo json_encode(['success' => true, 'redirect' => '/index.php']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
}
