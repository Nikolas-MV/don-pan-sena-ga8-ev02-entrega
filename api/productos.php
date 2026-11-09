<?php
/**
 * GET /api/productos.php - catálogo en lectura para el front-end.
 * Exige sesión. No expone campos sensibles ni permite escritura
 * (las altas siguen siendo exclusivas de la aplicación PHP con CSRF de formulario).
 */
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

exigir_metodo('GET');
exigir_sesion();

try {
    $conn = getConnection();
    $resultado = $conn->query(
        'SELECT id_producto, nombre, precio, stock_actual, stock_minimo, codigo_barra
         FROM productos
         WHERE activo = 1
         ORDER BY nombre ASC'
    );
    $filas = $resultado->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $e) {
    error_log('[api/productos] ' . $e->getMessage());
    json_error(500, 'servidor', 'No fue posible leer el catálogo.');
}

// Cast explícito: evita que decimals lleguen como strings y aligera la respuesta.
$productos = array_map(static function (array $fila): array {
    return [
        'id_producto' => (int) $fila['id_producto'],
        'nombre' => $fila['nombre'],
        'precio' => (float) $fila['precio'],
        'stock_actual' => (int) $fila['stock_actual'],
        'stock_minimo' => (int) $fila['stock_minimo'],
        'codigo_barra' => $fila['codigo_barra'],
    ];
}, $filas);

json_salida(['ok' => true, 'productos' => $productos]);
