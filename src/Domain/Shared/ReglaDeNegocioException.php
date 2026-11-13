<?php
declare(strict_types=1);

namespace RumboAlCielo\Domain\Shared;

/** Se lanza cuando una operación incumple una regla del negocio. */
class ReglaDeNegocioException extends \DomainException
{
}
