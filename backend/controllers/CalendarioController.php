<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Calendario.php';

class CalendarioController {
    private Calendario $calendarioModel;

    public function __construct() {
        $this->calendarioModel = new Calendario();
    }

    /**
     * Marca un día como feriado.
     * Solo aplica a días L-J (los únicos operativos del restaurante).
     */
    public function marcarFeriado(string $fecha, string $descripcion): array {
        if (empty($fecha)) {
            return ['success' => false, 'message' => 'La fecha es requerida.'];
        }

        // Validar formato con DateTime (evita strtotime en Windows)
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$dt || $dt->format('Y-m-d') !== $fecha) {
            return ['success' => false, 'message' => 'Formato de fecha inválido. Use YYYY-MM-DD.'];
        }

        $diaSemana = (int) $dt->format('N'); // ISO-8601: 1=Lun … 7=Dom
        if ($diaSemana < 1 || $diaSemana > 4) {
            return ['success' => false, 'message' => 'Solo se pueden marcar feriados en días Lunes a Jueves.'];
        }

        $this->calendarioModel->guardar($fecha, true, $descripcion);
        // Un feriado suspende las reservas ya programadas para evitar descuentos.
        $db = Database::getConnection();
        $cancelar = $db->prepare("UPDATE reservas SET estado='CANCELADO', fecha_cancelacion=NOW() WHERE fecha_reserva=? AND estado IN ('RESERVADO','INASISTENCIA')");
        $cancelar->execute([$fecha]);
        return [
            'success' => true,
            'message' => 'Día ' . $dt->format('d/m/Y') . ' marcado como feriado.',
        ];
    }

    /**
     * Quita el feriado de un día (lo restaura como operativo).
     */
    public function quitarFeriado(string $fecha): array {
        $registro = $this->calendarioModel->findByFecha($fecha);
        if (!$registro) {
            return ['success' => false, 'message' => 'El día no está en el calendario.'];
        }
        $this->calendarioModel->guardar($fecha, false, '');
        return ['success' => true, 'message' => 'Feriado eliminado. El día vuelve a ser operativo.'];
    }

    /** Lista todos los feriados registrados */
    public function listarFeriados(): array {
        return $this->calendarioModel->listarFeriados();
    }

    /**
     * Retorna los días L-J de un mes con su estado operativo.
     * Delega en el modelo (que usa DateTime internamente).
     */
    public function calendarioMes(int $anio, int $mes): array {
        return $this->calendarioModel->calendarioMes($anio, $mes);
    }
}
