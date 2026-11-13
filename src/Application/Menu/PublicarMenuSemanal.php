<?php
declare(strict_types=1);
namespace RumboAlCielo\Application\Menu;
use DateTime;
use RumboAlCielo\Domain\Ports\MenuRepository;
use RumboAlCielo\Domain\Ports\PlatoCatalogRepository;
use RumboAlCielo\Domain\Ports\CalendarioRepository;
final class PublicarMenuSemanal
{
    public function __construct(private MenuRepository $menus,private PlatoCatalogRepository $platos,private CalendarioRepository $calendario) {}
    public function ejecutar(array $selecciones,int $adminId): array
    {
        $errores=[];$exitos=[];$catalogo=$this->platos->listarCatalogo();
        $etiquetas=['SOPA'=>'la sopa','PRINCIPAL'=>'el plato principal','POSTRE'=>'el postre'];
        foreach($selecciones as $fecha=>$seleccion){
            if(!is_array($seleccion))continue;
            $ids=['SOPA'=>(int)($seleccion['sopa_id']??0),'PRINCIPAL'=>(int)($seleccion['principal_id']??0),'POSTRE'=>(int)($seleccion['postre_id']??0)];
            if(!array_filter($ids))continue;
            $dt=DateTime::createFromFormat('Y-m-d',(string)$fecha);
            if(!$dt||$dt->format('Y-m-d')!==$fecha){$errores[]='Fecha inválida recibida: '.htmlspecialchars((string)$fecha);continue;}
            $fechaCorta=$dt->format('d/m/Y');
            if(in_array(0,$ids,true)){$errores[]="Para el {$fechaCorta} debes elegir sopa, plato principal y postre.";continue;}
            $nombres=[];$invalido=false;
            foreach($ids as $tipo=>$id){$plato=$catalogo[$id]??null;if(!$plato||$plato['tipo']!==$tipo||(int)$plato['activo']!==1){$errores[]="El plato elegido para {$etiquetas[$tipo]} del {$fechaCorta} no es válido o está inactivo.";$invalido=true;break;}$nombres[$tipo]=$plato['nombre'];}
            if($invalido)continue;
            if(!$this->calendario->esDiaOperativo($fecha)){$errores[]='No se puede publicar menú para '.$fechaCorta.': día no operativo o feriado.';continue;}
            $descripcion="Sopa: {$nombres['SOPA']}\nPlato principal: {$nombres['PRINCIPAL']}\nPostre: {$nombres['POSTRE']}";
            $this->menus->guardarMenuConPlatos(['fecha'=>$fecha,'sopa_id'=>$ids['SOPA'],'principal_id'=>$ids['PRINCIPAL'],'postre_id'=>$ids['POSTRE'],'sopa'=>$nombres['SOPA'],'plato'=>$nombres['PRINCIPAL'],'postre'=>$nombres['POSTRE'],'descripcion'=>$descripcion,'admin'=>$adminId]);
            $resumen=function_exists('mb_substr')?mb_substr($descripcion,0,60):substr($descripcion,0,60);$longitud=function_exists('mb_strlen')?mb_strlen($descripcion):strlen($descripcion);$exitos[]=$fechaCorta.' — '.$resumen.($longitud>60?'…':'');
        }
        return ['errores'=>$errores,'exitos'=>$exitos];
    }
}
