<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
requireLogin();

$conn = getConnection();

// Campo de búsqueda del catálogo de clientes (documento de diseño, sección 3.6)
$busqueda = trim($_GET['q'] ?? '');

// Filtro de estado: por defecto solo los clientes activos.
$verInactivos = isset($_GET['inactivos']);

$sql = 'SELECT id_cliente, nombre, telefono, email, direccion, puntos, activo
        FROM clientes';

$where = [];
$params = '';

if (!$verInactivos) {
    $where[] = 'activo = 1';
}

if ($busqueda !== '') {
    $where[] = '(nombre LIKE ? OR telefono LIKE ? OR email LIKE ?)';
    $params = 'sss';
}

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY nombre';

if ($params === '') {
    $clientes = $conn->query($sql);
} else {
    $like = '%' . $busqueda . '%';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($params, $like, $like, $like);
    $stmt->execute();
    $clientes = $stmt->get_result();
}

$pageTitle = 'Clientes';
require __DIR__ . '/../includes/header.php';
?>

<h1>Clientes</h1>

<?php if (($_GET['ok'] ?? '') === 'baja'): ?>
    <p class="alert-warn">Cliente dado de baja. Su historial de compras se conserva.</p>
<?php elseif (($_GET['ok'] ?? '') === 'reactivar'): ?>
    <p class="alert-ok">✅ Cliente reactivado.</p>
<?php elseif (isset($_GET['ok'])): ?>
    <p class="alert-ok">✅ Cliente registrado correctamente.</p>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'campos'): ?>
    <p class="alert-error">⚠️ El nombre del cliente es obligatorio.</p>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'duplicado'): ?>
    <p class="alert-error">⚠️ Ya existe un cliente activo con ese teléfono.</p>
<?php elseif (isset($_GET['error'])): ?>
    <p class="alert-error">⚠️ Ocurrió un error al procesar la solicitud. Intenta de nuevo.</p>
<?php endif; ?>

<section class="nuevo-producto">
    <h2>Registrar nuevo cliente</h2>
    <form action="../actions/cliente_action.php" method="POST" class="form-nuevo">
        <?= csrfField() ?>
        <input type="text" name="nombre" placeholder="Nombre completo" required>
        <input type="text" name="telefono" placeholder="Teléfono">
        <input type="email" name="email" placeholder="Correo electrónico">
        <input type="text" name="direccion" placeholder="Dirección">
        <input type="number" name="puntos" placeholder="Puntos" min="0" value="0">
        <button type="submit">Registrar</button>
    </form>
</section>

<form method="GET" action="clientes.php" class="buscador">
    <input type="text" name="q" placeholder="Buscar por nombre, teléfono o correo"
           value="<?= htmlspecialchars($busqueda) ?>">
    <?php if ($verInactivos): ?>
        <input type="hidden" name="inactivos" value="1">
    <?php endif; ?>
    <button type="submit">Buscar</button>
    <?php if ($busqueda !== ''): ?>
        <a href="clientes.php" class="btn-secundario">Limpiar</a>
    <?php endif; ?>
</form>

<p class="txt-suave">
    <?php if ($verInactivos): ?>
        <a href="clientes.php<?= $busqueda !== '' ? '?q=' . urlencode($busqueda) : '' ?>">
            Mostrar solo clientes activos
        </a>
    <?php else: ?>
        <a href="clientes.php?inactivos=1<?= $busqueda !== '' ? '&q=' . urlencode($busqueda) : '' ?>">
            Ver clientes dados de baja
        </a>
    <?php endif; ?>
</p>

<table class="tabla-clientes">
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Teléfono</th>
            <th>Correo</th>
            <th>Dirección</th>
            <th>Puntos</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($clientes->num_rows === 0): ?>
        <tr>
            <td colspan="6">No se encontraron clientes<?= $busqueda !== '' ? ' para la búsqueda' : '' ?>.</td>
        </tr>
    <?php endif; ?>
    <?php while ($c = $clientes->fetch_assoc()): ?>
        <tr class="<?= (int)$c['activo'] === 0 ? 'fila-anulada' : '' ?>">
            <td><?= htmlspecialchars($c['nombre']) ?></td>
            <td><?= htmlspecialchars($c['telefono'] ?? '') ?></td>
            <td><?= htmlspecialchars($c['email'] ?? '') ?></td>
            <td><?= htmlspecialchars($c['direccion'] ?? '') ?></td>
            <td><?= (int)$c['puntos'] ?></td>
            <td>
                <?php if ((int)$c['activo'] === 0): ?>
                    <form action="../actions/cliente_estado_action.php" method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="id_cliente" value="<?= (int)$c['id_cliente'] ?>">
                        <input type="hidden" name="accion" value="reactivar">
                        <button type="submit" class="btn-secundario">Reactivar</button>
                    </form>
                <?php else: ?>
                    <form action="../actions/cliente_estado_action.php" method="POST"
                          onsubmit="return confirm('Dar de baja a este cliente? Se conservara su historial.');">
                        <?= csrfField() ?>
                        <input type="hidden" name="id_cliente" value="<?= (int)$c['id_cliente'] ?>">
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