<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/autoload.php';
use RumboAlCielo\Domain\Ports\ConfiguracionRepository;
require_once __DIR__ . '/../config/database.php';
/** Adaptador de persistencia para parámetros configurables del negocio. */
class Configuracion
{
    private PDO $db;
    public function __construct() { $this->db = Database::getConnection(); }
    public function obtener(string $clave, string $defecto): string
    {
        $q=$this->db->prepare('SELECT valor FROM configuracion_sistema WHERE clave=?');
        $q->execute([$clave]); $valor=$q->fetchColumn();
        return $valor===false || $valor===null || $valor==='' ? $defecto : (string)$valor;
    }
    public function tieneInscripcionPension(int $usuarioId,int $beneficiarioId,int $anio,int $mes): bool
    {
        $q=$this->db->prepare("SELECT COUNT(*) FROM inscripciones_pension WHERE usuario_id=? AND beneficiario_id=? AND anio=? AND mes=? AND estado='ACTIVA'");
        $q->execute([$usuarioId,$beneficiarioId,$anio,$mes]); return (int)$q->fetchColumn()>0;
    }
}
