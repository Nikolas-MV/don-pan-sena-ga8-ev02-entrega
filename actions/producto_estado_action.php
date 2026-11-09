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
$accion = $_POST['accion'] ?? '';

if ($idProducto <= 0 || !in_array($accion, ['baja', 'reactivar'], true)) {
    header('Location: ../public/productos.php?error=1');
    exit;
}

$conn = getConnection();

try {
    // Baja logica: no se borra para conservar el historial de ventas.
    $nuevoEstado = $accion === 'baja' ? 0 : 1;
    $stmt = $conn->prepare('UPDATE productos SET activo = ? WHERE id_producto = ?');
    $stmt->bind_param('ii', $nuevoEstado, $idProducto);
    $stmt->execute();

    header('Location: ../public/productos.php?ok=' . $accion);
    exit;
} catch (Exception $e) {
    logAndRedirect('../public/productos.php', $e);
}