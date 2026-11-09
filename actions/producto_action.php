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

$nombre = trim($_POST['nombre'] ?? '');
$precio = (float)($_POST['precio'] ?? 0);
$stock = (int)($_POST['stock'] ?? 0);
$stockMinimo = (int)($_POST['stock_minimo'] ?? 0);
$idCategoria = (int)($_POST['id_categoria'] ?? 0);

if ($nombre === '' || $precio <= 0 || $stock < 0) {
    header('Location: ../public/inventario.php?error=campos');
    exit;
}

$conn = getConnection();
$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        'INSERT INTO productos (nombre, precio, stock_actual, stock_minimo, id_categoria)
         VALUES (?, ?, ?, ?, ?)'
    );
    $idCategoria = $idCategoria > 0 ? $idCategoria : null;
    $stmt->bind_param('sdiii', $nombre, $precio, $stock, $stockMinimo, $idCategoria);
    $stmt->execute();
    $idProducto = $conn->insert_id;

    // Si se registró stock inicial > 0, lo reflejamos como entrada de inventario
    if ($stock > 0) {
        $stmtMov = $conn->prepare(
            "INSERT INTO movimientos_inventario (id_producto, tipo, cantidad, motivo)
             VALUES (?, 'entrada', ?, 'Registro inicial')"
        );
        $stmtMov->bind_param('ii', $idProducto, $stock);
        $stmtMov->execute();
    }

    $conn->commit();
    header('Location: ../public/inventario.php?ok=producto');
    exit;
} catch (Exception $e) {
    $conn->rollback();
    logAndRedirect('../public/inventario.php', $e);
}
