<?php
require_once __DIR__ . '/../config/database.php';

class Reserva {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /** Crea una reserva para un titular con tarifa histórica. */
    public function crearParaTitular(int $usuarioId, string $fecha, string $modalidad = 'ESPORADICO', float $tarifa = 18.00, bool $admin = false): int {
        $stmt = $this->db->prepare(
            "INSERT INTO reservas (usuario_id, familiar_id, tipo_beneficiario, modalidad, tarifa, confirmado_por_admin, fecha_reserva, estado)
             VALUES (:uid, NULL, 'TITULAR', :modalidad, :tarifa, :admin, :fecha, 'RESERVADO')"
        );
        $stmt->execute([':uid'=>$usuarioId, ':modalidad'=>$modalidad, ':tarifa'=>$tarifa, ':admin'=>(int)$admin, ':fecha'=>$fecha]);
        return (int)$this->db->lastInsertId();
    }

    /** Crea una reserva para un familiar con tarifa histórica. */
    public function crearParaFamiliar(int $usuarioId, int $familiarId, string $fecha, string $modalidad = 'ESPORADICO', float $tarifa = 18.00, bool $admin = false): int {
        $stmt = $this->db->prepare(
            "INSERT INTO reservas (usuario_id, familiar_id, tipo_beneficiario, modalidad, tarifa, confirmado_por_admin, fecha_reserva, estado)
             VALUES (:uid, :fid, 'FAMILIAR', :modalidad, :tarifa, :admin, :fecha, 'RESERVADO')
             ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), estado='RESERVADO', modalidad=VALUES(modalidad), tarifa=VALUES(tarifa), confirmado_por_admin=VALUES(confirmado_por_admin), fecha_cancelacion=NULL"
        );
        $stmt->execute([':uid'=>$usuarioId, ':fid'=>$familiarId, ':modalidad'=>$modalidad, ':tarifa'=>$tarifa, ':admin'=>(int)$admin, ':fecha'=>$fecha]);
        return (int)$this->db->lastInsertId();
    }

    /** Crea una reserva esporádica para un invitado (conocido o familiar no registrado) del titular. */
    public function crearParaInvitado(int $usuarioId, string $fecha, float $tarifa): int {
        $stmt = $this->db->prepare(
            "INSERT INTO reservas (usuario_id, familiar_id, tipo_beneficiario, modalidad, tarifa, confirmado_por_admin, fecha_reserva, estado)
             VALUES (:uid, NULL, 'FAMILIAR', 'ESPORADICO', :tarifa, 0, :fecha, 'RESERVADO')"
        );
        $stmt->execute([':uid'=>$usuarioId, ':tarifa'=>$tarifa, ':fecha'=>$fecha]);
        return (int)$this->db->lastInsertId();
    }

    /** Cancela una reserva */
    public function cancelar(int $reservaId): bool {
        $stmt = $this->db->prepare(
            'UPDATE reservas
             SET estado = \'CANCELADO\', fecha_cancelacion = NOW()
             WHERE id = :id AND estado = \'RESERVADO\''
        );
        $stmt->execute([':id' => $reservaId]);
        return $stmt->rowCount() > 0;
    }

    /** Obtiene una reserva por ID */
    public function findById(int $id): array|false {
        $stmt = $this->db->prepare('SELECT * FROM reservas WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /** Lista reservas de un titular (propias y de sus familiares) */
    public function listarPorTitular(int $usuarioId, ?string $fechaInicio = null, ?string $fechaFin = null): array {
        $where = 'r.usuario_id = :uid';
        $params = [':uid' => $usuarioId];

        if ($fechaInicio) {
            $where .= ' AND r.fecha_reserva >= :fi';
            $params[':fi'] = $fechaInicio;
        }
        if ($fechaFin) {
            $where .= ' AND r.fecha_reserva <= :ff';
            $params[':ff'] = $fechaFin;
        }

        $stmt = $this->db->prepare(
            "SELECT r.*, IF(r.tipo_beneficiario = 'FAMILIAR', COALESCE(f.nombre, 'Invitado'), NULL) AS nombre_familiar
             FROM reservas r
             LEFT JOIN familiares f ON f.id = r.familiar_id
             WHERE $where
             ORDER BY r.fecha_reserva DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Verifica si ya existe una reserva activa para un titular en una fecha */
    public function existeParaTitular(int $usuarioId, string $fecha): bool {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM reservas
             WHERE usuario_id = :uid AND fecha_reserva = :fecha
               AND tipo_beneficiario = \'TITULAR\' AND estado IN (\'RESERVADO\',\'ENTREGADO\',\'INASISTENCIA\')'
        );
        $stmt->execute([':uid' => $usuarioId, ':fecha' => $fecha]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Verifica si ya existe una reserva activa para un familiar en una fecha */
    public function existeParaFamiliar(int $familiarId, string $fecha): bool {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM reservas
             WHERE familiar_id = :fid AND fecha_reserva = :fecha AND estado IN (\'RESERVADO\',\'ENTREGADO\',\'INASISTENCIA\')'
        );
        $stmt->execute([':fid' => $familiarId, ':fecha' => $fecha]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Cuenta almuerzos consumidos (estado RESERVADO) de un titular
     * en un período de mes dado.
     */
    public function contarConsumosTitular(int $usuarioId, int $anio, int $mes): int {
        $inicio = sprintf('%04d-%02d-01', $anio, $mes);
        $fin    = (new DateTime($inicio))->format('Y-m-t');
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM reservas
             WHERE usuario_id = :uid
               AND tipo_beneficiario = \'TITULAR\'
               AND fecha_reserva BETWEEN :ini AND :fin
               AND estado IN (\'RESERVADO\',\'ENTREGADO\',\'INASISTENCIA\')'
        );
        $stmt->execute([':uid' => $usuarioId, ':ini' => $inicio, ':fin' => $fin]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta almuerzos consumidos de un familiar en un mes.
     */
    public function contarConsumosFamiliar(int $familiarId, int $anio, int $mes): int {
        $inicio = sprintf('%04d-%02d-01', $anio, $mes);
        $fin    = (new DateTime($inicio))->format('Y-m-t');
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM reservas
             WHERE familiar_id = :fid
               AND fecha_reserva BETWEEN :ini AND :fin
               AND estado IN (\'RESERVADO\',\'ENTREGADO\',\'INASISTENCIA\')'
        );
        $stmt->execute([':fid' => $familiarId, ':ini' => $inicio, ':fin' => $fin]);
        return (int) $stmt->fetchColumn();
    }

    /** Lista reservas de un día para admin */
    public function listarPorFecha(string $fecha): array {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.nombre AS nombre_titular, u.codigo_empleado,
                    IF(r.tipo_beneficiario = 'FAMILIAR', COALESCE(f.nombre, 'Invitado'), NULL) AS nombre_familiar
             FROM reservas r
             JOIN usuarios u ON u.id = r.usuario_id
             LEFT JOIN familiares f ON f.id = r.familiar_id
             WHERE r.fecha_reserva = :fecha
             ORDER BY u.nombre ASC, r.tipo_beneficiario ASC"
        );
        $stmt->execute([':fecha' => $fecha]);
        return $stmt->fetchAll();
    }
}
