<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Reserva.php';
require_once __DIR__ . '/../models/Familiar.php';
require_once __DIR__ . '/../models/Calendario.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Menu.php';

class ReservaController {
    private Reserva   $reservaModel;
    private Familiar  $familiarModel;
    private Calendario $calendarioModel;
    private Usuario   $usuarioModel;
    private Menu      $menuModel;

    public function __construct() {
        $this->reservaModel   = new Reserva();
        $this->familiarModel  = new Familiar();
        $this->calendarioModel= new Calendario();
        $this->usuarioModel   = new Usuario();
        $this->menuModel      = new Menu();
    }

    private function parametro(string $clave, string $porDefecto): string {
        try {
            $db = Database::getConnection();
            $q = $db->prepare('SELECT valor FROM configuracion_sistema WHERE clave = ?');
            $q->execute([$clave]);
            return (string)($q->fetchColumn() ?: $porDefecto);
        } catch (Throwable $e) { return $porDefecto; }
    }

    private function modalidadDelMes(int $usuarioId, string $fecha, int $beneficiarioId = 0): array {
        $dt = new DateTime($fecha);
        $q = Database::getConnection()->prepare("SELECT COUNT(*) FROM inscripciones_pension WHERE usuario_id=? AND beneficiario_id=? AND anio=? AND mes=? AND estado='ACTIVA'");
        $q->execute([$usuarioId, $beneficiarioId, (int)$dt->format('Y'), (int)$dt->format('m')]);
        $pensionado = (int)$q->fetchColumn() > 0;
        return [$pensionado ? 'PENSIONADO' : 'ESPORADICO', (float)$this->parametro($pensionado ? 'tarifa_pensionado' : 'tarifa_esporadico', $pensionado ? '15.00' : '18.00')];
    }

    /** Valida fecha, día operativo y hora límite. Devuelve el mensaje de error o null si todo está bien. */
    private function errorDeFecha(string $fecha): ?string {
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$dt || $dt->format('Y-m-d') !== $fecha) return 'Fecha inválida.';
        if ($fecha < date('Y-m-d')) return 'No se aceptan reservas para fechas pasadas.';
        if ((int)$dt->format('N') > 4) return 'El restaurante atiende únicamente de lunes a jueves.';
        if (!$this->calendarioModel->esDiaOperativo($fecha)) return 'El restaurante no opera en feriados.';
        $limite = $this->parametro('hora_limite', HORA_LIMITE_CANCELACION);
        if ($fecha === date('Y-m-d') && date('H:i') >= $limite) return 'Expiró el horario límite de reserva (' . $limite . ').';
        return null;
    }

    /** Devuelve el titular si es un empleado activo (no administrador); si no, null. */
    private function titularValido(int $usuarioId): ?array {
        $u = $this->usuarioModel->findById($usuarioId);
        if (!$u || $u['tipo_cuenta'] === 'ADMIN' || !(int)$u['activo']) return null;
        return $u;
    }

    /**
     * El titular reserva su propio almuerzo hasta la hora límite.
     * Si está inscrito en la pensión del mes paga la tarifa de pensión; si no, la esporádica.
     */
    public function reservarTitular(int $usuarioId, string $fecha): array {
        if ($err = $this->errorDeFecha($fecha)) return ['success'=>false,'message'=>$err];
        if (!$this->titularValido($usuarioId)) return ['success'=>false,'message'=>'Cuenta no autorizada o inactiva.'];
        if ($this->reservaModel->existeParaTitular($usuarioId, $fecha)) return ['success'=>false,'message'=>'Ya tienes una reserva para esa fecha.'];
        [$modalidad, $tarifa] = $this->modalidadDelMes($usuarioId, $fecha, 0);
        $id = $this->reservaModel->crearParaTitular($usuarioId, $fecha, $modalidad, $tarifa, false);
        return ['success'=>true,'message'=>'Reserva confirmada a Bs '.number_format($tarifa,2).($modalidad==='PENSIONADO'?' (pensión)':' (esporádico)').'.','reserva_id'=>$id];
    }

    /**
     * El titular reserva para un familiar registrado (familiarId > 0) o para un invitado
     * (familiarId = 0: conocido o familiar no registrado, siempre tarifa esporádica).
     * Un familiar inscrito en la pensión del mes paga la tarifa de pensión.
     */
    public function reservarFamiliar(int $titularId, int $familiarId, string $fecha): array {
        if ($err = $this->errorDeFecha($fecha)) return ['success'=>false,'message'=>$err];
        if (!$this->titularValido($titularId)) return ['success'=>false,'message'=>'Cuenta no autorizada o inactiva.'];

        if ($familiarId > 0) {
            $f = $this->familiarModel->findById($familiarId);
            if (!$f || (int)$f['empleado_titular_id'] !== $titularId || $f['estado'] !== 'ACTIVO') return ['success'=>false,'message'=>'Familiar inválido o inactivo.'];
            if ($this->reservaModel->existeParaFamiliar($familiarId, $fecha)) return ['success'=>false,'message'=>'Ya existe una reserva para ese familiar en esa fecha.'];
            [$modalidad, $tarifa] = $this->modalidadDelMes($titularId, $fecha, $familiarId);
            $id = $this->reservaModel->crearParaFamiliar($titularId, $familiarId, $fecha, $modalidad, $tarifa, false);
            return ['success'=>true,'message'=>'Reserva del familiar confirmada a Bs '.number_format($tarifa,2).($modalidad==='PENSIONADO'?' (pensión)':' (esporádico)').'.','reserva_id'=>$id];
        }

        $tarifa = (float)$this->parametro('tarifa_esporadico', '18.00');
        $id = $this->reservaModel->crearParaInvitado($titularId, $fecha, $tarifa);
        return ['success'=>true,'message'=>'Reserva del invitado confirmada a Bs '.number_format($tarifa,2).' (esporádico).','reserva_id'=>$id];
    }

    /**
     * Cancela una reserva aplicando la regla de las 09:00 a.m.
     * Un titular solo puede cancelar sus reservas o las de sus familiares.
     */
    public function cancelar(int $reservaId, int $titularId): array {
        $reserva = $this->reservaModel->findById($reservaId);

        if (!$reserva) {
            return ['success' => false, 'message' => 'Reserva no encontrada.'];
        }

        if ($reserva['estado'] === 'CANCELADO') {
            return ['success' => false, 'message' => 'Esta reserva ya fue cancelada.'];
        }
        if ($reserva['estado'] !== 'RESERVADO') {
            return ['success' => false, 'message' => 'La reserva superó el horario límite de cancelación y se mantiene el cobro.'];
        }

        // Verificar propiedad: la reserva debe pertenecer al titular
        if ((int) $reserva['usuario_id'] !== $titularId) {
            return ['success' => false, 'message' => 'No tienes permiso para cancelar esta reserva.'];
        }

        // Regla de hora: si es para HOY, solo se permite antes de las 09:00
        $hoy = date('Y-m-d');
        if ($reserva['fecha_reserva'] === $hoy) {
            $horaActual = date('H:i');
            $horaLimite = $this->parametro('hora_limite', HORA_LIMITE_CANCELACION);
            if ($horaActual >= $horaLimite) {
                return [
                    'success' => false,
                    'message' => 'Las reservas del día de hoy solo pueden cancelarse antes de las ' . $horaLimite . '.'
                ];
            }
        }

        // No se puede cancelar reservas de días pasados
        if ($reserva['fecha_reserva'] < $hoy) {
            return ['success' => false, 'message' => 'No puedes cancelar reservas de fechas pasadas.'];
        }

        // Para pensionados inscritos, no permitir bajar de 10 almuerzos programados/cobrables.
        $dtReserva = new DateTime($reserva['fecha_reserva']);
        $q = Database::getConnection()->prepare("SELECT COUNT(*) FROM inscripciones_pension WHERE usuario_id=? AND beneficiario_id=? AND anio=? AND mes=? AND estado='ACTIVA'");
        $beneficiarioId = $reserva['tipo_beneficiario'] === 'FAMILIAR' ? (int)$reserva['familiar_id'] : 0;
        $q->execute([$titularId, $beneficiarioId, (int)$dtReserva->format('Y'), (int)$dtReserva->format('m')]);
        if ($reserva['modalidad'] === 'PENSIONADO' && (int)$q->fetchColumn() > 0) {
            $total = $reserva['tipo_beneficiario'] === 'FAMILIAR'
                ? $this->reservaModel->contarConsumosFamiliar((int)$reserva['familiar_id'], (int)$dtReserva->format('Y'), (int)$dtReserva->format('m'))
                : $this->reservaModel->contarConsumosTitular($titularId, (int)$dtReserva->format('Y'), (int)$dtReserva->format('m'));
            $minimo=(int)$this->parametro('minimo_pension',(string)MINIMO_ALMUERZOS_MES);
            $minimo=min($minimo,$this->calendarioModel->diasOperativosMes((int)$dtReserva->format('Y'),(int)$dtReserva->format('m')));
            if ($total <= $minimo) return ['success'=>false,'message'=>'No puedes cancelar: debes mantener el mínimo de '.$minimo.' almuerzos mensuales.'];
        }
        $this->reservaModel->cancelar($reservaId);
        return ['success' => true, 'message' => 'Reserva cancelada correctamente.'];
    }

    /** Registra una solicitud esporádica recibida por WhatsApp; solo ADMIN. */
    public function registrarEsporadicaAdmin(int $usuarioId, int $familiarId, string $fecha): array {
        $dt=DateTime::createFromFormat('Y-m-d',$fecha);
        if(!$dt || $dt->format('Y-m-d')!==$fecha) return ['success'=>false,'message'=>'Fecha inválida.'];
        if($fecha < date('Y-m-d')) return ['success'=>false,'message'=>'No se aceptan reservas para fechas pasadas.'];
        if(date('N', $dt->getTimestamp()) > 4 || !$this->calendarioModel->esDiaOperativo($fecha)) return ['success'=>false,'message'=>'El restaurante atiende de lunes a jueves y no opera en feriados.'];
        $limite=$this->parametro('hora_limite',HORA_LIMITE_CANCELACION);
        if($fecha===date('Y-m-d') && date('H:i') >= $limite) return ['success'=>false,'message'=>'Expiró el horario límite de reserva ('.$limite.').'];
        $usuario=$this->usuarioModel->findById($usuarioId);
        if(!$usuario || $usuario['tipo_cuenta']==='ADMIN' || !(int)$usuario['activo']) return ['success'=>false,'message'=>'Empleado no autorizado o inactivo.'];
        $tarifa=(float)$this->parametro('tarifa_esporadico','18.00');
        if($familiarId>0) {
            $f=$this->familiarModel->findById($familiarId);
            if(!$f || (int)$f['empleado_titular_id']!==$usuarioId || $f['estado']!=='ACTIVO') return ['success'=>false,'message'=>'Familiar inválido.'];
            if($this->reservaModel->existeParaFamiliar($familiarId,$fecha)) return ['success'=>false,'message'=>'Ya existe una reserva para ese familiar y fecha.'];
            $id=$this->reservaModel->crearParaFamiliar($usuarioId,$familiarId,$fecha,'ESPORADICO',$tarifa,true);
        } elseif($familiarId<0) {
            $id=$this->reservaModel->crearParaInvitado($usuarioId,$fecha,$tarifa);
        } else {
            if($this->reservaModel->existeParaTitular($usuarioId,$fecha)) return ['success'=>false,'message'=>'Ya existe una reserva para ese empleado y fecha.'];
            $id=$this->reservaModel->crearParaTitular($usuarioId,$fecha,'ESPORADICO',$tarifa,true);
        }
        $destinatario=$usuario['correo'] ?? '';
        if ($destinatario) { @mail($destinatario, 'Reserva de almuerzo confirmada', 'Tu reserva para el '.$fecha.' fue confirmada por Bs '.number_format($tarifa,2).'.', 'From: sistema@rumboalcielo.bo'); }
        return ['success'=>true,'message'=>'Reserva esporádica confirmada por administración a Bs '.number_format($tarifa,2).'.','reserva_id'=>$id];
    }

    /**
     * Lista las reservas del titular y sus familiares.
     */
    public function listarMisReservas(int $titularId, ?string $fechaInicio = null, ?string $fechaFin = null): array {
        return $this->reservaModel->listarPorTitular($titularId, $fechaInicio, $fechaFin);
    }

    /**
     * Lista todas las reservas de un día (para admin).
     */
    public function listarPorFecha(string $fecha): array {
        return $this->reservaModel->listarPorFecha($fecha);
    }
}
