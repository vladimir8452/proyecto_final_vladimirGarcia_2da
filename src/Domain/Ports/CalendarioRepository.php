<?php
declare(strict_types=1); namespace RumboAlCielo\Domain\Ports;
interface CalendarioRepository { public function esDiaOperativo(string $fecha); public function diasOperativosMes(int $anio,int $mes); }
