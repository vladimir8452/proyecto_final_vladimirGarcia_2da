<?php
/**
 * API de entrada para los casos de uso de Arquitectura Limpia.
 * Los endpoints de negocio existentes se mantienen para compatibilidad con
 * el frontend; esta ruta expone operaciones CRUD controladas por módulo.
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../../src/autoload.php';
use RumboAlCielo\Infrastructure\Contenedor;

requireAuth();
$modulo = (string)($_GET['modulo'] ?? $_POST['modulo'] ?? '');
$accion = (string)($_GET['accion'] ?? $_POST['accion'] ?? 'listar');
$roles = [
    'usuarios' => ['ADMIN'], 'familiares' => ['ADMIN','PENSIONADO','NO_PENSIONADO'],
    'pensiones' => ['ADMIN','PENSIONADO'], 'menus' => ['ADMIN'],
    'reservas' => ['ADMIN','PENSIONADO','NO_PENSIONADO'], 'reportes' => ['ADMIN']
];
if (!isset($roles[$modulo])) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Módulo no válido.']); exit;
}
requireRole($roles[$modulo]);
try {
    $controller = Contenedor::moduloController($modulo);
    $datos = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    $id = (int)($datos['id'] ?? 0);
    unset($datos['modulo'], $datos['accion'], $datos['action'], $datos['id']);
    switch ($accion) {
        case 'listar':
            // Aislamiento por titular: nunca confiar en el ID enviado por el navegador.
            if ($modulo === 'familiares' && rolActual() !== 'ADMIN') {
                $datos['empleado_titular_id'] = (int)$_SESSION['usuario_id'];
                if ($id > 0) { $actual=$controller->obtener($id); if (empty($actual['success']) || (int)($actual['data']['empleado_titular_id'] ?? 0)!==(int)$_SESSION['usuario_id']) { http_response_code(403); $resultado=['success'=>false,'message'=>'No puedes modificar un familiar que no pertenece a tu cuenta.']; break; } }
            }
            if ($modulo === 'reservas' && rolActual() !== 'ADMIN') $datos['usuario_id'] = (int)$_SESSION['usuario_id'];
            $resultado = $controller->listar($datos); break;
        case 'obtener':
            $resultado = $controller->obtener($id); break;
        case 'guardar':
            if (in_array($modulo, ['pensiones','reservas'], true) && rolActual() !== 'ADMIN') { http_response_code(405); $resultado=['success'=>false,'message'=>'Use el flujo de negocio del módulo para esta operación.']; break; }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); $resultado=['success'=>false,'message'=>'Se requiere POST.']; break; }
            // Nunca permitir que un usuario no administrador cree o modifique otro usuario.
            if ($modulo === 'familiares' && rolActual() !== 'ADMIN') {
                $datos['empleado_titular_id'] = (int)$_SESSION['usuario_id'];
                if ($id > 0) { $actual=$controller->obtener($id); if (empty($actual['success']) || (int)($actual['data']['empleado_titular_id'] ?? 0)!==(int)$_SESSION['usuario_id']) { http_response_code(403); $resultado=['success'=>false,'message'=>'No puedes modificar un familiar que no pertenece a tu cuenta.']; break; } }
            }
            $resultado = $controller->guardar($datos, $id > 0 ? $id : null); break;
        case 'eliminar':
            if (rolActual() !== 'ADMIN') { http_response_code(403); $resultado=['success'=>false,'message'=>'Acción solo disponible para ADMIN.']; break; }
            if (in_array($modulo, ['reservas','reportes','pensiones'], true)) { http_response_code(405); $resultado=['success'=>false,'message'=>'Este registro no se elimina físicamente; use su flujo de negocio.']; break; }
            $resultado = $controller->eliminar($id); break;
        default:
            http_response_code(400); $resultado=['success'=>false,'message'=>'Acción no válida.'];
    }
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[arquitectura.php] '.$e->getMessage());
    http_response_code(500); echo json_encode(['success'=>false,'message'=>'Error interno del servidor.']);
}
