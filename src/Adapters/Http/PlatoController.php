<?php
declare(strict_types=1);

namespace RumboAlCielo\Adapters\Http;

use RumboAlCielo\Application\Plato\ActivarPlato;
use RumboAlCielo\Application\Plato\DesactivarPlato;
use RumboAlCielo\Application\Plato\ListarPlatos;
use RumboAlCielo\Application\Plato\ModificarPlato;
use RumboAlCielo\Application\Plato\RegistrarPlato;
use RumboAlCielo\Domain\Plato\Plato;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;

/**
 * Adaptador de entrada: traduce la petición HTTP a casos de uso y la
 * respuesta a JSON. No contiene reglas de negocio.
 */
final class PlatoController
{
    private RegistrarPlato $registrar;
    private ModificarPlato $modificar;
    private DesactivarPlato $desactivar;
    private ActivarPlato $activar;
    private ListarPlatos $listar;

    public function __construct(
        RegistrarPlato $registrar,
        ModificarPlato $modificar,
        DesactivarPlato $desactivar,
        ActivarPlato $activar,
        ListarPlatos $listar
    ) {
        $this->registrar  = $registrar;
        $this->modificar  = $modificar;
        $this->desactivar = $desactivar;
        $this->activar    = $activar;
        $this->listar     = $listar;
    }

    public function registrar(array $in): array
    {
        return $this->responder(function () use ($in) {
            $plato = $this->registrar->execute(
                (string) ($in['nombre'] ?? ''),
                (string) ($in['tipo'] ?? ''),
                $in['descripcion'] ?? null
            );
            return ['success' => true, 'message' => 'Plato registrado correctamente.', 'data' => self::aArray($plato)];
        });
    }

    public function modificar(array $in): array
    {
        return $this->responder(function () use ($in) {
            $plato = $this->modificar->execute(
                (int) ($in['id'] ?? 0),
                (string) ($in['nombre'] ?? ''),
                (string) ($in['tipo'] ?? ''),
                $in['descripcion'] ?? null,
                date('Y-m-d')
            );
            return ['success' => true, 'message' => 'Plato actualizado correctamente.', 'data' => self::aArray($plato)];
        });
    }

    public function desactivar(array $in): array
    {
        return $this->responder(function () use ($in) {
            $this->desactivar->execute((int) ($in['id'] ?? 0), date('Y-m-d'));
            return ['success' => true, 'message' => 'Plato desactivado.'];
        });
    }

    public function activar(array $in): array
    {
        return $this->responder(function () use ($in) {
            $this->activar->execute((int) ($in['id'] ?? 0));
            return ['success' => true, 'message' => 'Plato activado.'];
        });
    }

    public function listar(array $in): array
    {
        return $this->responder(function () use ($in) {
            $platos = $this->listar->execute(
                $in['tipo'] ?? null,
                ($in['solo_activos'] ?? '0') === '1'
            );
            return ['success' => true, 'data' => array_map([self::class, 'aArray'], $platos)];
        });
    }

    private function responder(callable $accion): array
    {
        try {
            return $accion();
        } catch (ReglaDeNegocioException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function aArray(Plato $p): array
    {
        return [
            'id'          => $p->id(),
            'nombre'      => $p->nombre(),
            'tipo'        => $p->tipo(),
            'descripcion' => $p->descripcion(),
            'activo'      => $p->estaActivo(),
        ];
    }
}
