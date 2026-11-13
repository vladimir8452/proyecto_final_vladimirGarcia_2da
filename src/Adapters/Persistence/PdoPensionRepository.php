<?php
declare(strict_types=1);
namespace RumboAlCielo\Adapters\Persistence;
use PDO;
use RumboAlCielo\Domain\Ports\PensionRepository;
final class PdoPensionRepository implements PensionRepository
{
    public function __construct(private PDO $db) {}
    public function obtenerInscripcion(int $usuarioId, int $beneficiarioId, int $anio, int $mes): ?array
    {
        $q=$this->db->prepare("SELECT * FROM inscripciones_pension WHERE usuario_id=? AND beneficiario_id=? AND anio=? AND mes=? AND estado='ACTIVA' LIMIT 1");
        $q->execute([$usuarioId,$beneficiarioId,$anio,$mes]); $r=$q->fetch(PDO::FETCH_ASSOC); return $r ?: null;
    }
    public function familiarActivoPertenece(int $familiarId, int $usuarioId): bool
    {
        $q=$this->db->prepare("SELECT COUNT(*) FROM familiares WHERE id=? AND empleado_titular_id=? AND estado='ACTIVO'");
        $q->execute([$familiarId,$usuarioId]); return (int)$q->fetchColumn()>0;
    }
    public function guardarInscripcion(int $usuarioId, int $beneficiarioId, int $anio, int $mes): void
    {
        $q=$this->db->prepare("INSERT INTO inscripciones_pension(usuario_id,beneficiario_id,anio,mes,estado) VALUES(?,?,?,?, 'ACTIVA') ON DUPLICATE KEY UPDATE estado='ACTIVA', registrado_en=CURRENT_TIMESTAMP");
        $q->execute([$usuarioId,$beneficiarioId,$anio,$mes]);
    }
    public function tarifaPension(): float
    {
        $q=$this->db->prepare("SELECT valor FROM configuracion_sistema WHERE clave='tarifa_pensionado'"); $q->execute(); return (float)($q->fetchColumn() ?: 15);
    }
    public function feriados(string $desde, string $hasta): array
    {
        $q=$this->db->prepare('SELECT fecha FROM calendario_operativo WHERE es_feriado=1 AND fecha BETWEEN ? AND ?');
        $q->execute([$desde,$hasta]); return array_column($q->fetchAll(PDO::FETCH_ASSOC),'fecha');
    }
    public function reservaExiste(int $usuarioId, int $beneficiarioId, string $fecha): bool
    {
        if($beneficiarioId>0){$q=$this->db->prepare('SELECT COUNT(*) FROM reservas WHERE familiar_id=? AND fecha_reserva=?');$q->execute([$beneficiarioId,$fecha]);}
        else {$q=$this->db->prepare("SELECT COUNT(*) FROM reservas WHERE usuario_id=? AND tipo_beneficiario='TITULAR' AND fecha_reserva=?");$q->execute([$usuarioId,$fecha]);}
        return (int)$q->fetchColumn()>0;
    }
    public function crearReservaPension(int $usuarioId, int $beneficiarioId, float $tarifa, string $fecha): bool
    {
        if($beneficiarioId>0){$q=$this->db->prepare("INSERT IGNORE INTO reservas(usuario_id,familiar_id,tipo_beneficiario,modalidad,tarifa,fecha_reserva,estado) VALUES(?,?,'FAMILIAR','PENSIONADO',?,?,'RESERVADO')");$q->execute([$usuarioId,$beneficiarioId,$tarifa,$fecha]);}
        else {$q=$this->db->prepare("INSERT IGNORE INTO reservas(usuario_id,familiar_id,tipo_beneficiario,modalidad,tarifa,fecha_reserva,estado) VALUES(?,NULL,'TITULAR','PENSIONADO',?,?,'RESERVADO')");$q->execute([$usuarioId,$tarifa,$fecha]);}
        return $q->rowCount()>0;
    }
}
