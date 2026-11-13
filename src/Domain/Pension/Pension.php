<?php
declare(strict_types=1);
namespace RumboAlCielo\Domain\Pension;
use RumboAlCielo\Domain\Shared\Registro;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
/** Entidad de dominio del módulo Pension; no depende de HTTP, PDO ni framework. */
final class Pension extends Registro
{
    public function __construct(?int $id, array $atributos)
    {
        $mes=(int)($atributos['mes'] ?? 0); if ((int)($atributos['usuario_id'] ?? 0)<1 || (int)($atributos['anio'] ?? 0)<2000 || $mes<1 || $mes>12) throw new ReglaDeNegocioException('La inscripción requiere usuario, año y mes válidos.');
        if (isset($atributos['estado']) && !in_array($atributos['estado'], ['ACTIVA','CANCELADA'], true)) throw new ReglaDeNegocioException('Estado de inscripción no válido.');
        if ((int)($atributos['beneficiario_id'] ?? 0)<0) throw new ReglaDeNegocioException('Beneficiario no válido.');
        parent::__construct($id, $atributos);
    }
}
