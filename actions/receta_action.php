<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/recetas.php');
    exit;
}

csrfVerify();

requireOwner();

$accion = $_POST['accion'] ?? '';

if ($accion === 'eliminar') {
    $idReceta = (int)($_POST['id_receta'] ?? 0);

    if ($idReceta <= 0) {
        header('Location: ../public/recetas.php?error=1');
        exit;
    }

    $conn = getConnection();

    try {
        $stmt = $conn->prepare('DELETE FROM recetas WHERE id_receta = ?');
        $stmt->bind_param('i', $idReceta);
        $stmt->execute();

        header('Location: ../public/recetas.php?ok=eliminada');
        exit;
    } catch (Exception $e) {
        logAndRedirect('../public/recetas.php', $e);
    }
}

$idProducto = (int)($_POST['id_producto'] ?? 0);
$idInsumo = (int)($_POST['id_insumo'] ?? 0);
$cantidad = (float)($_POST['cantidad'] ?? 0);

if ($idProducto <= 0 || $idInsumo <= 0 || $cantidad <= 0) {
    header('Location: ../public/recetas.php?error=campos');
    exit;
}

$conn = getConnection();

// El indice unico tambien lo impide, pero avisamos antes para no ver un error 500.
$stmtDup = $conn->prepare(
    'SELECT id_receta FROM recetas WHERE id_producto = ? AND id_insumo = ? LIMIT 1'
);
$stmtDup->bind_param('ii', $idProducto, $idInsumo);
$stmtDup->execute();
if ($stmtDup->get_result()->fetch_assoc()) {
    header('Location: ../public/recetas.php?error=duplicado');
    exit;
}

try {
    $stmt = $conn->prepare(
        'INSERT INTO recetas (id_producto, id_insumo, cantidad) VALUES (?, ?, ?)'
    );
    $stmt->bind_param('iid', $idProducto, $idInsumo, $cantidad);
    $stmt->execute();

    header('Location: ../public/recetas.php?ok=creada');
    exit;
} catch (Exception $e) {
    logAndRedirect('../public/recetas.php', $e);
}