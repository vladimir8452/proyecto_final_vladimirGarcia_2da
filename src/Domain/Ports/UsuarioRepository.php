<?php
declare(strict_types=1); namespace RumboAlCielo\Domain\Ports;
interface UsuarioRepository { public function findById(int $id); public function findByEmail(string $correo); public function correoExiste(string $correo,int $excluirId=0); public function ciExiste(string $ci,int $excluirId=0); public function codigoExiste(string $codigo,int $excluirId=0); public function crear(array $data); public function actualizar(int $id,array $data); public function cambiarEstado(int $id,int $activo); public function listarEmpleados(); }
