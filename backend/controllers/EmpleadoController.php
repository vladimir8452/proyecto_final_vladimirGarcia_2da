<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/autoload.php';
require_once __DIR__ . '/../models/Usuario.php';

class EmpleadoController {
    private \RumboAlCielo\Application\Services\EmpleadoService $useCase;
    public function __construct() { $this->useCase = \RumboAlCielo\Infrastructure\Contenedor::empleadoService(); }
    public function registrar(array $data): array { return $this->useCase->registrar($data); }
    public function actualizar(int $id, array $data): array { return $this->useCase->actualizar($id, $data); }
    public function cambiarEstado(int $id, int $activo): array { return $this->useCase->cambiarEstado($id, $activo); }
    public function listar(): array { return $this->useCase->listar(); }
    public function obtener(int $id): array|false { return $this->useCase->obtener($id); }
}
