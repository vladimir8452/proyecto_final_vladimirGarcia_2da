<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
requireAuth(); requireRole('ADMIN');
$db=Database::getConnection();
$action=$_POST['action'] ?? $_GET['action'] ?? '';
if($action==='listar') { $q=$db->query('SELECT clave,valor FROM configuracion_sistema ORDER BY clave'); echo json_encode(['success'=>true,'data'=>$q->fetchAll()]); exit; }
if($action==='guardar') {
  $permitidas=['hora_limite','tarifa_pensionado','tarifa_esporadico','minimo_pension'];
  $clave=$_POST['clave']??''; $valor=trim($_POST['valor']??'');
  if(!in_array($clave,$permitidas,true) || $valor==='') { echo json_encode(['success'=>false,'message'=>'Parámetro inválido.']); exit; }
  if($clave==='minimo_pension' && (!ctype_digit($valor) || (int)$valor<1)) { echo json_encode(['success'=>false,'message'=>'El mínimo debe ser un número entero positivo.']); exit; }
  if($clave==='hora_limite' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$valor)) { echo json_encode(['success'=>false,'message'=>'La hora debe tener formato HH:MM.']); exit; }
  if($clave!=='hora_limite' && (!is_numeric($valor) || (float)$valor<=0)) { echo json_encode(['success'=>false,'message'=>'El valor debe ser numérico y mayor que cero.']); exit; }
  $q=$db->prepare('INSERT INTO configuracion_sistema(clave,valor) VALUES(?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)'); $q->execute([$clave,$valor]);
  echo json_encode(['success'=>true,'message'=>'Configuración actualizada.']); exit;
}
http_response_code(400); echo json_encode(['success'=>false,'message'=>'Acción no válida.']);
