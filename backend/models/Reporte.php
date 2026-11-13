<?php
require_once __DIR__ . '/../../src/autoload.php';
use RumboAlCielo\Domain\Ports\ReporteRepository;
require_once __DIR__ . '/../config/database.php';

class Reporte implements ReporteRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /** Familiares de un titular con su consumo del período (activos, o inactivos con consumos). */
    private function familiaresConsumo(int $titularId, string $inicio, string $fin): array {
        $stmt = $this->db->prepare(
            "SELECT f.id, f.nombre, f.estado,
                    COUNT(r.id) AS almuerzos, COALESCE(SUM(r.tarifa),0) AS monto_reservas, SUM(r.modalidad='PENSIONADO') AS almuerzos_pension
             FROM familiares f
             LEFT JOIN reservas r ON r.familiar_id = f.id
               AND r.fecha_reserva BETWEEN :ini AND :fin
               AND r.estado IN ('RESERVADO','ENTREGADO','INASISTENCIA')
             WHERE f.empleado_titular_id = :tid
             GROUP BY f.id
             HAVING f.estado = 'ACTIVO' OR COUNT(r.id) > 0"
        );
        $stmt->execute([':ini' => $inicio, ':fin' => $fin, ':tid' => $titularId]);
        $familiares = $stmt->fetchAll();

        // Invitados (conocidos o familiares no registrados): se cobran al titular a tarifa esporádica.
        $g = $this->db->prepare(
            "SELECT COUNT(*) AS n, COALESCE(SUM(tarifa),0) AS monto FROM reservas
             WHERE usuario_id = :tid AND tipo_beneficiario = 'FAMILIAR' AND familiar_id IS NULL
               AND fecha_reserva BETWEEN :ini AND :fin
               AND estado IN ('RESERVADO','ENTREGADO','INASISTENCIA')"
        );
        $g->execute([':ini' => $inicio, ':fin' => $fin, ':tid' => $titularId]);
        $inv = $g->fetch();
        if ($inv && (int)$inv['n'] > 0) {
            $familiares[] = ['id' => 0, 'nombre' => 'Invitados', 'almuerzos' => (int)$inv['n'],
                             'monto_reservas' => (float)$inv['monto'], 'almuerzos_pension' => 0];
        }
        return $familiares;
    }

    /** Titulares pensionados con sus consumos y los de sus familiares. */
    public function datosParaReporteMensual(int $anio, int $mes): array {
        return $this->datosTitulares('PENSIONADO', $anio, $mes);
    }

    /** Titulares NO PENSIONADO (pago directo, sin mínimo) con los consumos de sus familiares. */
    public function datosNoPensionadosMensual(int $anio, int $mes): array {
        return $this->datosTitulares('NO_PENSIONADO', $anio, $mes);
    }

    private function datosTitulares(string $tipoCuenta, int $anio, int $mes): array {
        $inicio = sprintf('%04d-%02d-01', $anio, $mes);
        $fin    = (new DateTime($inicio))->format('Y-m-t');

        $stmt = $this->db->prepare(
            "SELECT u.id, u.nombre, u.codigo_empleado,
                    COUNT(r.id) AS almuerzos_titular, COALESCE(SUM(r.tarifa),0) AS monto_reservas, SUM(r.modalidad='PENSIONADO') AS almuerzos_pension
             FROM usuarios u
             LEFT JOIN reservas r ON r.usuario_id = u.id
               AND r.tipo_beneficiario = 'TITULAR'
               AND r.fecha_reserva BETWEEN :ini AND :fin
               AND r.estado IN ('RESERVADO','ENTREGADO','INASISTENCIA')
             WHERE u.tipo_cuenta = :tipo AND u.activo = 1
             GROUP BY u.id"
        );
        $stmt->execute([':ini' => $inicio, ':fin' => $fin, ':tipo' => $tipoCuenta]);
        $titulares = $stmt->fetchAll();

        foreach ($titulares as &$titular) {
            $titular['familiares'] = $this->familiaresConsumo((int)$titular['id'], $inicio, $fin);
        }
        unset($titular);
        return $titulares;
    }

    public function tieneInscripcionPension(int $usuarioId, int $beneficiarioId, int $anio, int $mes): bool {
        $q=$this->db->prepare("SELECT COUNT(*) FROM inscripciones_pension WHERE usuario_id=? AND beneficiario_id=? AND anio=? AND mes=? AND estado='ACTIVA'");
        $q->execute([$usuarioId,$beneficiarioId,$anio,$mes]); return (int)$q->fetchColumn()>0;
    }

    /** Guarda el reporte consolidado en la tabla reportes_mensuales */
    public function guardar(array $fila): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO reportes_mensuales
               (anio, mes, codigo_empleado, nombre_empleado,
                almuerzos_titular, monto_titular,
                detalle_familiares, almuerzos_familiares,
                penalizacion_familiares, total_descuento)
             VALUES
               (:anio, :mes, :codigo, :nombre,
                :alm_t, :monto_t,
                :det_f, :alm_f, :pen_f, :total)
             ON DUPLICATE KEY UPDATE
               almuerzos_titular       = VALUES(almuerzos_titular),
               monto_titular           = VALUES(monto_titular),
               detalle_familiares      = VALUES(detalle_familiares),
               almuerzos_familiares    = VALUES(almuerzos_familiares),
               penalizacion_familiares = VALUES(penalizacion_familiares),
               total_descuento         = VALUES(total_descuento),
               generado_en             = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            ':anio'   => $fila['anio'],
            ':mes'    => $fila['mes'],
            ':codigo' => $fila['codigo_empleado'],
            ':nombre' => $fila['nombre_empleado'],
            ':alm_t'  => $fila['almuerzos_titular'],
            ':monto_t'=> $fila['monto_titular'],
            ':det_f'  => json_encode($fila['detalle_familiares'] ?? [], JSON_UNESCAPED_UNICODE),
            ':alm_f'  => $fila['almuerzos_familiares'],
            ':pen_f'  => $fila['penalizacion_familiares'],
            ':total'  => $fila['total_descuento'],
        ]);
        return true;
    }

    /** Lista los reportes generados */
    public function listar(int $anio, int $mes): array {
        $stmt = $this->db->prepare(
            'SELECT * FROM reportes_mensuales
             WHERE anio = :anio AND mes = :mes
             ORDER BY nombre_empleado ASC'
        );
        $stmt->execute([':anio' => $anio, ':mes' => $mes]);
        return $stmt->fetchAll();
    }
}
