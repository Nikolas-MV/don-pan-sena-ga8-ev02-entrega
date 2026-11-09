<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
requireLogin();

$conn = getConnection();
$productos = $conn->query(
    'SELECT id_producto, nombre, precio, stock_actual
     FROM productos WHERE activo = 1 ORDER BY nombre'
);
$clientes = $conn->query(
    'SELECT id_cliente, nombre FROM clientes WHERE activo = 1 ORDER BY nombre'
);

$pageTitle = 'Ventas';
require __DIR__ . '/../includes/header.php';
?>

<h1>Registrar venta</h1>

<?php if (isset($_GET['ok'])): ?>
    <p class="alert-ok">✅ Venta registrada correctamente.</p>
<?php elseif (isset($_GET['error'])): ?>
    <p class="alert-error">⚠️ No se pudo registrar la venta. Revisa el stock e intenta de nuevo.</p>
<?php endif; ?>

<form action="../actions/venta_action.php" method="POST" id="form-venta">
    <?= csrfField() ?>
    <table class="tabla-venta">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Cantidad</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($p = $productos->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($p['nombre']) ?></td>
                <td>$<?= number_format($p['precio'], 2) ?></td>
                <td><?= $p['stock_actual'] ?></td>
                <td>
                    <input type="number" min="0" max="<?= $p['stock_actual'] ?>"
                           name="cantidad[<?= $p['id_producto'] ?>]" value="0"
                           data-precio="<?= $p['precio'] ?>"
                           data-stock="<?= (int)$p['stock_actual'] ?>">
                </td>
                <td class="subtotal"></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <div class="form-nuevo" style="margin-top: 1rem;">
        <label for="id_cliente">Cliente</label>
        <select name="id_cliente" id="id_cliente">
            <option value="">Cliente rápido (sin registro)</option>
            <?php while ($c = $clientes->fetch_assoc()): ?>
                <option value="<?= (int)$c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endwhile; ?>
        </select>

        <label for="metodo_pago">Método de pago</label>
        <select name="metodo_pago" id="metodo_pago">
            <option value="efectivo">Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
            <option value="transferencia">Transferencia</option>
        </select>
    </div>

    <p id="aviso-venta" style="display: none;"></p>

    <div class="total-venta">
        <span>Total a cobrar:</span>
        <strong id="total-venta">$0.00</strong>
    </div>

    <button type="submit" id="btn-cobrar">Cobrar</button>
</form>

<script src="js/venta-total.js"></script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
