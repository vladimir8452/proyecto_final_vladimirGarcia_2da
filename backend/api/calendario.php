<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../controllers/CalendarioController.php';

requireAuth();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$controller = new CalendarioController();

switch ($action) {
    case 'marcar_feriado':
        requireRole('ADMIN');
        $fecha       = $_POST['fecha']       ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        echo json_encode($controller->marcarFeriado($fecha, $descripcion));
        break;

    case 'quitar_feriado':
        requireRole('ADMIN');
        $fecha = $_POST['fecha'] ?? '';
        echo json_encode($controller->quitarFeriado($fecha));
        break;

    case 'listar_feriados':
        $data = $controller->listarFeriados();
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'mes':
        $anio = (int) ($_GET['anio'] ?? date('Y'));
        $mes  = (int) ($_GET['mes']  ?? date('m'));
        $data = $controller->calendarioMes($anio, $mes);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
}
