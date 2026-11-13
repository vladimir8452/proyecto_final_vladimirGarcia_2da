<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Reserva.php';
require_once __DIR__ . '/../models/Familiar.php';
require_once __DIR__ . '/../models/Calendario.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Menu.php';
require_once __DIR__ . '/../models/Configuracion.php';

class ReservaController {
    private \RumboAlCielo\Application\Services\ReservaService $useCase;
    public function __construct() { $this->useCase = \RumboAlCielo\Infrastructure\Contenedor::reservaService(); }
    public function reservarTitular(int $usuarioId, string $fecha): array { return $this->useCase->reservarTitular($usuarioId, $fecha); }
    public function reservarFamiliar(int $titularId, int $familiarId, string $fecha): array { return $this->useCase->reservarFamiliar($titularId, $familiarId, $fecha); }
    public function cancelar(int $reservaId, int $titularId): array { return $this->useCase->cancelar($reservaId, $titularId); }
    public function registrarEsporadicaAdmin(int $usuarioId, int $familiarId, string $fecha): array {
        $resultado = $this->useCase->registrarEsporadicaAdmin($usuarioId, $familiarId, $fecha);
        $correo = $resultado['correo_notificacion'] ?? null;
        if ($correo) @mail($correo, 'Reserva de almuerzo confirmada', 'Tu reserva para el '.$fecha.' fue confirmada por Bs '.number_format((float)($resultado['tarifa_notificacion'] ?? 0),2).'.', 'From: sistema@rumboalcielo.bo');
        unset($resultado['correo_notificacion'], $resultado['fecha_notificacion'], $resultado['tarifa_notificacion']);
        return $resultado;
    }
    public function listarMisReservas(int $titularId, ?string $fechaInicio = null, ?string $fechaFin = null): array { return $this->useCase->listarMisReservas($titularId, $fechaInicio, $fechaFin); }
    public function listarPorFecha(string $fecha): array { return $this->useCase->listarPorFecha($fecha); }
}
