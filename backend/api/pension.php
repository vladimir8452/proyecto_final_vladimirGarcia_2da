<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../../src/autoload.php';
requireAuth();
$uid=(int)$_SESSION['usuario_id'];
$action=$_POST['action']??$_GET['action']??'';
try {
    $controller=\RumboAlCielo\Infrastructure\Contenedor::pensionController();
    $mes=(int)($_POST['mes']??$_GET['mes']??date('m'));
    $anio=(int)($_POST['anio']??$_GET['anio']??date('Y'));
    $beneficiario=(int)($_POST['beneficiario_id']??$_GET['beneficiario_id']??0);
    if($action==='estado') $resultado=$controller->estado($uid,$beneficiario,$anio,$mes);
    elseif($action==='inscribir') $resultado=$controller->inscribir($uid,$beneficiario,$anio,$mes,(string)($_SESSION['tipo_cuenta']??''));
    else { http_response_code(400); $resultado=['success'=>false,'message'=>'Acción no válida.']; }
    echo json_encode($resultado,JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
    error_log('[pension.php] '.$e->getMessage());
    http_response_code(500); echo json_encode(['success'=>false,'message'=>'No se pudo procesar la operación de pensión.'],JSON_UNESCAPED_UNICODE);
}
