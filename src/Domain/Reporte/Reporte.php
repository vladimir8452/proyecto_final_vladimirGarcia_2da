<?php
declare(strict_types=1);
namespace RumboAlCielo\Domain\Reporte;
use RumboAlCielo\Domain\Shared\Registro;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
/** Entidad de dominio del módulo Reporte; no depende de HTTP, PDO ni framework. */
final class Reporte extends Registro
{
    public function __construct(?int $id, array $atributos)
    {
        $mes=(int)($atributos['mes'] ?? 0); if ((int)($atributos['anio'] ?? 0)<2000 || $mes<1 || $mes>12 || trim((string)($atributos['codigo_empleado'] ?? ''))==='') throw new ReglaDeNegocioException('El reporte requiere código de empleado, año y mes válidos.');
        parent::__construct($id, $atributos);
    }
}
