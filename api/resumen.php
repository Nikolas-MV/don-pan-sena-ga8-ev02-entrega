<?php
/**
 * GET /api/resumen.php - KPIs del panel principal.
 * Sólo lectura agregada; exige sesión (cualquier rol autenticado).
 */
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

exigir_metodo('GET');
exigir_sesion();

try {
    $conn = getConnection();

    $hoy = $conn->prepare(
        'SELECT COUNT(*) AS ventas, COALESCE(SUM(total), 0) AS total
         FROM ventas WHERE anulada = 0 AND DATE(fecha_hora) = CURDATE()'
    );
    $hoy->execute();
    $ventas = $hoy->get_result()->fetch_assoc();

    $prod = $conn->query(
        'SELECT COUNT(*) AS activos,
                SUM(CASE WHEN stock_actual <= stock_minimo THEN 1 ELSE 0 END) AS bajos
         FROM productos WHERE activo = 1'
    )->fetch_assoc();
} catch (Throwable $e) {
    error_log('[api/resumen] ' . $e->getMessage());
    json_error(500, 'servidor', 'No fue posible leer el resumen.');
}

json_salida([
    'ok' => true,
    'ventasHoy' => (int) ($ventas['ventas'] ?? 0),
    'totalHoy' => '$' . number_format((float) ($ventas['total'] ?? 0), 0, ',', '.'),
    'productos' => (int) ($prod['activos'] ?? 0),
    'stockBajo' => (int) ($prod['bajos'] ?? 0),
]);
