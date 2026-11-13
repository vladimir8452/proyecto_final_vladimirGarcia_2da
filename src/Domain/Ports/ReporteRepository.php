<?php
declare(strict_types=1); namespace RumboAlCielo\Domain\Ports;
interface ReporteRepository { public function datosParaReporteMensual(int $anio,int $mes); public function datosNoPensionadosMensual(int $anio,int $mes); public function tieneInscripcionPension(int $usuarioId,int $beneficiarioId,int $anio,int $mes); public function guardar(array $fila); public function listar(int $anio,int $mes); }
