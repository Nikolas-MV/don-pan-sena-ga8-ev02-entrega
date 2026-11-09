<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Comprueba rechazo explícito de tokens vacíos y tipos inesperados. */
final class CsrfTest extends TestCase
{
    public static function tokens(): array
    {
        $uno = str_repeat('a', 64);
        return [
            'coinciden' => [$uno, $uno, true],
            'diferentes' => [$uno, str_repeat('b', 64), false],
            'ambos vacios' => ['', '', false],
            'esperado vacio' => ['', $uno, false],
            'recibido vacio' => [$uno, '', false],
            'ausente' => [null, null, false],
            'falta recibido' => [$uno, null, false],
            'array recibido' => [$uno, [], false],
            'array esperado' => [[], $uno, false],
            'numero' => [$uno, 123, false],
            'booleano' => [true, true, false],
        ];
    }

    #[DataProvider('tokens')]
    public function testComparacion(mixed $esperado, mixed $recibido, bool $valido): void
    {
        self::assertSame($valido, donPanCsrfValido($esperado, $recibido));
    }
}
