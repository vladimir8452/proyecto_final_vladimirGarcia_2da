<?php
declare(strict_types=1); namespace RumboAlCielo\Domain\Ports;
interface ConfiguracionRepository { public function obtener(string $clave,string $defecto); public function tieneInscripcionPension(int $usuarioId,int $beneficiarioId,int $anio,int $mes); }
