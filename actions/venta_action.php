<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/ventas.php');
    exit;
}

csrfVerify();

try {
    $cantidades = donPanCantidadesVenta($_POST['cantidad'] ?? []);
} catch (InvalidArgumentException $e) {
    header('Location: ../public/ventas.php?error=campos');
    exit;
}

if (empty($cantidades)) {
    header('Location: ../public/ventas.php?error=vacio');
    exit;
}

$conn = getConnection();
$conn->begin_transaction();

try {
    // Turno actual del empleado (simplificado: se toma el turno asignado en su perfil)
    $stmtTurno = $conn->prepare('SELECT id_turno FROM empleados WHERE id_empleado = ?');
    $stmtTurno->bind_param('i', $_SESSION['id_empleado']);
    $stmtTurno->execute();
    $idTurno = $stmtTurno->get_result()->fetch_assoc()['id_turno'];

    // Cliente opcional (Conexión 4: Cliente <-> Sistema)
    $idCliente = (int)($_POST['id_cliente'] ?? 0);
    $idCliente = $idCliente > 0 ? $idCliente : null;

    $metodoPago = $_POST['metodo_pago'] ?? 'efectivo';
    if (!in_array($metodoPago, ['efectivo', 'tarjeta', 'transferencia'], true)) {
        $metodoPago = 'efectivo';
    }

    $stmtVenta = $conn->prepare(
        'INSERT INTO ventas (id_empleado, id_turno, id_cliente, total, metodo_pago)
         VALUES (?, ?, ?, 0, ?)'
    );
    $stmtVenta->bind_param('iiss', $_SESSION['id_empleado'], $idTurno, $idCliente, $metodoPago);
    $stmtVenta->execute();
    $idVenta = $conn->insert_id;

    $total = 0;

    $stmtProducto = $conn->prepare(
        'SELECT precio, stock_actual, activo FROM productos WHERE id_producto = ? FOR UPDATE'
    );
    $stmtDetalle = $conn->prepare(
        'INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?)'
    );
    $stmtStock = $conn->prepare(
        'UPDATE productos SET stock_actual = stock_actual - ? WHERE id_producto = ?'
    );
    $stmtMov = $conn->prepare(
        "INSERT INTO movimientos_inventario (id_producto, tipo, cantidad, motivo) VALUES (?, 'salida', ?, 'Venta')"
    );

    foreach ($cantidades as $idProducto => $cantidad) {
        $idProducto = (int)$idProducto;
        $cantidad = (int)$cantidad;

        $stmtProducto->bind_param('i', $idProducto);
        $stmtProducto->execute();
        $prod = $stmtProducto->get_result()->fetch_assoc();

        $cantidad = donPanValidarLineaVenta($cantidad, $prod ?: null);

        $subtotal = $prod['precio'] * $cantidad;
        $total += $subtotal;

        $stmtDetalle->bind_param('iiidd', $idVenta, $idProducto, $cantidad, $prod['precio'], $subtotal);
        $stmtDetalle->execute();

        $stmtStock->bind_param('ii', $cantidad, $idProducto);
        $stmtStock->execute();

        $stmtMov->bind_param('ii', $idProducto, $cantidad);
        $stmtMov->execute();
    }

    $stmtTotal = $conn->prepare('UPDATE ventas SET total = ? WHERE id_venta = ?');
    $stmtTotal->bind_param('di', $total, $idVenta);
    $stmtTotal->execute();

    $conn->commit();
    header('Location: ../public/ventas.php?ok=1');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    logAndRedirect('../public/ventas.php', $e);
}