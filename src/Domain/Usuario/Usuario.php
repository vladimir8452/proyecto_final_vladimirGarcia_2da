<?php
declare(strict_types=1);
namespace RumboAlCielo\Domain\Usuario;
use RumboAlCielo\Domain\Shared\Registro;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
/** Entidad de dominio del módulo Usuario; no depende de HTTP, PDO ni framework. */
final class Usuario extends Registro
{
    public function __construct(?int $id, array $atributos)
    {
        if (trim((string)($atributos['nombre'] ?? '')) === '' || trim((string)($atributos['correo'] ?? '')) === '') throw new ReglaDeNegocioException('El nombre y el correo del usuario son obligatorios.');
        parent::__construct($id, $atributos);
    }
}
