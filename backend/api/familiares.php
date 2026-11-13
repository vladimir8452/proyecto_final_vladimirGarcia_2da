<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../controllers/FamiliarController.php';

requireAuth();

$action     = $_POST['action'] ?? $_GET['action'] ?? '';
$controller = new FamiliarController();
$tipo       = $_SESSION['tipo_cuenta'] ?? '';

// El admin puede operar sobre cualquier titular; el empleado solo sobre sí mismo.
if ($tipo === 'ADMIN') {
    $titularId = (int) ($_POST['titular_id'] ?? $_GET['titular_id'] ?? 0);
} else {
    $titularId = (int) $_SESSION['usuario_id'];
}

switch ($action) {

    // El administrador puede registrar para cualquier titular; el titular solo para sí mismo.
    case 'registrar':
        if ($tipo !== 'ADMIN' && $tipo !== 'PENSIONADO') {
            http_response_code(403); echo json_encode(['success'=>false,'message'=>'Solo titulares pensionados pueden registrar familiares.']); break;
        }
        $nombre=$_POST['nombre']??''; $parentesco=$_POST['parentesco']??''; $ci=$_POST['ci']??'';
        echo json_encode($controller->registrar($titularId,$nombre,$parentesco,$ci));
        break;

    case 'desactivar':
        $familiarId=(int)($_POST['familiar_id']??0);
        echo json_encode($controller->desactivar($titularId,$familiarId));
        break;

    case 'actualizar':
        if ($tipo !== 'ADMIN' && $tipo !== 'PENSIONADO') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Acceso denegado.']); break; }
        $familiarId=(int)($_POST['familiar_id']??0); $nombre=$_POST['nombre']??''; $parentesco=$_POST['parentesco']??''; $ci=$_POST['ci']??'';
        echo json_encode($controller->actualizar($titularId,$familiarId,$nombre,$parentesco,$ci));
        break;

    // ── ADMIN Y EMPLEADO ─────────────────────────────────────────
    case 'listar':
        $data = $controller->listar($titularId);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
}
