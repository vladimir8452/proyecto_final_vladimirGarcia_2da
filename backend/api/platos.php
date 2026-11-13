<?php
/**
 * Endpoint JSON del módulo Platos (punto de entrada del framework/servidor).
 * Solo enruta la acción hacia PlatoController; la lógica está en src/.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../../src/autoload.php';

use RumboAlCielo\Infrastructure\Contenedor;

requireRole('ADMIN');

$accion      = $_POST['action'] ?? $_GET['action'] ?? '';
$datos       = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$controlador = Contenedor::platoController();

try {
    switch ($accion) {
        case 'listar':     $respuesta = $controlador->listar($datos);     break;
        case 'registrar':  $respuesta = $controlador->registrar($datos);  break;
        case 'modificar':  $respuesta = $controlador->modificar($datos);  break;
        case 'desactivar': $respuesta = $controlador->desactivar($datos); break;
        case 'activar':    $respuesta = $controlador->activar($datos);    break;
        default:
            http_response_code(400);
            $respuesta = ['success' => false, 'message' => 'Acción no válida.'];
    }
    echo json_encode($respuesta);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
    error_log('[platos.php] ' . $e->getMessage());
}
