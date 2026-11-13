<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/config/session.php';
requireRole('PENSIONADO');
$pagina = 'Mi Pensión Mensual';
require_once __DIR__ . '/layout.php';
$proximo = new DateTime('first day of next month');
$cfg=Database::getConnection()->query('SELECT clave,valor FROM configuracion_sistema')->fetchAll(PDO::FETCH_KEY_PAIR);
$tarifaPension=(float)($cfg['tarifa_pensionado']??15); $minimoPension=(int)($cfg['minimo_pension']??10);
?>
<div class="card" style="max-width:760px;">
 <div class="card-header"><h3>🍽️ Inscripción mensual a pensión</h3></div>
 <div class="card-body">
  <p>Al inscribirte antes de iniciar el mes, el sistema confirmará automáticamente tus almuerzos de lunes a jueves, excluyendo feriados, a la tarifa de <strong>Bs <?= number_format($tarifaPension,2) ?> por almuerzo</strong>. Se aplica un mínimo mensual de <?= $minimoPension ?> almuerzos (Bs <?= number_format($tarifaPension*$minimoPension,2) ?>).</p>
  <div class="form-group"><label class="form-label">Beneficiario</label><select id="beneficiarioPension" class="form-select" style="max-width:320px"><option value="0">Yo (titular)</option></select></div>
  <div class="form-group"><label class="form-label">Mes de inscripción</label><input type="month" id="mesPension" class="form-control" style="max-width:240px;" value="<?= $proximo->format('Y-m') ?>"></div>
  <div id="estadoPension" class="alert alert-info">Consulta el estado de tu inscripción.</div>
  <button class="btn btn-primary" onclick="inscribirme()">Confirmar pensión del mes</button>
 </div>
</div>
<script>
async function cargarBeneficiarios(){const r=await apiGet('/backend/api/familiares.php',{action:'listar'});(r.data||[]).filter(x=>x.estado==='ACTIVO').forEach(x=>{const o=document.createElement('option');o.value=x.id;o.textContent=x.nombre+' ('+(x.parentesco||'Familiar')+')';document.getElementById('beneficiarioPension').appendChild(o);});}
async function consultar(){const v=document.getElementById('mesPension').value.split('-');const r=await apiGet('/backend/api/pension.php',{action:'estado',anio:v[0],mes:v[1],beneficiario_id:document.getElementById('beneficiarioPension').value});document.getElementById('estadoPension').textContent=r.data?'Ya estás inscrito para ese mes.':'Aún no tienes inscripción para ese mes.';}
async function inscribirme(){const v=document.getElementById('mesPension').value.split('-');if(!v[0]||!v[1])return Toast.warning('Selecciona un mes.');const r=await apiPost('/backend/api/pension.php',{action:'inscribir',anio:v[0],mes:v[1],beneficiario_id:document.getElementById('beneficiarioPension').value});const el=document.getElementById('estadoPension');el.className='alert '+(r.success?'alert-success':'alert-danger');el.textContent=r.message; if(r.success) consultar();}
document.getElementById('mesPension').addEventListener('change',consultar);document.getElementById('beneficiarioPension').addEventListener('change',consultar);cargarBeneficiarios().then(consultar);
</script>
<?php require_once __DIR__ . '/layout_end.php'; ?>
