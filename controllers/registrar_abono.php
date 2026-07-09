<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$pdo = app();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mis_pedidos.php");
    exit;
}

$id_pedido = isset($_POST['id_pedido']) ? (int)$_POST['id_pedido'] : 0;
$monto = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;

if ($id_pedido <= 0 || $monto <= 0) {
    $_SESSION['error'] = "Pedido o monto inválido";
    header("Location: mis_pedidos.php");
    exit;
}

try {
    // Llamar al procedimiento (solo 2 parámetros)
    $sql = "CALL sp_registrar_pago(:id_pedido, :monto)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_pedido' => $id_pedido,
        ':monto' => $monto
    ]);
    
    $stmt->closeCursor();
    
    $_SESSION['exito'] = "Abono registrado correctamente";
    header("Location: detalle_pedido.php?id=" . $id_pedido);
    exit;
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al registrar el abono: " . $e->getMessage();
    header("Location: detalle_pedido.php?id=" . $id_pedido);
    exit;
}
?>