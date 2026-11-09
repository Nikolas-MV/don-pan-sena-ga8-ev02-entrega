<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
requireLogin();

$conn = getConnection();

// Campo de búsqueda (documento de diseño, sección 3.3)
$busqueda = trim($_GET['q'] ?? '');

// Filtro de estado: por defecto solo los productos activos.
$verInactivos = isset($_GET['inactivos']);

$sql = 'SELECT p.id_producto, p.nombre, p.precio, p.stock_actual, p.stock_minimo,
               p.codigo_barra, p.activo, c.nombre AS categoria
        FROM productos p
        LEFT JOIN categorias c ON c.id_categoria = p.id_categoria';

$where = [];
$params = '';

if (!$verInactivos) {
    $where[] = 'p.activo = 1';
}

if ($busqueda !== '') {
    $like = '%' . $busqueda . '%';
    $where[] = '(p.nombre LIKE ? OR p.codigo_barra LIKE ?)';
    $params .= 'ss';
}

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY p.nombre';

if ($params === '') {
    $productos = $conn->query($sql);
} else {
    $like = '%' . $busqueda . '%';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($params, $like, $like);
    $stmt->execute();
    $productos = $stmt->get_result();
}

$categorias = $conn->query('SELECT id_categoria, nombre FROM categorias ORDER BY nombre');

$pageTitle = 'Productos';
require __DIR__ . '/../includes/header.php';
?>

<h1>Productos</h1>

<?php if (isset($_GET['ok'])): ?>
    <p class="alert-ok">✅ Producto registrado correctamente.</p>
<?php elseif ($_GET['ok'] ?? '' === 'baja'): ?>
    <p class="alert-warn">Producto dado de baja. Ya no aparece en el punto de venta.</p>
<?php elseif ($_GET['ok'] ?? '' === 'reactivar'): ?>
    <p class="alert-ok">✅ Producto reactivado.</p>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'campos'): ?>
    <p class="alert-error">⚠️ Completa todos los campos (nombre, precio y stock válidos).</p>
<?php elseif (isset($_GET['error'])): ?>
    <p class="alert-error">⚠️ Ocurrió un error al procesar la solicitud. Intenta de nuevo.</p>
<?php endif; ?>

<section class="nuevo-producto">
    <h2>Nuevo producto</h2>
    <form action="../actions/producto_action.php" method="POST" class="form-nuevo">
        <?= csrfField() ?>
        <input type="text" name="nombre" placeholder="Nombre del producto" required>
        <input type="number" name="precio" placeholder="Precio" min="0.01" step="0.01" required>
        <input type="number" name="stock" placeholder="Stock inicial" min="0" required>
        <input type="number" name="stock_minimo" placeholder="Stock mínimo" min="0" required>
        <select name="id_categoria">
            <option value="">Sin categoría</option>
            <?php while ($c = $categorias->fetch_assoc()): ?>
                <option value="<?= (int)$c['id_categoria'] ?>">
                    <?= htmlspecialchars($c['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit">Registrar</button>
    </form>
</section>

<form method="GET" action="productos.php" class="buscador">
    <input type="text" name="q" placeholder="Buscar por nombre o código de barras"
           value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit">Buscar</button>
    <?php if ($busqueda !== ''): ?>
        <a href="productos.php" class="btn-secundario">Limpiar</a>
    <?php endif; ?>
</form>

<p class="txt-suave">
    <?php if ($verInactivos): ?>
        <a href="productos.php<?= $busqueda !== '' ? '?q=' . urlencode($busqueda) : '' ?>">
            Mostrar solo productos activos
        </a>
    <?php else: ?>
        <a href="productos.php?inactivos=1<?= $busqueda !== '' ? '&q=' . urlencode($busqueda) : '' ?>">
            Ver productos dados de baja
        </a>
    <?php endif; ?>
</p>

<table class="tabla-productos">
    <thead>
        <tr>
            <th>Producto</th>
            <th>Categoría</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Estado</th>
            <th>Editar</th>
            <th>Disponibilidad</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($productos->num_rows === 0): ?>
        <tr>
            <td colspan="7">No se encontraron productos<?= $busqueda !== '' ? ' para la búsqueda' : '' ?>.</td>
        </tr>
    <?php endif; ?>
    <?php while ($p = $productos->fetch_assoc()): ?>
        <tr class="<?= (int)$p['activo'] === 0 ? 'fila-anulada' : ($p['stock_actual'] <= $p['stock_minimo'] ? 'fila-alerta' : '') ?>">
            <td><?= htmlspecialchars($p['nombre']) ?></td>
            <td><?= htmlspecialchars($p['categoria'] ?? '—') ?></td>
            <td>$<?= number_format($p['precio'], 2) ?></td>
            <td><?= $p['stock_actual'] ?></td>
            <td>
                <?= $p['stock_actual'] <= $p['stock_minimo'] ? '⚠️ Stock bajo' : '✅ OK' ?>
            </td>
            <td>
                <form action="../actions/producto_editar_action.php" method="POST" class="form-editar">
                    <?= csrfField() ?>
                    <input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>">
                    <input type="number" name="precio" value="<?= $p['precio'] ?>"
                           step="0.01" min="0.01" title="Precio">
                    <input type="number" name="stock_minimo" value="<?= (int)$p['stock_minimo'] ?>"
                           min="0" title="Stock mínimo">
                    <button type="submit">Guardar</button>
                </form>
            </td>
            <td>
                <?php if ((int)$p['activo'] === 0): ?>
                    <form action="../actions/producto_estado_action.php" method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>">
                        <input type="hidden" name="accion" value="reactivar">
                        <button type="submit" class="btn-secundario">Reactivar</button>
                    </form>
                <?php else: ?>
                    <form action="../actions/producto_estado_action.php" method="POST"
                          onsubmit="return confirm('Dar de baja este producto? Dejara de aparecer en el punto de venta.');">
                        <?= csrfField() ?>
                        <input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>">
                        <input type="hidden" name="accion" value="baja">
                        <button type="submit" class="btn-peligro">Dar de baja</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../includes/footer.php'; ?>