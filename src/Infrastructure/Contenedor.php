<?php
declare(strict_types=1);

namespace RumboAlCielo\Infrastructure;

use RumboAlCielo\Adapters\Http\PlatoController;
use RumboAlCielo\Adapters\Persistence\PdoPlatoRepository;
use RumboAlCielo\Application\Plato\ActivarPlato;
use RumboAlCielo\Application\Plato\DesactivarPlato;
use RumboAlCielo\Application\Plato\ListarPlatos;
use RumboAlCielo\Application\Plato\ModificarPlato;
use RumboAlCielo\Application\Plato\RegistrarPlato;

/**
 * Raíz de composición: único lugar donde se conectan las capas
 * (infraestructura -> adaptadores -> casos de uso -> dominio).
 */
final class Contenedor
{
    public static function platoController(): PlatoController
    {
        require_once __DIR__ . '/../../backend/config/database.php';

        $repositorio = new PdoPlatoRepository(\Database::getConnection());

        return new PlatoController(
            new RegistrarPlato($repositorio),
            new ModificarPlato($repositorio),
            new DesactivarPlato($repositorio),
            new ActivarPlato($repositorio),
            new ListarPlatos($repositorio)
        );
    }
}
