<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/compras.php');
    exit;
}

csrfVerify();

// Solo el dueño gestiona compras a proveedores.
requireOwner();

$idProveedor = (int)($_POST['id_proveedor'] ?? 0);
$costos = array_filter($_POST['costo'] ?? [], fn($c) => (float)$c > 0);

if ($idProveedor <= 0 || empty($costos)) {
    header('Location: ../public/compras.php?error=vacia');
    exit;
}

$conn = getConnection();
$conn->begin_transaction();

try {
    // 1. Cabecera de la compra con total en 0 (se actualiza al final).
    $stmtCompra = $conn->prepare(
        'INSERT INTO compras (id_proveedor, id_empleado, total) VALUES (?, ?, 0)'
    );
    $stmtCompra->bind_param('ii', $idProveedor, $_SESSION['id_empleado']);
    $stmtCompra->execute();
    $idCompra = $conn->insert_id;

    // 2. Detalle: cada insumo comprado con su costo.
    $stmtDetalle = $conn->prepare(
        'INSERT INTO detalle_compras (id_compra, id_insumo, cantidad, precio_unitario, subtotal)
         VALUES (?, ?, ?, ?, ?)'
    );

    // Suma el stock del insumo. El rastro va en movimientos_insumos, porque
    // movimientos_inventario tiene clave foranea a productos, no a insumos.
    $stmtStock = $conn->prepare(
        'UPDATE insumos SET stock_actual = stock_actual + ? WHERE id_insumo = ?'
    );
    $stmtMov = $conn->prepare(
        "INSERT INTO movimientos_insumos (id_insumo, id_empleado, tipo, cantidad, motivo)
         VALUES (?, ?, 'entrada', ?, ?)"
    );

    $total = 0;

    foreach ($costos as $idInsumo => $costo) {
        $idInsumo = (int)$idInsumo;
        $cantidad = (int)($_POST['cantidad'][$idInsumo] ?? 0);
        $costo = (float)$costo;

        if ($cantidad <= 0) {
            continue;
        }

        $subtotal = $cantidad * $costo;
        $total += $subtotal;

        $stmtDetalle->bind_param('iiddd', $idCompra, $idInsumo, $cantidad, $costo, $subtotal);
        $stmtDetalle->execute();

        $stmtStock->bind_param('ii', $cantidad, $idInsumo);
        $stmtStock->execute();

        // bind_param exige variables por referencia: la concatenacion
        // se separa en su propia variable.
        $motivoCompra = 'Compra a proveedor #' . $idCompra;

        $stmtMov->bind_param(
            'iiis',
            $idInsumo,
            $_SESSION['id_empleado'],
            $cantidad,
            $motivoCompra
        );
        $stmtMov->execute();
    }

    // 3. Total de la cabecera.
    $stmtTotal = $conn->prepare('UPDATE compras SET total = ? WHERE id_compra = ?');
    $stmtTotal->bind_param('di', $total, $idCompra);
    $stmtTotal->execute();

    $conn->commit();
    header('Location: ../public/compras.php?ok=1');
    exit;
} catch (Exception $e) {
    $conn->rollback();
    logAndRedirect('../public/compras.php', $e);
}