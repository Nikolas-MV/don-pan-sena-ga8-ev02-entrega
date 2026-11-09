<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

requireOwner();

$conn = getConnection();

$insumos = $conn->query(
    'SELECT id_insumo, nombre, unidad, stock_actual, stock_minimo
     FROM insumos ORDER BY nombre'
);

// Historial de movimientos, con el nombre del insumo y quien lo registro.
$movimientos = $conn->query(
    'SELECT m.id_movimiento, m.fecha_hora, m.tipo, m.cantidad, m.motivo,
            i.nombre AS insumo, i.unidad, e.nombre AS empleado
     FROM movimientos_insumos m
     JOIN insumos i ON i.id_insumo = m.id_insumo
     LEFT JOIN empleados e ON e.id_empleado = m.id_empleado
     ORDER BY m.fecha_hora DESC
     LIMIT 100'
);

// Que insumos usa cada producto (relacion N:N resuelta con la tabla recetas).
$recetas = $conn->query(
    'SELECT r.id_receta, r.cantidad, p.nombre AS producto, p.id_producto,
            i.nombre AS insumo, i.unidad
     FROM recetas r
     JOIN productos p ON p.id_producto = r.id_producto
     JOIN insumos i ON i.id_insumo = r.id_insumo
     ORDER BY p.nombre, i.nombre'
);

$pageTitle = 'Insumos';
require __DIR__ . '/../includes/header.php';
?>

<h1>Insumos y producción</h1>

<section class="tarjetas">
    <?php
    $totalInsumos = 0;
    $bajoMinimo = 0;
    while ($i = $insumos->fetch_assoc()) {
        $totalInsumos++;
        if ((int)$i['stock_actual'] <= (int)$i['stock_minimo']) {
            $bajoMinimo++;
        }
    }
    $insumos->data_seek(0);
    ?>
    <div class="tarjeta">
        <h2><?= $totalInsumos ?></h2>
        <p>Insumos registrados</p>
    </div>
    <div class="tarjeta">
        <h2><?= $bajoMinimo ?></h2>
        <p>Con stock bajo</p>
    </div>
</section>

<?php if ($bajoMinimo > 0): ?>
    <p class="alert-warn">⚠️ Hay <?= $bajoMinimo ?> insumo(s) en o por debajo del mínimo.</p>
<?php endif; ?>

<h2>Stock de insumos</h2>
<table>
    <thead>
        <tr>
            <th>Insumo</th>
            <th>Unidad</th>
            <th>Stock actual</th>
            <th>Stock mínimo</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($i = $insumos->fetch_assoc()): ?>
        <tr class="<?= (int)$i['stock_actual'] <= (int)$i['stock_minimo'] ? 'fila-alerta' : '' ?>">
            <td><?= htmlspecialchars($i['nombre']) ?></td>
            <td><?= htmlspecialchars($i['unidad']) ?></td>
            <td><?= (int)$i['stock_actual'] ?></td>
            <td><?= (int)$i['stock_minimo'] ?></td>
            <td>
                <?= (int)$i['stock_actual'] <= (int)$i['stock_minimo'] ? '⚠️ Reponer' : '✅ OK' ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<p><a href="recetas.php">Ver y editar las recetas de producción →</a></p>

<h2>Historial de movimientos</h2>
<p class="txt-suave">Se muestran los 100 movimientos más recientes.</p>
<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Insumo</th>
            <th>Tipo</th>
            <th>Cantidad</th>
            <th>Motivo</th>
            <th>Registró</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($movimientos->num_rows === 0): ?>
        <tr><td colspan="6">Todavía no hay movimientos de insumos.</td></tr>
    <?php endif; ?>
    <?php while ($m = $movimientos->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($m['fecha_hora']) ?></td>
            <td><?= htmlspecialchars($m['insumo']) ?></td>
            <td>
                <?php if ($m['tipo'] === 'entrada'): ?>
                    <span class="tag-entrada">⬆ Entrada</span>
                <?php else: ?>
                    <span class="tag-salida">⬇ Salida</span>
                <?php endif; ?>
            </td>
            <td><?= (int)$m['cantidad'] ?> <?= htmlspecialchars($m['unidad']) ?></td>
            <td><?= htmlspecialchars($m['motivo'] ?? '') ?></td>
            <td><?= htmlspecialchars($m['empleado'] ?? '—') ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../includes/footer.php'; ?>