<?php
declare(strict_types=1);
namespace RumboAlCielo\Domain\Reserva;
use RumboAlCielo\Domain\Shared\Registro;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
/** Entidad de dominio del módulo Reserva; no depende de HTTP, PDO ni framework. */
final class Reserva extends Registro
{
    public function __construct(?int $id, array $atributos)
    {
        $fecha=(string)($atributos['fecha_reserva'] ?? ''); if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha) || !in_array($atributos['tipo_beneficiario'] ?? '', ['TITULAR','FAMILIAR'], true) || !in_array($atributos['modalidad'] ?? 'ESPORADICO', ['PENSIONADO','ESPORADICO'], true)) throw new ReglaDeNegocioException('Los datos de la reserva no son válidos.');
        if (isset($atributos['estado']) && !in_array($atributos['estado'], ['RESERVADO','CANCELADO','ENTREGADO','INASISTENCIA'], true)) throw new ReglaDeNegocioException('Estado de reserva no válido.');
        if (isset($atributos['tarifa']) && (float)$atributos['tarifa']<0) throw new ReglaDeNegocioException('La tarifa no puede ser negativa.');
        parent::__construct($id, $atributos);
    }
}
