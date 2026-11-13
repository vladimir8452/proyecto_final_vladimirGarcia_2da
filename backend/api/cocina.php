<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
requireAuth(); requireRole('ADMIN');
$db=Database::getConnection();
$action=$_POST['action'] ?? $_GET['action'] ?? '';
$fecha=$_POST['fecha'] ?? $_GET['fecha'] ?? date('Y-m-d');

if ($action==='consolidado') {
  $horaLimite='09:00'; $hq=$db->prepare("SELECT valor FROM configuracion_sistema WHERE clave='hora_limite'"); $hq->execute(); if($hv=$hq->fetchColumn()) $horaLimite=$hv;
  if ($fecha < date('Y-m-d') || ($fecha===date('Y-m-d') && date('H:i') >= $horaLimite)) { $close=$db->prepare("UPDATE reservas SET estado='INASISTENCIA' WHERE fecha_reserva=? AND estado='RESERVADO'"); $close->execute([$fecha]); }
  $q=$db->prepare("SELECT r.id,r.fecha_reserva,r.tipo_beneficiario,r.modalidad,r.tarifa,r.estado,u.nombre AS titular,IF(r.tipo_beneficiario='FAMILIAR',COALESCE(f.nombre,'Invitado'),NULL) AS familiar,u.codigo_empleado FROM reservas r JOIN usuarios u ON u.id=r.usuario_id LEFT JOIN familiares f ON f.id=r.familiar_id WHERE r.fecha_reserva=? AND r.estado IN ('RESERVADO','ENTREGADO','INASISTENCIA') ORDER BY u.nombre,f.nombre");
  $q->execute([$fecha]); $items=$q->fetchAll();
  echo json_encode(['success'=>true,'fecha'=>$fecha,'total_raciones'=>count($items),'data'=>$items]); exit;
}
if ($action==='entregar') {
  $id=(int)($_POST['reserva_id']??0);
  $q=$db->prepare("UPDATE reservas SET estado='ENTREGADO',entregado_en=NOW() WHERE id=? AND estado IN ('RESERVADO','INASISTENCIA') AND fecha_reserva=?");
  $q->execute([$id,$fecha]);
  echo json_encode(['success'=>$q->rowCount()>0,'message'=>$q->rowCount()>0?'Ración marcada como entregada.':'No se pudo actualizar la ración.']); exit;
}
if ($action==='cerrar_corte') {
  $horaLimite='09:00'; $q=$db->prepare("SELECT valor FROM configuracion_sistema WHERE clave='hora_limite'"); $q->execute(); if($v=$q->fetchColumn()) $horaLimite=$v;
  if ($fecha>date('Y-m-d') || ($fecha===date('Y-m-d') && date('H:i')<$horaLimite)) { echo json_encode(['success'=>false,'message'=>'El corte diario aún no ha llegado.']); exit; }
  $q=$db->prepare("UPDATE reservas SET estado='INASISTENCIA' WHERE fecha_reserva=? AND estado='RESERVADO'"); $q->execute([$fecha]);
  echo json_encode(['success'=>true,'message'=>'Corte aplicado. Las raciones pendientes quedan registradas como cobradas por inasistencia.','actualizadas'=>$q->rowCount()]); exit;
}
http_response_code(400); echo json_encode(['success'=>false,'message'=>'Acción no válida.']);
