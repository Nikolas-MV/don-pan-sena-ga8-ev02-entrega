<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/productos.php');
    exit;
}

csrfVerify();

$idProducto = (int)($_POST['id_producto'] ?? 0);
$precio = (float)($_POST['precio'] ?? 0);
$stockMinimo = (int)($_POST['stock_minimo'] ?? 0);

if ($idProducto <= 0 || $precio <= 0 || $stockMinimo < 0) {
    header('Location: ../public/productos.php?error=campos');
    exit;
}

$conn = getConnection();

try {
    $stmt = $conn->prepare(
        'UPDATE productos SET precio = ?, stock_minimo = ? WHERE id_producto = ?'
    );
    $stmt->bind_param('dii', $precio, $stockMinimo, $idProducto);
    $stmt->execute();

    header('Location: ../public/productos.php?ok=1');
    exit;
} catch (Exception $e) {
    logAndRedirect('../public/productos.php', $e);
}