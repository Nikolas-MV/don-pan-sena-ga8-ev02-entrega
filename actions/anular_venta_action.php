<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/historial_ventas.php');
    exit;
}

csrfVerify();

requireOwner();

$idVenta = (int)($_POST['id_venta'] ?? 0);

if ($idVenta <= 0) {
    header('Location: ../public/historial_ventas.php?error=invalida');
    exit;
}

$conn = getConnection();
$conn->begin_transaction();

try {
    // Bloquea la venta para que dos anulaciones simultaneas no se pisen.
    $stmtVenta = $conn->prepare(
        'SELECT id_venta, anulada FROM ventas WHERE id_venta = ? FOR UPDATE'
    );
    $stmtVenta->bind_param('i', $idVenta);
    $stmtVenta->execute();
    $venta = $stmtVenta->get_result()->fetch_assoc();

    if (!$venta) {
        throw new Exception("La venta $idVenta no existe");
    }

    if ((int)$venta['anulada'] === 1) {
        $conn->rollback();
        header('Location: ../public/historial_ventas.php?error=ya_anulada');
        exit;
    }

    // Devuelve el stock de cada producto y deja el rastro en movimientos.
    $stmtDetalle = $conn->prepare(
        'SELECT id_producto, cantidad FROM detalle_ventas WHERE id_venta = ?'
    );
    $stmtDetalle->bind_param('i', $idVenta);
    $stmtDetalle->execute();
    // Se guarda el result set: llamar a get_result() dentro del while
    // vuelve a ejecutar la sentencia y deja la conexion fuera de sync.
    $resultDetalle = $stmtDetalle->get_result();

    $stmtStock = $conn->prepare(
        'UPDATE productos SET stock_actual = stock_actual + ? WHERE id_producto = ?'
    );
    $stmtMov = $conn->prepare(
        "INSERT INTO movimientos_inventario (id_producto, tipo, cantidad, motivo)
         VALUES (?, 'entrada', ?, ?)"
    );

    $motivo = 'Anulacion de la venta #' . $idVenta;

    while ($d = $resultDetalle->fetch_assoc()) {
        $idProducto = (int)$d['id_producto'];
        $cantidad = (int)$d['cantidad'];

        $stmtStock->bind_param('ii', $cantidad, $idProducto);
        $stmtStock->execute();

        $stmtMov->bind_param('iis', $idProducto, $cantidad, $motivo);
        $stmtMov->execute();
    }

    $stmtAnular = $conn->prepare(
        'UPDATE ventas SET anulada = 1, anulada_por = ?, fecha_anulacion = NOW()
         WHERE id_venta = ?'
    );
    $stmtAnular->bind_param('ii', $_SESSION['id_empleado'], $idVenta);
    $stmtAnular->execute();

    $conn->commit();
    header('Location: ../public/historial_ventas.php?ok=anulada');
    exit;
} catch (Exception $e) {
    $conn->rollback();
    logAndRedirect('../public/historial_ventas.php', $e);
}
