<?php
require_once __DIR__ . '/../../src/autoload.php';
use RumboAlCielo\Domain\Ports\CalendarioRepository;
require_once __DIR__ . '/../config/database.php';

class Calendario implements CalendarioRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /** Obtiene el registro de calendario para una fecha específica */
    public function findByFecha(string $fecha): array|false {
        $stmt = $this->db->prepare(
            'SELECT * FROM calendario_operativo WHERE fecha = :fecha LIMIT 1'
        );
        $stmt->execute([':fecha' => $fecha]);
        return $stmt->fetch();
    }

    /** Lista todos los días del calendario de un mes/año dado */
    public function listarPorMes(int $anio, int $mes): array {
        $inicio = sprintf('%04d-%02d-01', $anio, $mes);
        // Usamos DateTime para calcular el último día del mes — evita strtotime
        $dt  = new DateTime($inicio);
        $fin = $dt->format('Y-m-t');

        $stmt = $this->db->prepare(
            'SELECT * FROM calendario_operativo
             WHERE fecha BETWEEN :inicio AND :fin
             ORDER BY fecha ASC'
        );
        $stmt->execute([':inicio' => $inicio, ':fin' => $fin]);
        return $stmt->fetchAll();
    }

    /** Lista todos los feriados registrados */
    public function listarFeriados(): array {
        $stmt = $this->db->query(
            'SELECT * FROM calendario_operativo
             WHERE es_feriado = 1
             ORDER BY fecha DESC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Crea o actualiza un día en el calendario.
     * Usa INSERT ... ON DUPLICATE KEY UPDATE para idempotencia.
     */
    public function guardar(string $fecha, bool $esFeriado, string $descripcion = ''): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO calendario_operativo (fecha, es_feriado, descripcion_feriado)
             VALUES (:fecha, :feriado, :desc)
             ON DUPLICATE KEY UPDATE
               es_feriado          = VALUES(es_feriado),
               descripcion_feriado = VALUES(descripcion_feriado)'
        );
        $stmt->execute([
            ':fecha'   => $fecha,
            ':feriado' => (int) $esFeriado,
            ':desc'    => $descripcion ?: null,
        ]);
        return true;
    }

    /**
     * Verifica si una fecha es operativa:
     *   - Debe ser Lunes a Jueves (N = 1..4 en ISO-8601)
     *   - No debe estar marcada como feriado en el calendario
     *
     * Usa DateTime en lugar de strtotime() para evitar falsos negativos
     * con fechas futuras en entornos Windows.
     */
    public function esDiaOperativo(string $fecha): bool {
        // Validar formato antes de instanciar DateTime
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$dt || $dt->format('Y-m-d') !== $fecha) {
            return false; // fecha con formato inválido
        }

        // ISO-8601: 1=Lun, 2=Mar, 3=Mié, 4=Jue, 5=Vie, 6=Sáb, 7=Dom
        $diaSemana = (int) $dt->format('N');
        if ($diaSemana < 1 || $diaSemana > 4) {
            return false; // solo L-J
        }

        $registro = $this->findByFecha($fecha);
        if ($registro && (bool) $registro['es_feriado']) {
            return false; // feriado marcado por el admin
        }

        return true;
    }

    /**
     * Genera la lista de días L-J de un mes con su estado operativo.
     * También usa DateTime para los cálculos de rango.
     */
    public function calendarioMes(int $anio, int $mes): array {
        $inicio    = new DateTime(sprintf('%04d-%02d-01', $anio, $mes));
        $totalDias = (int) $inicio->format('t');
        $resultado = [];

        for ($d = 1; $d <= $totalDias; $d++) {
            $dt        = new DateTime(sprintf('%04d-%02d-%02d', $anio, $mes, $d));
            $diaSemana = (int) $dt->format('N');

            if ($diaSemana >= 1 && $diaSemana <= 4) {
                $fecha    = $dt->format('Y-m-d');
                $registro = $this->findByFecha($fecha);

                $resultado[] = [
                    'fecha'       => $fecha,
                    'dia_semana'  => $diaSemana,
                    'nombre_dia'  => ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves'][$diaSemana],
                    'es_feriado'  => $registro ? (bool) $registro['es_feriado'] : false,
                    'descripcion' => $registro['descripcion_feriado'] ?? '',
                    'operativo'   => !($registro && (bool) $registro['es_feriado']),
                ];
            }
        }

        return $resultado;
    }

    /** Cuenta los días operativos (L-J sin feriados) de un mes. */
    public function diasOperativosMes(int $anio, int $mes): int {
        $n = 0;
        foreach ($this->calendarioMes($anio, $mes) as $d) {
            if ($d['operativo']) $n++;
        }
        return $n;
    }

    /** Elimina un registro del calendario */
    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare('DELETE FROM calendario_operativo WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
