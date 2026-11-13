<?php
declare(strict_types=1);
namespace RumboAlCielo\Adapters\Http;
use RumboAlCielo\Application\Pension\GestionarInscripcionPension;
final class PensionController
{
    public function __construct(private GestionarInscripcionPension $useCase) {}
    public function estado(int $uid,int $beneficiario,int $anio,int $mes): array { return $this->useCase->estado($uid,$beneficiario,$anio,$mes); }
    public function inscribir(int $uid,int $beneficiario,int $anio,int $mes,string $tipoCuenta): array { return $this->useCase->inscribir($uid,$beneficiario,$anio,$mes,$tipoCuenta); }
}
