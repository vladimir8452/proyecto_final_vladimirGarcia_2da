<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../controllers/ReservaController.php';

requireAuth();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$controller = new ReservaController();
$titularId = (int) $_SESSION['usuario_id'];

ini_set('display_errors', '0');   // evita que avisos de PHP rompan el JSON
try {
switch ($action) {
    case 'registrar_esporadica':
        requireRole('ADMIN');
        $usuarioObjetivo=(int)($_POST['usuario_id']??0);
        $familiarObjetivo=(int)($_POST['familiar_id']??0);
        $fecha=$_POST['fecha']??'';
        $resultado=$controller->registrarEsporadicaAdmin($usuarioObjetivo,$familiarObjetivo,$fecha);
        echo json_encode($resultado);
        break;
    case 'reservar_titular':
        $fecha = $_POST['fecha'] ?? '';
        echo json_encode($controller->reservarTitular($titularId, $fecha));
        break;

    case 'reservar_familiar':
        $familiarId = (int) ($_POST['familiar_id'] ?? 0);
        $fecha      = $_POST['fecha'] ?? '';
        echo json_encode($controller->reservarFamiliar($titularId, $familiarId, $fecha));
        break;

    case 'cancelar':
        $reservaId = (int) ($_POST['reserva_id'] ?? 0);
        echo json_encode($controller->cancelar($reservaId, $titularId));
        break;

    case 'listar':
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin    = $_GET['fecha_fin']    ?? null;
        $reservas    = $controller->listarMisReservas($titularId, $fechaInicio, $fechaFin);
        echo json_encode(['success' => true, 'data' => $reservas]);
        break;

    case 'listar_por_fecha': // Admin
        requireRole('ADMIN');
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $data  = $controller->listarPorFecha($fecha);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
}
} catch (Throwable $e) {
    error_log('[reservas.php] ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')']);
}
