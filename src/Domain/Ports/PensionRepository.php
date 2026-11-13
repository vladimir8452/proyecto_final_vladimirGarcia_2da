<?php
declare(strict_types=1);
namespace RumboAlCielo\Domain\Ports;
interface PensionRepository
{
    public function obtenerInscripcion(int $usuarioId, int $beneficiarioId, int $anio, int $mes): ?array;
    public function familiarActivoPertenece(int $familiarId, int $usuarioId): bool;
    public function guardarInscripcion(int $usuarioId, int $beneficiarioId, int $anio, int $mes): void;
    public function tarifaPension(): float;
    public function feriados(string $desde, string $hasta): array;
    public function reservaExiste(int $usuarioId, int $beneficiarioId, string $fecha): bool;
    public function crearReservaPension(int $usuarioId, int $beneficiarioId, float $tarifa, string $fecha): bool;
}
