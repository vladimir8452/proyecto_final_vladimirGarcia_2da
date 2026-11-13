<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/autoload.php';
require_once __DIR__ . '/../models/Familiar.php';
require_once __DIR__ . '/../models/Usuario.php';

class FamiliarController {
    private \RumboAlCielo\Application\Services\FamiliarService $useCase;
    public function __construct() { $this->useCase = \RumboAlCielo\Infrastructure\Contenedor::familiarService(); }
    public function registrar(int $titularId, string $nombre, string $parentesco, string $ci): array { return $this->useCase->registrar($titularId, $nombre, $parentesco, $ci); }
    public function actualizar(int $titularId, int $familiarId, string $nombre, string $parentesco, string $ci): array { return $this->useCase->actualizar($titularId, $familiarId, $nombre, $parentesco, $ci); }
    public function desactivar(int $titularId, int $familiarId): array { return $this->useCase->desactivar($titularId, $familiarId); }
    public function listar(int $titularId): array { return $this->useCase->listar($titularId); }
    public function registrarDesdeAdmin(int $titularId, string $nombre, string $parentesco, string $ci): array { return $this->useCase->registrarDesdeAdmin($titularId, $nombre, $parentesco, $ci); }
}
