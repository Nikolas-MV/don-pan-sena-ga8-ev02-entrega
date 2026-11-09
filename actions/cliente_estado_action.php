<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/clientes.php');
    exit;
}

csrfVerify();

$idCliente = (int)($_POST['id_cliente'] ?? 0);
$accion = $_POST['accion'] ?? '';

if ($idCliente <= 0 || !in_array($accion, ['baja', 'reactivar'], true)) {
    header('Location: ../public/clientes.php?error=1');
    exit;
}

$conn = getConnection();

try {
    // Baja logica: el historial de compras del cliente se conserva.
    $nuevoEstado = $accion === 'baja' ? 0 : 1;
    $stmt = $conn->prepare('UPDATE clientes SET activo = ? WHERE id_cliente = ?');
    $stmt->bind_param('ii', $nuevoEstado, $idCliente);
    $stmt->execute();

    header('Location: ../public/clientes.php?ok=' . $accion);
    exit;
} catch (Exception $e) {
    logAndRedirect('../public/clientes.php', $e);
}