<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Verifica la misma política que invoca includes/auth.php. */
final class AutorizacionTest extends TestCase
{
    public static function sesiones(): array
    {
        return [
            'ausente' => [[], false],
            'empleada' => [['id_empleado' => 1, 'rol' => 'empleada'], false],
            'desconocido' => [['id_empleado' => 1, 'rol' => 'otro'], false],
            'rol ausente' => [['id_empleado' => 1], false],
            'dueno sin empleado' => [['rol' => 'dueño'], false],
            'dueno' => [['id_empleado' => 1, 'rol' => 'dueño'], true],
        ];
    }

    #[DataProvider('sesiones')]
    public function testPoliticaDeRol(array $sesion, bool $esperado): void
    {
        self::assertSame($esperado, donPanEsDueno($sesion));
    }
}
