<?php
declare(strict_types=1);
namespace RumboAlCielo\Domain\Shared;
interface RepositorioRegistros {
    public function guardar(Registro $registro): void;
    public function buscar(int $id): ?Registro;
    /** @return Registro[] */ public function listar(array $filtros = []): array;
    public function eliminar(int $id): bool;
}
