<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Disponibilidad y cantidades de la transacción real de ventas. */
final class ReglasVentaTest extends TestCase
{
    public static function cantidadesInvalidas(): array
    {
        return [
            'cero' => [0], 'negativa' => [-1], 'fraccion' => [1.5],
            'texto fraccion' => ['1.5'], 'no numerica' => ['pan'],
            'array' => [[]], 'booleano' => [true], 'null' => [null],
            'exceso' => ['2147483648'], 'vacia' => [''],
        ];
    }

    #[DataProvider('cantidadesInvalidas')]
    public function testRechazaCantidadInvalida(mixed $cantidad): void
    {
        $this->expectException(InvalidArgumentException::class);
        donPanCantidadVenta($cantidad);
    }

    public function testAceptaEnteroDeFormulario(): void
    {
        self::assertSame(2, donPanValidarLineaVenta('2', ['activo' => '1', 'stock_actual' => 5]));
    }

    public function testPermiteAgotarExactamenteElStock(): void
    {
        self::assertSame(5, donPanValidarLineaVenta(5, ['activo' => 1, 'stock_actual' => 5]));
    }

    public function testRechazaInexistente(): void
    {
        $this->expectException(InvalidArgumentException::class);
        donPanValidarLineaVenta(1, null);
    }

    public function testRechazaInactivo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        donPanValidarLineaVenta(1, ['activo' => 0, 'stock_actual' => 10]);
    }

    public function testRechazaStockInsuficiente(): void
    {
        $this->expectException(InvalidArgumentException::class);
        donPanValidarLineaVenta(6, ['activo' => 1, 'stock_actual' => 5]);
    }

    public function testOmiteSoloCasillasSinSeleccion(): void
    {
        self::assertSame([7 => 2], donPanCantidadesVenta([5 => '0', 6 => '', 7 => '2']));
    }

    public function testNoOcultaCantidadesNegativas(): void
    {
        $this->expectException(InvalidArgumentException::class);
        donPanCantidadesVenta([7 => '-2']);
    }

    public function testRechazaContenedorInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        donPanCantidadesVenta('2');
    }

    public function testRechazaIdentificadorInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        donPanCantidadesVenta(['otro' => '2']);
    }

    public function testLimiteEnteroValido(): void
    {
        self::assertSame(2147483647, donPanCantidadVenta('2147483647'));
    }
}
