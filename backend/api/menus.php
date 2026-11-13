<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../controllers/MenuController.php';

requireAuth();

$action     = $_POST['action'] ?? $_GET['action'] ?? '';
$controller = new MenuController();

try {
    switch ($action) {
        case 'publicar':
            requireRole('ADMIN');
            $fecha       = $_POST['fecha']       ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $adminId     = (int) $_SESSION['usuario_id'];
            echo json_encode($controller->publicar($fecha, $descripcion, $adminId));
            break;

        case 'semana_actual':
            $menus = $controller->menuSemanaActual();
            echo json_encode(['success' => true, 'data' => $menus]);
            break;

        case 'semana':
            $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
            $menus = $controller->menuSemana($fechaInicio);
            echo json_encode(['success' => true, 'data' => $menus]);
            break;

        case 'dia':
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $menu  = $controller->menuDelDia($fecha);
            echo json_encode(['success' => true, 'data' => $menu]);
            break;

        case 'listar':
            requireRole('ADMIN');
            $pagina    = (int) ($_GET['pagina']    ?? 1);
            $porPagina = (int) ($_GET['por_pagina'] ?? 30);
            $menus = $controller->listarTodos($pagina, $porPagina);
            echo json_encode(['success' => true, 'data' => $menus]);
            break;

        case 'eliminar':
            requireRole('ADMIN');
            $menuId = (int) ($_POST['menu_id'] ?? 0);
            echo json_encode($controller->eliminar($menuId));
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor.',
    ]);
    error_log('[menus.php] ' . $e->getMessage());
}
