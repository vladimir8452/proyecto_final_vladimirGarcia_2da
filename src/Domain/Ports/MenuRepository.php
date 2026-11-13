<?php
declare(strict_types=1); namespace RumboAlCielo\Domain\Ports;
interface MenuRepository { public function findByFecha(string $fecha); public function listarSemana(string $fechaInicio); public function listarTodos(int $limit=30,int $offset=0); public function guardar(string $fecha,string $descripcion,int $adminId); public function eliminar(int $id); public function findById(int $id); public function guardarMenuConPlatos(array $datos); }
