<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../controllers/ReporteController.php';

requireAuth();
requireRole('ADMIN');

$action     = $_POST['action'] ?? $_GET['action'] ?? '';
$controller = new ReporteController();

ini_set('display_errors', '0');   // evita que avisos de PHP rompan el JSON
try {
switch ($action) {
    case 'generar':
        $anio = (int) ($_POST['anio'] ?? date('Y'));
        $mes  = (int) ($_POST['mes']  ?? date('m'));
        if ($mes < 1 || $mes > 12) {
            echo json_encode(['success' => false, 'message' => 'Mes inválido.']);
            break;
        }
        echo json_encode($controller->generarReporteMensual($anio, $mes));
        break;

    case 'obtener':
        $anio = (int) ($_GET['anio'] ?? date('Y'));
        $mes  = (int) ($_GET['mes']  ?? date('m'));
        $data = $controller->obtenerReporte($anio, $mes);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
}
} catch (Throwable $e) {
    error_log('[reportes.php] ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')']);
}
