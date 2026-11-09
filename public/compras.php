<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
requireLogin();

// Solo el dueno gestiona compras a proveedores (modulo de costo).
requireOwner();

$conn = getConnection();

$proveedores = $conn->query('SELECT id_proveedor, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre');
$insumos = $conn->query('SELECT id_insumo, nombre, unidad, stock_actual FROM insumos ORDER BY nombre');

$compras = $conn->query(
    'SELECT c.id_compra, c.fecha_hora, c.total, c.anulada, p.nombre AS proveedor,
            e.nombre AS empleado,
            (SELECT COUNT(*) FROM detalle_compras dc WHERE dc.id_compra = c.id_compra) AS items
     FROM compras c
     JOIN proveedores p ON p.id_proveedor = c.id_proveedor
     LEFT JOIN empleados e ON e.id_empleado = c.id_empleado
     ORDER BY c.fecha_hora DESC
     LIMIT 50'
);

$pageTitle = 'Compras';
require __DIR__ . '/../includes/header.php';
?>

<h1>Compras a proveedores</h1>

<?php if (($_GET['ok'] ?? '') === 'anulada'): ?>
    <p class="alert-warn">Compra anulada. Los insumos se devolvieron al stock.</p>
<?php elseif (isset($_GET['ok'])): ?>
    <p class="alert-ok">✅ Compra registrada y stock de insumos actualizado.</p>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'ya_anulada'): ?>
    <p class="alert-error">⚠️ Esa compra ya estaba anulada.</p>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'invalida'): ?>
    <p class="alert-error">⚠️ Compra no valida.</p>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'vacia'): ?>
    <p class="alert-error">⚠️ Selecciona un proveedor y al menos un insumo con cantidad.</p>
<?php elseif (isset($_GET['error'])): ?>
    <p class="alert-error">⚠️ Ocurrio un error al procesar la solicitud. Intenta de nuevo.</p>
<?php endif; ?>

<section class="nuevo-producto">
    <h2>Registrar compra</h2>
    <form action="../actions/compra_action.php" method="POST">
        <?= csrfField() ?>

        <label for="id_proveedor">Proveedor</label>
        <select name="id_proveedor" id="id_proveedor" required>
            <option value="">Selecciona un proveedor</option>
            <?php while ($pr = $proveedores->fetch_assoc()): ?>
                <option value="<?= (int)$pr['id_proveedor'] ?>">
                    <?= htmlspecialchars($pr['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <table class="tabla-compras">
            <thead>
                <tr>
                    <th>Insumo</th>
                    <th>Stock actual</th>
                    <th>Cantidad</th>
                    <th>Costo unitario</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($i = $insumos->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($i['nombre']) ?></td>
                    <td><?= (int)$i['stock_actual'] ?> <?= htmlspecialchars($i['unidad']) ?></td>
                    <td>
                        <input type="number" min="0" step="1"
                               name="cantidad[<?= (int)$i['id_insumo'] ?>]" value="0">
                    </td>
                    <td>
                        <input type="number" min="0" step="0.01"
                               name="costo[<?= (int)$i['id_insumo'] ?>]" value="0">
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

        <button type="submit">Registrar compra</button>
    </form>
</section>

<h2>Últimas compras</h2>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Proveedor</th>
            <th>Registró</th>
            <th>Items</th>
            <th>Total</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($compras->num_rows === 0): ?>
        <tr><td colspan="7">Aun no hay compras registradas.</td></tr>
    <?php endif; ?>
    <?php while ($c = $compras->fetch_assoc()): ?>
        <tr class="<?= (int)$c['anulada'] === 1 ? 'fila-anulada' : '' ?>">
            <td><?= (int)$c['id_compra'] ?></td>
            <td><?= htmlspecialchars($c['fecha_hora']) ?></td>
            <td><?= htmlspecialchars($c['proveedor']) ?></td>
            <td><?= htmlspecialchars($c['empleado'] ?? '—') ?></td>
            <td><?= (int)$c['items'] ?></td>
            <td>
                <?php if ((int)$c['anulada'] === 1): ?>
                    <s>$<?= number_format($c['total'], 2) ?></s> (anulada)
                <?php else: ?>
                    $<?= number_format($c['total'], 2) ?>
                <?php endif; ?>
            </td>
            <td>
                <?php if ((int)$c['anulada'] === 1): ?>
                    <span class="txt-suave">—</span>
                <?php else: ?>
                    <form action="../actions/anular_compra_action.php" method="POST"
                          onsubmit="return confirm('Anular la compra #<?= (int)$c['id_compra'] ?>? Se descontara el stock de los insumos.');">
                        <?= csrfField() ?>
                        <input type="hidden" name="id_compra" value="<?= (int)$c['id_compra'] ?>">
                        <button type="submit" class="btn-peligro">Anular</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../includes/footer.php'; ?>