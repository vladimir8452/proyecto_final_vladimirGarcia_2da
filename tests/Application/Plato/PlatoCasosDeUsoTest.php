<?php
declare(strict_types=1);

namespace RumboAlCielo\Tests\Application\Plato;

use PHPUnit\Framework\TestCase;
use RumboAlCielo\Application\Plato\ActivarPlato;
use RumboAlCielo\Application\Plato\DesactivarPlato;
use RumboAlCielo\Application\Plato\ListarPlatos;
use RumboAlCielo\Application\Plato\ModificarPlato;
use RumboAlCielo\Application\Plato\RegistrarPlato;
use RumboAlCielo\Domain\Shared\ReglaDeNegocioException;
use RumboAlCielo\Tests\Doubles\InMemoryPlatoRepository;

/**
 * Pruebas de los casos de uso del módulo Platos.
 * Cada prueba indica el criterio de aceptación que verifica
 * (los códigos HU/CA se ajustan cuando el backlog quede completo).
 */
final class PlatoCasosDeUsoTest extends TestCase
{
    private const HOY = '2026-11-12';

    private InMemoryPlatoRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new InMemoryPlatoRepository();
    }

    /** Criterio: registro de plato con nombre y tipo válidos. */
    public function test_registra_un_plato_valido_y_queda_activo(): void
    {
        $plato = (new RegistrarPlato($this->repo))->execute('  Sopa de maní ', 'sopa', 'Con papa frita');

        $this->assertSame(1, $plato->id());
        $this->assertSame('Sopa de maní', $plato->nombre());
        $this->assertSame('SOPA', $plato->tipo());
        $this->assertTrue($plato->estaActivo());
    }

    /** [Caso de error] Criterio: nombre obligatorio. */
    public function test_rechaza_un_plato_sin_nombre(): void
    {
        $this->expectException(ReglaDeNegocioException::class);
        $this->expectExceptionMessage('El nombre del plato es obligatorio.');

        (new RegistrarPlato($this->repo))->execute('   ', 'SOPA');
    }

    /** [Caso de error] Criterio: tipo válido (sopa, principal o postre). */
    public function test_rechaza_un_tipo_de_plato_invalido(): void
    {
        $this->expectException(ReglaDeNegocioException::class);
        $this->expectExceptionMessage('El tipo de plato debe ser SOPA, PRINCIPAL o POSTRE.');

        (new RegistrarPlato($this->repo))->execute('Pique macho', 'ENTRADA');
    }

    /** [Caso de error] Criterio: no se repite el mismo nombre dentro de un tipo. */
    public function test_rechaza_un_plato_duplicado_del_mismo_tipo(): void
    {
        $registrar = new RegistrarPlato($this->repo);
        $registrar->execute('Silpancho', 'PRINCIPAL');

        $this->expectException(ReglaDeNegocioException::class);
        $this->expectExceptionMessage('Ya existe un plato con ese nombre y tipo.');

        $registrar->execute('silpancho', 'PRINCIPAL');
    }

    /** Criterio: el mismo nombre puede existir en otro tipo. */
    public function test_permite_el_mismo_nombre_en_otro_tipo(): void
    {
        $registrar = new RegistrarPlato($this->repo);
        $registrar->execute('Api', 'POSTRE');
        $otro = $registrar->execute('Api', 'SOPA');

        $this->assertSame(2, $otro->id());
    }

    /** Criterio: modificación de un plato existente. */
    public function test_modifica_el_nombre_de_un_plato(): void
    {
        $plato = (new RegistrarPlato($this->repo))->execute('Sopa de mani', 'SOPA');

        $modificado = (new ModificarPlato($this->repo))
            ->execute($plato->id(), 'Sopa de maní', 'SOPA', null, self::HOY);

        $this->assertSame('Sopa de maní', $this->repo->buscarPorId($plato->id())->nombre());
        $this->assertSame($plato->id(), $modificado->id());
    }

    /** [Caso de error] Criterio: no se cambia el tipo de un plato que está en un menú vigente. */
    public function test_no_cambia_el_tipo_de_un_plato_en_un_menu_futuro(): void
    {
        $plato = (new RegistrarPlato($this->repo))->execute('Flan', 'POSTRE');
        $this->repo->simularUsoEnMenu($plato->id(), '2026-11-16');

        $this->expectException(ReglaDeNegocioException::class);
        $this->expectExceptionMessage('No se puede cambiar el tipo');

        (new ModificarPlato($this->repo))->execute($plato->id(), 'Flan', 'PRINCIPAL', null, self::HOY);
    }

    /** [Caso de error] Criterio: el plato a modificar debe existir. */
    public function test_rechaza_modificar_un_plato_inexistente(): void
    {
        $this->expectException(ReglaDeNegocioException::class);
        $this->expectExceptionMessage('El plato no existe.');

        (new ModificarPlato($this->repo))->execute(99, 'Nada', 'SOPA', null, self::HOY);
    }

    /** Criterio: desactivación de un plato que no está en menús vigentes. */
    public function test_desactiva_un_plato_que_no_esta_en_ningun_menu(): void
    {
        $plato = (new RegistrarPlato($this->repo))->execute('Chairo', 'SOPA');

        (new DesactivarPlato($this->repo))->execute($plato->id(), self::HOY);

        $this->assertFalse($this->repo->buscarPorId($plato->id())->estaActivo());
    }

    /** [Caso de error] Criterio: no se desactiva un plato de un menú de hoy o futuro. */
    public function test_no_desactiva_un_plato_que_esta_en_un_menu_futuro(): void
    {
        $plato = (new RegistrarPlato($this->repo))->execute('Fricase', 'PRINCIPAL');
        $this->repo->simularUsoEnMenu($plato->id(), self::HOY);

        $this->expectException(ReglaDeNegocioException::class);
        $this->expectExceptionMessage('No se puede desactivar');

        (new DesactivarPlato($this->repo))->execute($plato->id(), self::HOY);
    }

    /** Criterio: un plato desactivado se puede volver a activar. */
    public function test_reactiva_un_plato_desactivado(): void
    {
        $plato = (new RegistrarPlato($this->repo))->execute('Chairo', 'SOPA');
        (new DesactivarPlato($this->repo))->execute($plato->id(), self::HOY);

        (new ActivarPlato($this->repo))->execute($plato->id());

        $this->assertTrue($this->repo->buscarPorId($plato->id())->estaActivo());
    }

    /** Criterio: el catálogo se puede filtrar por tipo y por estado activo. */
    public function test_lista_platos_filtrando_por_tipo_y_solo_activos(): void
    {
        $registrar = new RegistrarPlato($this->repo);
        $registrar->execute('Chairo', 'SOPA');
        $inactivo = $registrar->execute('Lawa', 'SOPA');
        $registrar->execute('Flan', 'POSTRE');
        (new DesactivarPlato($this->repo))->execute($inactivo->id(), self::HOY);

        $sopasActivas = (new ListarPlatos($this->repo))->execute('SOPA', true);

        $this->assertCount(1, $sopasActivas);
        $this->assertSame('Chairo', $sopasActivas[0]->nombre());
    }

    /** [Caso de error] Criterio: el filtro de tipo debe ser válido. */
    public function test_rechaza_listar_con_un_tipo_invalido(): void
    {
        $this->expectException(ReglaDeNegocioException::class);

        (new ListarPlatos($this->repo))->execute('BEBIDA');
    }
}
