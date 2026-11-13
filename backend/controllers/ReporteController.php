<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reporte.php';
require_once __DIR__ . '/../models/Calendario.php';
require_once __DIR__ . '/../models/Configuracion.php';

class ReporteController {
    private \RumboAlCielo\Application\Services\ReporteService $useCase;
    public function __construct() { $this->useCase = \RumboAlCielo\Infrastructure\Contenedor::reporteService(); }
    public function generarReporteMensual(int $anio, int $mes): array { return $this->useCase->generarReporteMensual($anio, $mes); }
    public function obtenerReporte(int $anio, int $mes): array { return $this->useCase->obtenerReporte($anio, $mes); }
}
