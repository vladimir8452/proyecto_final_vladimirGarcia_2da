<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reporte.php';
require_once __DIR__ . '/../models/Calendario.php';

class ReporteController {
    private Reporte $reporteModel;
    private Calendario $calendarioModel;

    public function __construct() {
        $this->reporteModel = new Reporte();
        $this->calendarioModel = new Calendario();
    }

    private function parametro(string $clave, string $defecto): string {
        try { $db=Database::getConnection(); $q=$db->prepare('SELECT valor FROM configuracion_sistema WHERE clave=?'); $q->execute([$clave]); return (string)($q->fetchColumn() ?: $defecto); } catch (Throwable $e) { return $defecto; }
    }

    /**
     * Calcula el detalle de familiares de un titular.
     * Si el familiar está en pensión, se aplica el mínimo mensual; si no, solo lo consumido.
     * @return array [detalle, totalAlmuerzos, totalPenalizacion, totalMonto]
     */
    private function detalleFamiliares(array $familiares, int $titularId, int $anio, int $mes, int $minimo, float $tarifaPension): array {
        $detalle = []; $totalAlm = 0; $totalPen = 0.00; $totalMonto = 0.00;
        foreach ($familiares as $fam) {
            $almFam   = (int) $fam['almuerzos'];
            $montoFam = (float) $fam['monto_reservas'];
            $tienePension = (int)$fam['id'] > 0 && ((int)($fam['almuerzos_pension'] ?? 0) > 0
                || $this->reporteModel->tieneInscripcionPension($titularId, (int)$fam['id'], $anio, $mes));
            $pen = $tienePension ? max(0, ($minimo * $tarifaPension) - $montoFam) : 0.00;
            $detalle[] = [
                'nombre'         => $fam['nombre'],
                'almuerzos'      => $almFam,
                'monto'          => $montoFam,
                'penalizacion'   => $pen,
                'total_familiar' => $montoFam + $pen,
            ];
            $totalAlm += $almFam; $totalPen += $pen; $totalMonto += $montoFam;
        }
        return [$detalle, $totalAlm, $totalPen, $totalMonto];
    }

    /**
     * Genera y persiste el reporte consolidado mensual.
     *
     * Reglas:
     *  - Pensionado (titular o familiar inscrito): tarifa de pensión, con mínimo mensual.
     *    El mínimo se limita a los días operativos del mes (los feriados no generan cobro obligatorio).
     *  - Esporádico / NO_PENSIONADO: tarifa de cada reserva, sin mínimo.
     *  - Todo cargo de familiares va al código del titular, sea cual sea su tipo de cuenta.
     */
    public function generarReporteMensual(int $anio, int $mes): array {
        $filas = [];
        $minimo = (int) $this->parametro('minimo_pension', (string) MINIMO_ALMUERZOS_MES);
        $minimo = min($minimo, $this->calendarioModel->diasOperativosMes($anio, $mes));
        $tarifaPension = (float) $this->parametro('tarifa_pensionado', (string) TARIFA_PENSIONADO);

        // ── Pensionados ──────────────────────────────────────────────
        foreach ($this->reporteModel->datosParaReporteMensual($anio, $mes) as $titular) {
            $tienePensionTitular = (int)($titular['almuerzos_pension'] ?? 0) > 0
                || $this->reporteModel->tieneInscripcionPension((int)$titular['id'], 0, $anio, $mes);
            $montoTitular = $tienePensionTitular
                ? max($minimo * $tarifaPension, (float) $titular['monto_reservas'])
                : (float) $titular['monto_reservas'];

            [$detalle, $almFam, $penFam, $montoFam] = $this->detalleFamiliares(
                $titular['familiares'], (int)$titular['id'], $anio, $mes, $minimo, $tarifaPension
            );

            $fila = [
                'anio' => $anio, 'mes' => $mes,
                'codigo_empleado'         => $titular['codigo_empleado'] ?? 'SIN_CODIGO',
                'nombre_empleado'         => $titular['nombre'],
                'almuerzos_titular'       => (int) $titular['almuerzos_titular'],
                'monto_titular'           => $montoTitular,
                'detalle_familiares'      => $detalle,
                'almuerzos_familiares'    => $almFam,
                'penalizacion_familiares' => $penFam,
                'total_descuento'         => $montoTitular + $montoFam + $penFam,
            ];
            $this->reporteModel->guardar($fila);
            $filas[] = $fila;
        }

        // ── No Pensionados (pago por consumo, sin mínimo; incluye sus familiares) ──
        foreach ($this->reporteModel->datosNoPensionadosMensual($anio, $mes) as $np) {
            $monto = (float) $np['monto_reservas'];
            [$detalle, $almFam, $penFam, $montoFam] = $this->detalleFamiliares(
                $np['familiares'], (int)$np['id'], $anio, $mes, $minimo, $tarifaPension
            );

            $fila = [
                'anio' => $anio, 'mes' => $mes,
                'codigo_empleado'         => $np['codigo_empleado'] ?? 'SIN_CODIGO',
                'nombre_empleado'         => $np['nombre'],
                'almuerzos_titular'       => (int) $np['almuerzos_titular'],
                'monto_titular'           => $monto,
                'detalle_familiares'      => $detalle,
                'almuerzos_familiares'    => $almFam,
                'penalizacion_familiares' => $penFam,
                'total_descuento'         => $monto + $montoFam + $penFam,
            ];
            $this->reporteModel->guardar($fila);
            $filas[] = $fila;
        }

        return [
            'success' => true,
            'message' => 'Reporte generado correctamente.',
            'total_empleados' => count($filas),
            'filas' => $filas,
        ];
    }

    /**
     * Obtiene el reporte ya generado de un mes.
     */
    public function obtenerReporte(int $anio, int $mes): array {
        return $this->reporteModel->listar($anio, $mes);
    }
}
