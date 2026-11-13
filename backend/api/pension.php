<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
requireAuth();

$db = Database::getConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$uid = (int)$_SESSION['usuario_id'];

if ($action === 'estado') {
    $mes = (int)($_GET['mes'] ?? date('m'));
    $anio = (int)($_GET['anio'] ?? date('Y'));
    $beneficiario=(int)($_GET['beneficiario_id']??0);
    $q=$db->prepare("SELECT * FROM inscripciones_pension WHERE usuario_id=? AND beneficiario_id=? AND anio=? AND mes=? AND estado='ACTIVA'");
    $q->execute([$uid,$beneficiario,$anio,$mes]);
    echo json_encode(['success'=>true,'data'=>$q->fetch() ?: null]); exit;
}

if ($action === 'inscribir') {
    if (($_SESSION['tipo_cuenta'] ?? '') !== 'PENSIONADO') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Solo las cuentas pensionadas pueden inscribirse.']); exit; }
    $mes = (int)($_POST['mes'] ?? date('m'));
    $anio = (int)($_POST['anio'] ?? date('Y'));
    if ($mes<1 || $mes>12 || !checkdate($mes,1,$anio)) { echo json_encode(['success'=>false,'message'=>'Mes o año inválido.']); exit; }
    $inicio = DateTime::createFromFormat('!Y-n-j', "$anio-$mes-1");
    $ahora = new DateTime();
    if ($inicio <= new DateTime($ahora->format('Y-m-01'))) { echo json_encode(['success'=>false,'message'=>'La inscripción debe realizarse antes de iniciar el mes correspondiente.']); exit; }
    $beneficiario=(int)($_POST['beneficiario_id']??0);
    if($beneficiario>0){$vf=$db->prepare("SELECT COUNT(*) FROM familiares WHERE id=? AND empleado_titular_id=? AND estado='ACTIVO'");$vf->execute([$beneficiario,$uid]);if(!(int)$vf->fetchColumn()){echo json_encode(['success'=>false,'message'=>'El familiar seleccionado no pertenece a tu cuenta o está inactivo.']);exit;}}
    $q=$db->prepare("INSERT INTO inscripciones_pension(usuario_id,beneficiario_id,anio,mes,estado) VALUES(?,?,?,?, 'ACTIVA') ON DUPLICATE KEY UPDATE estado='ACTIVA', registrado_en=CURRENT_TIMESTAMP");
    $q->execute([$uid,$beneficiario,$anio,$mes]);
    // Confirmación automática de lunes a jueves del mes solicitado, excluyendo feriados.
    $tarifaQ=$db->prepare("SELECT valor FROM configuracion_sistema WHERE clave='tarifa_pensionado'"); $tarifaQ->execute(); $tarifa=(float)($tarifaQ->fetchColumn() ?: 15);
    $feriados=$db->prepare('SELECT fecha FROM calendario_operativo WHERE es_feriado=1 AND fecha BETWEEN ? AND ?');
    $desde=$inicio->format('Y-m-01'); $hasta=$inicio->format('Y-m-t'); $feriados->execute([$desde,$hasta]);
    $noLaborables=array_column($feriados->fetchAll(PDO::FETCH_ASSOC),'fecha');
    if($beneficiario>0){
      $insert=$db->prepare("INSERT IGNORE INTO reservas(usuario_id,familiar_id,tipo_beneficiario,modalidad,tarifa,fecha_reserva,estado) VALUES(?,?,'FAMILIAR','PENSIONADO',?,?,'RESERVADO')");
    } else {
      $insert=$db->prepare("INSERT IGNORE INTO reservas(usuario_id,familiar_id,tipo_beneficiario,modalidad,tarifa,fecha_reserva,estado) VALUES(?,NULL,'TITULAR','PENSIONADO',?,?,'RESERVADO')");
    }
    $creadas=0; $cursor=clone $inicio; $cursor->modify('first day of this month');
    while($cursor->format('Y-m')===$inicio->format('Y-m')) {
      $fechaDia=$cursor->format('Y-m-d');
      if((int)$cursor->format('N')<=4 && !in_array($fechaDia,$noLaborables,true)) {
        if($beneficiario>0){$ex=$db->prepare("SELECT COUNT(*) FROM reservas WHERE familiar_id=? AND fecha_reserva=?");$ex->execute([$beneficiario,$fechaDia]);}
        else {$ex=$db->prepare("SELECT COUNT(*) FROM reservas WHERE usuario_id=? AND tipo_beneficiario='TITULAR' AND fecha_reserva=?");$ex->execute([$uid,$fechaDia]);}
        if(!(int)$ex->fetchColumn()){if($beneficiario>0) $insert->execute([$uid,$beneficiario,$tarifa,$fechaDia]); else $insert->execute([$uid,$tarifa,$fechaDia]);$creadas += $insert->rowCount();}
      }
      $cursor->modify('+1 day');
    }
    echo json_encode(['success'=>true,'message'=>'Inscripción mensual registrada. Se confirmaron automáticamente '.$creadas.' almuerzos de lunes a jueves.','reservas_creadas'=>$creadas]); exit;
}

http_response_code(400); echo json_encode(['success'=>false,'message'=>'Acción no válida.']);
