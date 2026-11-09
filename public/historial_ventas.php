<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
requireLogin();

$conn = getConnection();

// Solo el dueño puede anular (es una corrección contable).
$puedeAnular = isOwner();

$stmt = $conn->prepare(
    'SELECT v.id_venta, v.fecha_hora, v.total, v.metodo_pago, v.anulada,
            v.fecha_anulacion, e.nombre AS empleado, c.nombre AS cliente,
            (SELECT COUNT(*) FROM detalle_ventas dv WHERE dv.id_venta = v.id_venta) AS items
     FROM ventas v
     JOIN empleados e ON e.id_empleado = v.id_empleado
     LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
     ORDER BY v.fecha_hora DESC
     LIMIT 100'
);
$stmt->execute();
$ventas = $stmt->get_result();

$pageTitle = 'Historial de ventas';
require __DIR__ . '/../includes/header.php';
?>

<h1>Historial de ventas</h1>

<?php if (isset($_GET['ok'])): ?>
    <p class="alert-ok">✅ Venta anulada. El stock se devolvio al inventario.</p>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'ya_anulada'): ?>
    <p class="alert-error">⚠️ Esa venta ya estaba anulada.</p>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'invalida'): ?>
    <p class="alert-error">⚠️ Venta no valida.</p>
<?php elseif (isset($_GET['error'])): ?>
    <p class="alert-error">⚠️ Ocurrio un error al procesar la solicitud. Intenta de nuevo.</p>
<?php endif; ?>

<p>Se muestran las 100 ventas mas recientes.</p>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Empleado</th>
            <th>Cliente</th>
            <th>Items</th>
            <th>Pago</th>
            <th>Total</th>
            <?php if ($puedeAnular): ?>
                <th>Accion</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php if ($ventas->num_rows === 0): ?>
        <tr>
            <td colspan="<?= $puedeAnular ? 8 : 7 ?>">Aun no hay ventas registradas.</td>
        </tr>
    <?php endif; ?>
    <?php while ($v = $ventas->fetch_assoc()): ?>
        <tr class="<?= (int)$v['anulada'] === 1 ? 'fila-anulada' : '' ?>">
            <td><?= (int)$v['id_venta'] ?></td>
            <td><?= htmlspecialchars($v['fecha_hora']) ?></td>
            <td><?= htmlspecialchars($v['empleado']) ?></td>
            <td><?= htmlspecialchars($v['cliente'] ?? 'Cliente rapido') ?></td>
            <td><?= (int)$v['items'] ?></td>
            <td><?= htmlspecialchars(ucfirst($v['metodo_pago'])) ?></td>
            <td>
                <?php if ((int)$v['anulada'] === 1): ?>
                    <s>$<?= number_format($v['total'], 2) ?></s> (anulada)
                <?php else: ?>
                    $<?= number_format($v['total'], 2) ?>
                <?php endif; ?>
            </td>
            <?php if ($puedeAnular): ?>
                <td>
                    <?php if ((int)$v['anulada'] === 1): ?>
                        <span class="txt-suave">—</span>
                    <?php else: ?>
                        <form action="../actions/anular_venta_action.php" method="POST"
                              onsubmit="return confirm('Anular la venta #<?= (int)$v['id_venta'] ?>? Se devolvera el stock al inventario.');">
                            <?= csrfField() ?>
                            <input type="hidden" name="id_venta" value="<?= (int)$v['id_venta'] ?>">
                            <button type="submit" class="btn-peligro">Anular</button>
                        </form>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../includes/footer.php'; ?>