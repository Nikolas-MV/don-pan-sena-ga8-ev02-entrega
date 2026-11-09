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

$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$email = trim($_POST['email'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$puntos = (int)($_POST['puntos'] ?? 0);

if ($nombre === '') {
    header('Location: ../public/clientes.php?error=campos');
    exit;
}

if ($puntos < 0) {
    $puntos = 0;
}

$conn = getConnection();

// Evita clientes duplicados por teléfono entre los que están activos.
// Un cliente dado de baja no bloquea el registro de uno nuevo.
if ($telefono !== '') {
    $stmtDup = $conn->prepare(
        'SELECT id_cliente FROM clientes WHERE telefono = ? AND activo = 1 LIMIT 1'
    );
    $stmtDup->bind_param('s', $telefono);
    $stmtDup->execute();
    if ($stmtDup->get_result()->fetch_assoc()) {
        header('Location: ../public/clientes.php?error=duplicado');
        exit;
    }
}

try {
    $stmt = $conn->prepare(
        'INSERT INTO clientes (nombre, telefono, email, direccion, puntos) VALUES (?,?,?,?,?)'
    );
    $stmt->bind_param('ssssi', $nombre, $telefono, $email, $direccion, $puntos);
    $stmt->execute();

    header('Location: ../public/clientes.php?ok=1');
    exit;
} catch (Exception $e) {
    logAndRedirect('../public/clientes.php', $e);
}