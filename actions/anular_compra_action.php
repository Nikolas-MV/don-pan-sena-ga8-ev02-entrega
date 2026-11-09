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

requireOwner();

$idCompra = (int)($_POST['id_compra'] ?? 0);

if ($idCompra <= 0) {
    header('Location: ../public/compras.php?error=invalida');
    exit;
}

$conn = getConnection();
$conn->begin_transaction();

try {
    // Bloquea la compra para que dos anulaciones simultaneas no se pisen.
    $stmtCompra = $conn->prepare(
        'SELECT id_compra, anulada FROM compras WHERE id_compra = ? FOR UPDATE'
    );
    $stmtCompra->bind_param('i', $idCompra);
    $stmtCompra->execute();
    $compra = $stmtCompra->get_result()->fetch_assoc();

    if (!$compra) {
        throw new Exception("La compra $idCompra no existe");
    }

    if ((int)$compra['anulada'] === 1) {
        $conn->rollback();
        header('Location: ../public/compras.php?error=ya_anulada');
        exit;
    }

    $stmtDetalle = $conn->prepare(
        'SELECT dc.id_insumo, dc.cantidad, i.stock_actual
         FROM detalle_compras dc
         JOIN insumos i ON i.id_insumo = dc.id_insumo
         WHERE dc.id_compra = ?'
    );
    $stmtDetalle->bind_param('i', $idCompra);
    $stmtDetalle->execute();
    $resultDetalle = $stmtDetalle->get_result();

    $stmtStock = $conn->prepare(
        'UPDATE insumos SET stock_actual = stock_actual - ? WHERE id_insumo = ?'
    );
    $stmtMov = $conn->prepare(
        "INSERT INTO movimientos_insumos (id_insumo, id_empleado, tipo, cantidad, motivo)
         VALUES (?, ?, 'salida', ?, ?)"
    );

    $motivo = 'Anulacion de la compra #' . $idCompra;

    // Primero se valida todo: si algun insumo quedaria negativo, se cancela.
    $porDescontar = [];
    while ($d = $resultDetalle->fetch_assoc()) {
        $cantidad = (int)$d['cantidad'];
        if ((int)$d['stock_actual'] < $cantidad) {
            throw new Exception(
                'Stock insuficiente de un insumo para revertir la compra'
            );
        }
        $porDescontar[] = [(int)$d['id_insumo'], $cantidad];
    }

    foreach ($porDescontar as [$idInsumo, $cantidad]) {
        $stmtStock->bind_param('ii', $cantidad, $idInsumo);
        $stmtStock->execute();

        $stmtMov->bind_param('iiis', $idInsumo, $_SESSION['id_empleado'], $cantidad, $motivo);
        $stmtMov->execute();
    }

    $stmtAnular = $conn->prepare(
        'UPDATE compras SET anulada = 1, anulada_por = ?, fecha_anulacion = NOW()
         WHERE id_compra = ?'
    );
    $stmtAnular->bind_param('ii', $_SESSION['id_empleado'], $idCompra);
    $stmtAnular->execute();

    $conn->commit();
    header('Location: ../public/compras.php?ok=anulada');
    exit;
} catch (Exception $e) {
    $conn->rollback();
    logAndRedirect('../public/compras.php', $e);
}