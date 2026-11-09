<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
requireLogin();

requireOwner();

$conn = getConnection();

$productos = $conn->query(
    'SELECT id_producto, nombre FROM productos WHERE activo = 1 ORDER BY nombre'
);
$insumos = $conn->query('SELECT id_insumo, nombre, unidad FROM insumos ORDER BY nombre');

$recetas = $conn->query(
    'SELECT r.id_receta, r.cantidad, p.nombre AS producto, i.nombre AS insumo, i.unidad
     FROM recetas r
     JOIN productos p ON p.id_producto = r.id_producto
     JOIN insumos i ON i.id_insumo = r.id_insumo
     ORDER BY p.nombre, i.nombre'
);

$pageTitle = 'Recetas';
require __DIR__ . '/../includes/header.php';
?>

<h1>Recetas de producción</h1>

<p>Define qué insumos y en qué cantidad necesita cada producto para elaborarse.</p>

<?php if (($_GET['ok'] ?? '') === 'creada'): ?>
    <p class="alert-ok">✅ Receta agregada.</p>
<?php elseif (($_GET['ok'] ?? '') === 'eliminada'): ?>
    <p class="alert-warn">Receta eliminada.</p>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'campos'): ?>
    <p class="alert-error">⚠️ Completa producto, insumo y una cantidad mayor que cero.</p>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'duplicado'): ?>
    <p class="alert-error">⚠️ Ese insumo ya está en la receta de este producto.</p>
<?php elseif (isset($_GET['error'])): ?>
    <p class="alert-error">⚠️ Ocurrió un error al procesar la solicitud.</p>
<?php endif; ?>

<section class="nuevo-producto">
    <h2>Agregar insumo a una receta</h2>
    <form action="../actions/receta_action.php" method="POST" class="form-nuevo">
        <?= csrfField() ?>
        <input type="hidden" name="accion" value="crear">

        <select name="id_producto" required>
            <option value="">Producto</option>
            <?php while ($p = $productos->fetch_assoc()): ?>
                <option value="<?= (int)$p['id_producto'] ?>">
                    <?= htmlspecialchars($p['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="id_insumo" required>
            <option value="">Insumo</option>
            <?php while ($i = $insumos->fetch_assoc()): ?>
                <option value="<?= (int)$i['id_insumo'] ?>">
                    <?= htmlspecialchars($i['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <input type="number" name="cantidad" placeholder="Cantidad" step="0.01" min="0.01" required>

        <button type="submit">Agregar</button>
    </form>
</section>

<h2>Recetas registradas</h2>
<table>
    <thead>
        <tr>
            <th>Producto</th>
            <th>Insumo</th>
            <th>Cantidad necesaria</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($recetas->num_rows === 0): ?>
        <tr>
            <td colspan="4">
                Todavía no hay recetas. Agrega arriba el primer insumo de un producto.
            </td>
        </tr>
    <?php endif; ?>
    <?php while ($r = $recetas->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($r['producto']) ?></td>
            <td><?= htmlspecialchars($r['insumo']) ?></td>
            <td><?= rtrim(rtrim(number_format($r['cantidad'], 2), '0'), '.') ?>
                <?= htmlspecialchars($r['unidad']) ?></td>
            <td>
                <form action="../actions/receta_action.php" method="POST"
                      onsubmit="return confirm('Eliminar esta receta?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_receta" value="<?= (int)$r['id_receta'] ?>">
                    <button type="submit" class="btn-peligro">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<p><a href="insumos.php">← Volver a insumos</a></p>

<?php require __DIR__ . '/../includes/footer.php'; ?>