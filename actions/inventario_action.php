<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/inventario.php');
    exit;
}

csrfVerify();

$idProducto = (int)($_POST['id_producto'] ?? 0);
$cantidad = (int)($_POST['cantidad'] ?? 0);

if ($idProducto <= 0 || $cantidad <= 0) {
    header('Location: ../public/inventario.php?error=1');
    exit;
}

$conn = getConnection();
$conn->begin_transaction();

try {
    $stmt = $conn->prepare('UPDATE productos SET stock_actual = stock_actual + ? WHERE id_producto = ?');
    $stmt->bind_param('ii', $cantidad, $idProducto);
    $stmt->execute();

    $stmtMov = $conn->prepare(
        "INSERT INTO movimientos_inventario (id_producto, tipo, cantidad, motivo) VALUES (?, 'entrada', ?, 'Recepción de proveedor')"
    );
    $stmtMov->bind_param('ii', $idProducto, $cantidad);
    $stmtMov->execute();

    $conn->commit();
    header('Location: ../public/inventario.php?ok=1');
    exit;
} catch (Exception $e) {
    $conn->rollback();
    logAndRedirect('../public/inventario.php', $e);
}