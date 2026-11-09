<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

requireOwner();

$conn = getConnection();

// Selector de periodo (documento de diseno, seccion 3.7).
// Por defecto muestra el dia de hoy.
$desde = $_GET['desde'] ?? date('Y-m-d');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$formato = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($formato, $desde)) {
    $desde = date('Y-m-d');
}
if (!preg_match($formato, $hasta)) {
    $hasta = date('Y-m-d');
}
if ($desde > $hasta) {
    [$desde, $hasta] = [$hasta, $desde];
}

$stmtVentas = $conn->prepare(
    'SELECT COUNT(*) AS total_ventas, COALESCE(SUM(total),0) AS total_dinero
     FROM ventas
     WHERE anulada = 0 AND DATE(fecha_hora) BETWEEN ? AND ?'
);
$stmtVentas->bind_param('ss', $desde, $hasta);
$stmtVentas->execute();
$ventasHoy = $stmtVentas->get_result()->fetch_assoc();

$stmtTop = $conn->prepare(
    'SELECT p.nombre, SUM(dv.cantidad) AS unidades_vendidas
     FROM detalle_ventas dv
     JOIN productos p ON p.id_producto = dv.id_producto
     JOIN ventas v ON v.id_venta = dv.id_venta
     WHERE v.anulada = 0 AND DATE(v.fecha_hora) BETWEEN ? AND ?
     GROUP BY p.id_producto
     ORDER BY unidades_vendidas DESC
     LIMIT 5'
);
$stmtTop->bind_param('ss', $desde, $hasta);
$stmtTop->execute();
$topProductos = $stmtTop->get_result();

$stockBajo = $conn->query(
    'SELECT nombre, stock_actual, stock_minimo FROM productos WHERE stock_actual <= stock_minimo'
);

$pageTitle = 'Reportes';
require __DIR__ . '/../includes/header.php';
?>

<h1>Reportes</h1>

<form method="GET" action="reportes.php" class="buscador">
    <label for="desde">Desde</label>
    <input type="date" name="desde" id="desde" value="<?= htmlspecialchars($desde) ?>">
    <label for="hasta">Hasta</label>
    <input type="date" name="hasta" id="hasta" value="<?= htmlspecialchars($hasta) ?>">
    <button type="submit">Filtrar</button>
    <a href="reportes.php" class="btn-secundario">Hoy</a>
</form>

<section class="tarjetas">
    <div class="tarjeta">
        <h2><?= (int)$ventasHoy['total_ventas'] ?></h2>
        <p>Ventas del periodo</p>
    </div>
    <div class="tarjeta">
        <h2>$<?= number_format($ventasHoy['total_dinero'], 2) ?></h2>
        <p>Ingresos del periodo</p>
    </div>
</section>

<h2>Productos mas vendidos (<?= htmlspecialchars($desde) ?> a <?= htmlspecialchars($hasta) ?>)</h2>
<ul>
    <?php while ($p = $topProductos->fetch_assoc()): ?>
        <li><?= htmlspecialchars($p['nombre']) ?> — <?= $p['unidades_vendidas'] ?> unidades</li>
    <?php endwhile; ?>
</ul>

<h2>Alertas de stock bajo</h2>
<ul>
    <?php while ($p = $stockBajo->fetch_assoc()): ?>
        <li><?= htmlspecialchars($p['nombre']) ?>: <?= $p['stock_actual'] ?> / minimo <?= $p['stock_minimo'] ?></li>
    <?php endwhile; ?>
</ul>

<?php require __DIR__ . '/../includes/footer.php'; ?>