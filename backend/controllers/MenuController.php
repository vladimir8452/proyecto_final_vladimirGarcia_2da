<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/autoload.php';
require_once __DIR__ . '/../models/Menu.php';
require_once __DIR__ . '/../models/Calendario.php';

class MenuController {
    private \RumboAlCielo\Application\Services\MenuService $useCase;
    public function __construct() { $this->useCase = \RumboAlCielo\Infrastructure\Contenedor::menuService(); }
    public function publicar(string $fecha, string $descripcion, int $adminId): array { return $this->useCase->publicar($fecha, $descripcion, $adminId); }
    public function menuSemanaActual(): array { return $this->useCase->menuSemanaActual(); }
    public function menuSemana(string $fechaInicio): array { return $this->useCase->menuSemana($fechaInicio); }
    public function menuDelDia(string $fecha): array|false { return $this->useCase->menuDelDia($fecha); }
    public function listarTodos(int $pagina = 1, int $porPagina = 30): array { return $this->useCase->listarTodos($pagina, $porPagina); }
    public function eliminar(int $menuId): array { return $this->useCase->eliminar($menuId); }
}
