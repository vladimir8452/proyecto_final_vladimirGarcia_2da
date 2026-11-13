<?php
declare(strict_types=1);

namespace RumboAlCielo\Domain\Plato;

use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;

/** Tipos de plato permitidos (PHP 8.0 no tiene enum nativo). */
final class TipoPlato
{
    public const SOPA      = 'SOPA';
    public const PRINCIPAL = 'PRINCIPAL';
    public const POSTRE    = 'POSTRE';

    /** @return string[] */
    public static function todos(): array
    {
        return [self::SOPA, self::PRINCIPAL, self::POSTRE];
    }

    /** Normaliza el valor o lanza excepción si no es un tipo válido. */
    public static function validar(string $tipo): string
    {
        $normalizado = strtoupper(trim($tipo));
        if (!in_array($normalizado, self::todos(), true)) {
            throw new ReglaDeNegocioException('El tipo de plato debe ser SOPA, PRINCIPAL o POSTRE.');
        }
        return $normalizado;
    }
}
