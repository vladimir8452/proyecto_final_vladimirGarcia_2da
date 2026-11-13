<?php
declare(strict_types=1);
namespace RumboAlCielo\Domain\Menu;
use RumboAlCielo\Domain\Shared\Registro;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
/** Entidad de dominio del módulo Menu; no depende de HTTP, PDO ni framework. */
final class Menu extends Registro
{
    public function __construct(?int $id, array $atributos)
    {
        $fecha=(string)($atributos['fecha'] ?? ''); if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha) || (int)($atributos['created_by'] ?? 0)<1) throw new ReglaDeNegocioException('El menú requiere una fecha válida y un usuario publicador.');
        $fechaObj=\DateTime::createFromFormat('!Y-m-d',$fecha); if (!$fechaObj || $fechaObj->format('Y-m-d')!==$fecha) throw new ReglaDeNegocioException('La fecha del menú no existe.');
        parent::__construct($id, $atributos);
    }
}
