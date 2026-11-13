<?php
declare(strict_types=1);

namespace RumboAlCielo\Domain\Plato;

/**
 * Puerto de salida: el dominio y los casos de uso solo conocen esta
 * interfaz; la implementación (PDO, memoria, etc.) vive en las capas externas.
 */
interface PlatoRepository
{
    /** Inserta (y asigna id) o actualiza el plato. */
    public function guardar(Plato $plato): void;

    public function buscarPorId(int $id): ?Plato;

    /** ¿Existe otro plato con el mismo nombre y tipo? */
    public function existeConNombreYTipo(string $nombre, string $tipo, ?int $excluirId = null): bool;

    /** @return Plato[] ordenados por tipo y nombre */
    public function listar(?string $tipo = null, bool $soloActivos = false): array;

    /** ¿El plato figura en algún menú publicado con fecha >= $fechaDesde (Y-m-d)? */
    public function estaEnMenuDesde(int $platoId, string $fechaDesde): bool;
}
