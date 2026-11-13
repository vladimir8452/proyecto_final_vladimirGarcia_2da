<?php
declare(strict_types=1);
namespace RumboAlCielo\Domain\Familiar;
use RumboAlCielo\Domain\Shared\Registro;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
/** Entidad de dominio del módulo Familiar; no depende de HTTP, PDO ni framework. */
final class Familiar extends Registro
{
    public function __construct(?int $id, array $atributos)
    {
        if ((int)($atributos['empleado_titular_id'] ?? 0) < 1 || trim((string)($atributos['nombre'] ?? '')) === '') throw new ReglaDeNegocioException('El titular y el nombre del familiar son obligatorios.');
        if (isset($atributos['parentesco']) && $atributos['parentesco'] !== null && !in_array($atributos['parentesco'], ['PADRE','MADRE','CONYUGE','HIJO'], true)) throw new ReglaDeNegocioException('Parentesco no válido.');
        if (isset($atributos['estado']) && !in_array($atributos['estado'], ['ACTIVO','INACTIVO'], true)) throw new ReglaDeNegocioException('Estado de familiar no válido.');
        parent::__construct($id, $atributos);
    }
}
