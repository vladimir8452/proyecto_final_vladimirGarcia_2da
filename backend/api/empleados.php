<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../controllers/EmpleadoController.php';

requireAuth();
requireRole('ADMIN');

$action     = $_POST['action'] ?? $_GET['action'] ?? '';
$controller = new EmpleadoController();

switch ($action) {
    case 'registrar':
        echo json_encode($controller->registrar($_POST));
        break;

    case 'actualizar':
        $id = (int) ($_POST['id'] ?? 0);
        echo json_encode($controller->actualizar($id, $_POST));
        break;

    case 'cambiar_estado':
        $id     = (int) ($_POST['id']     ?? 0);
        $activo = (int) ($_POST['activo'] ?? 0);
        echo json_encode($controller->cambiarEstado($id, $activo));
        break;

    case 'listar':
        $data = $controller->listar();
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'obtener':
        $id   = (int) ($_GET['id'] ?? 0);
        $data = $controller->obtener($id);
        echo json_encode(['success' => (bool) $data, 'data' => $data]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
}
