<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
requireLogin();

$conn = getConnection();
$productos = $conn->query('SELECT * FROM productos ORDER BY nombre');

$pageTitle = 'Inventario';
require __DIR__ . '/../includes/header.php';
?>

<h1>Inventario</h1>

<?php if (isset($_GET['ok'])): ?>
    <p class="alert-ok">✅ Producto registrado correctamente.</p>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'campos'): ?>
    <p class="alert-error">⚠️ Completa todos los campos (nombre, precio y stock válidos).</p>
<?php elseif (isset($_GET['error'])): ?>
    <p class="alert-error">⚠️ Ocurrió un error al procesar la solicitud. Intenta de nuevo.</p>
<?php endif; ?>

<section class="nuevo-producto">
    <h2>Registrar nuevo producto</h2>
    <form action="../actions/producto_action.php" method="POST" class="form-nuevo">
        <?= csrfField() ?>
        <input type="text" name="nombre" placeholder="Nombre del producto" required>
        <input type="number" name="precio" placeholder="Precio" min="0.01" step="0.01" required>
        <input type="number" name="stock" placeholder="Stock inicial" min="0" required>
        <input type="number" name="stock_minimo" placeholder="Stock mínimo" min="0" required>
        <button type="submit">Registrar</button>
    </form>
</section>

<table class="tabla-inventario">
    <thead>
        <tr>
            <th>Producto</th>
            <th>Stock actual</th>
            <th>Stock mínimo</th>
            <th>Estado</th>
            <th>Registrar entrada</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($p = $productos->fetch_assoc()): ?>
        <tr class="<?= $p['stock_actual'] <= $p['stock_minimo'] ? 'fila-alerta' : '' ?>">
            <td><?= htmlspecialchars($p['nombre']) ?></td>
            <td><?= $p['stock_actual'] ?></td>
            <td><?= $p['stock_minimo'] ?></td>
            <td>
                <?= $p['stock_actual'] <= $p['stock_minimo'] ? '⚠️ Stock bajo' : '✅ OK' ?>
            </td>
            <td>
                    <form action="../actions/inventario_action.php" method="POST" class="form-inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                    <input type="number" name="cantidad" min="1" required>
                    <button type="submit">Ingresar</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../includes/footer.php'; ?>
