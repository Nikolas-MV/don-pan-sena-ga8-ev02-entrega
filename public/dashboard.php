<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$conn = getConnection();

// Resumen de ventas del día (tarjetas de resumen del diseño, sección 3.2)
$ventasHoy = $conn->query(
    "SELECT COUNT(*) AS total_ventas, COALESCE(SUM(total),0) AS total_dinero
     FROM ventas WHERE anulada = 0 AND DATE(fecha_hora) = CURDATE()"
)->fetch_assoc();

// Productos que necesitan reposición
$stockBajo = $conn->query(
    'SELECT COUNT(*) AS total FROM productos WHERE activo = 1 AND stock_actual <= stock_minimo'
)->fetch_assoc();

$totalProductos = $conn->query('SELECT COUNT(*) AS total FROM productos WHERE activo = 1')->fetch_assoc();
$totalClientes = $conn->query('SELECT COUNT(*) AS total FROM clientes')->fetch_assoc();

// Ventas de los últimos 7 días para el resumen de actividad
$ultimasVentas = $conn->query(
    'SELECT v.fecha_hora, v.total, v.metodo_pago, e.nombre AS empleado
     FROM ventas v
     JOIN empleados e ON e.id_empleado = v.id_empleado
     ORDER BY v.fecha_hora DESC
     LIMIT 5'
);

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<h1>Resumen del negocio</h1>

<section class="tarjetas">
    <div class="tarjeta">
        <h2><?= (int)$ventasHoy['total_ventas'] ?></h2>
        <p>Ventas hoy</p>
    </div>
    <div class="tarjeta">
        <h2>$<?= number_format($ventasHoy['total_dinero'], 2) ?></h2>
        <p>Ingresos hoy</p>
    </div>
    <div class="tarjeta">
        <h2><?= (int)$totalProductos['total'] ?></h2>
        <p>Productos registrados</p>
    </div>
    <div class="tarjeta">
        <h2><?= (int)$totalClientes['total'] ?></h2>
        <p>Clientes</p>
    </div>
</section>

<?php if ((int)$stockBajo['total'] > 0): ?>
    <p class="alert-warn">
        ⚠️ Hay <?= (int)$stockBajo['total'] ?> producto(s) con stock bajo.
        <a href="inventario.php">Ir a inventario</a>
    </p>
<?php endif; ?>

<h2>Accesos rápidos</h2>
<div class="accesos-rapidos">
    <a href="ventas.php" class="btn-secundario">🛒 Registrar venta</a>
    <a href="productos.php" class="btn-secundario">🍞 Gestionar productos</a>
    <a href="inventario.php" class="btn-secundario">📦 Controlar inventario</a>
    <a href="clientes.php" class="btn-secundario">👥 Gestionar clientes</a>
    <?php if (isOwner()): ?>
        <a href="reportes.php" class="btn-secundario">📊 Ver reportes</a>
    <?php endif; ?>
</div>

<h2>Últimas ventas</h2>
<p><a href="historial_ventas.php">Ver historial completo →</a></p>
<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Empleado</th>
            <th>Método de pago</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($ultimasVentas->num_rows === 0): ?>
        <tr><td colspan="4">Aún no hay ventas registradas.</td></tr>
    <?php endif; ?>
    <?php while ($v = $ultimasVentas->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($v['fecha_hora']) ?></td>
            <td><?= htmlspecialchars($v['empleado']) ?></td>
            <td><?= htmlspecialchars(ucfirst($v['metodo_pago'])) ?></td>
            <td>$<?= number_format($v['total'], 2) ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../includes/footer.php'; ?>
