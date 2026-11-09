<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit;
}

csrfVerify();

$intentos = $_SESSION['login_intentos'] ?? 0;
$bloqueadoHasta = $_SESSION['login_bloqueado_hasta'] ?? 0;

if (time() < $bloqueadoHasta) {
    header('Location: ../public/index.php?error=bloqueado');
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

$conn = getConnection();
$stmt = $conn->prepare(
    'SELECT id_empleado, nombre, password_hash, rol FROM empleados WHERE usuario = ? AND activo = 1'
);
$stmt->bind_param('s', $usuario);
$stmt->execute();
$result = $stmt->get_result();
$empleado = $result->fetch_assoc();

if ($empleado && password_verify($password, $empleado['password_hash'])) {
    unset($_SESSION['login_intentos'], $_SESSION['login_bloqueado_hasta']);
    session_regenerate_id(true);
    $_SESSION['id_empleado'] = $empleado['id_empleado'];
    $_SESSION['nombre'] = $empleado['nombre'];
    $_SESSION['rol'] = $empleado['rol'];
    header('Location: ../public/ventas.php');
    exit;
}

$intentos++;
$_SESSION['login_intentos'] = $intentos;
if ($intentos >= 5) {
    $_SESSION['login_bloqueado_hasta'] = time() + 60;
    $_SESSION['login_intentos'] = 0;
}

header('Location: ../public/index.php?error=1');
exit;