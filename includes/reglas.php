<?php
/**
 * Reglas puras compartidas por la aplicación y sus pruebas unitarias.
 * No abren sesiones, conexiones ni archivos.
 */
declare(strict_types=1);

/** Determina el permiso de dueño sin depender de variables globales. */
function donPanEsDueno(array $sesion): bool
{
    return isset($sesion['id_empleado']) && ($sesion['rol'] ?? null) === 'dueño';
}

/** Compara únicamente tokens presentes y de tipo cadena. */
function donPanCsrfValido(mixed $esperado, mixed $recibido): bool
{
    return is_string($esperado) && is_string($recibido)
        && $esperado !== '' && $recibido !== ''
        && hash_equals($esperado, $recibido);
}

/**
 * Acepta enteros positivos y su representación habitual en formularios.
 * Rechaza conversiones implícitas, fracciones, booleanos y desbordamientos.
 */
function donPanCantidadVenta(mixed $valor): int
{
    if ((!is_int($valor) && !is_string($valor))
        || !preg_match('/^[1-9][0-9]*$/D', (string) $valor)) {
        throw new InvalidArgumentException('La cantidad debe ser un entero positivo.');
    }
    $cantidad = filter_var($valor, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]);
    if ($cantidad === false) {
        throw new InvalidArgumentException('La cantidad está fuera del rango permitido.');
    }
    return $cantidad;
}

/**
 * Omite solo las casillas sin selección. Una cantidad inválida se rechaza.
 * @return array<int, int> cantidades por identificador válido
 */
function donPanCantidadesVenta(mixed $entrada): array
{
    if (!is_array($entrada)) {
        throw new InvalidArgumentException('El formulario de cantidades no es válido.');
    }
    $salida = [];
    foreach ($entrada as $id => $cantidad) {
        if ($cantidad === '' || $cantidad === '0' || $cantidad === 0) {
            continue;
        }
        $id = donPanCantidadVenta($id);
        $salida[$id] = donPanCantidadVenta($cantidad);
    }
    return $salida;
}

/**
 * Comprueba la fila leída bajo bloqueo, antes de insertar detalle o descontar.
 * @return int cantidad validada que utiliza la transacción real
 */
function donPanValidarLineaVenta(mixed $cantidad, ?array $producto): int
{
    $cantidad = donPanCantidadVenta($cantidad);
    if ($producto === null
        || !in_array($producto['activo'] ?? null, [1, '1'], true)) {
        throw new InvalidArgumentException('El producto no está disponible para venta.');
    }
    if ((int) ($producto['stock_actual'] ?? 0) < $cantidad) {
        throw new InvalidArgumentException('Stock insuficiente.');
    }
    return $cantidad;
}
