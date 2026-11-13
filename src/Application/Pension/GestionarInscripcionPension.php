<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Pension;
use DateTime;
use RumboAlCielo\Domain\Ports\PensionRepository;
final class GestionarInscripcionPension
{
    public function __construct(private PensionRepository $repository) {}
    public function estado(int $usuarioId,int $beneficiarioId,int $anio,int $mes): array
    {
        if($mes<1||$mes>12||!checkdate($mes,1,$anio)) return ['success'=>false,'message'=>'Mes o año inválido.'];
        return ['success'=>true,'data'=>$this->repository->obtenerInscripcion($usuarioId,$beneficiarioId,$anio,$mes)];
    }
    public function inscribir(int $usuarioId,int $beneficiarioId,int $anio,int $mes,string $tipoCuenta): array
    {
        if($tipoCuenta!=='PENSIONADO') return ['success'=>false,'message'=>'Solo las cuentas pensionadas pueden inscribirse.'];
        if($mes<1||$mes>12||!checkdate($mes,1,$anio)) return ['success'=>false,'message'=>'Mes o año inválido.'];
        $inicio=DateTime::createFromFormat('!Y-n-j',"$anio-$mes-1");
        if(!$inicio || $inicio<=new DateTime(date('Y-m-01'))) return ['success'=>false,'message'=>'La inscripción debe realizarse antes de iniciar el mes correspondiente.'];
        if($beneficiarioId>0&&!$this->repository->familiarActivoPertenece($beneficiarioId,$usuarioId)) return ['success'=>false,'message'=>'El familiar seleccionado no pertenece a tu cuenta o está inactivo.'];
        $this->repository->guardarInscripcion($usuarioId,$beneficiarioId,$anio,$mes);
        $tarifa=$this->repository->tarifaPension();
        $desde=$inicio->format('Y-m-01');$hasta=$inicio->format('Y-m-t');
        $feriados=$this->repository->feriados($desde,$hasta);$creadas=0;
        $cursor=clone $inicio;$cursor->modify('first day of this month');
        while($cursor->format('Y-m')===$inicio->format('Y-m')){
            $fecha=$cursor->format('Y-m-d');
            if((int)$cursor->format('N')<=4&&!in_array($fecha,$feriados,true)&&!$this->repository->reservaExiste($usuarioId,$beneficiarioId,$fecha)){
                if($this->repository->crearReservaPension($usuarioId,$beneficiarioId,$tarifa,$fecha))$creadas++;
            }
            $cursor->modify('+1 day');
        }
        return ['success'=>true,'message'=>'Inscripción mensual registrada. Se confirmaron automáticamente '.$creadas.' almuerzos de lunes a jueves.','reservas_creadas'=>$creadas];
    }
}
