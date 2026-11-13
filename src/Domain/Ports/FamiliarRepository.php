<?php
declare(strict_types=1); namespace RumboAlCielo\Domain\Ports;
interface FamiliarRepository { public function listarPorTitular(int $titularId); public function findById(int $id); public function ciExiste(string $ci,int $excluirId=0); public function crear(int $titularId,string $nombre,string $parentesco='',string $ci=''); public function cambiarEstado(int $id,string $estado); public function actualizar(int $id,string $nombre,string $parentesco='',string $ci=''); }
