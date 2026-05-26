<?php
// controllers/procesar_entrega_controller.php
require_once __DIR__ . '/../config/bootstrap.php';

$pdo = app();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dale acceso a todo tu equipo operativo
require_login(['admin', 'vendedor', 'colaborador']);

// ... Resto del código que hace el UPDATE pedidos SET estado = 'Entregado' ...

$pedido_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($pedido_id <= 0) {
    header("Location: /unideportes-system/views/mis_pedidos.php?status=error");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Calcular el saldo pendiente usando la estructura real de tu BD
    $sqlSaldo = "SELECT p.total_pedido, IFNULL(SUM(pa.monto), 0) AS total_pagado
                 FROM pedidos p
                 LEFT JOIN pagos pa ON p.id = pa.id_pg_pedido
                 WHERE p.id = :id
                 GROUP BY p.id";
    $stmtSaldo = $pdo->prepare($sqlSaldo);
    $stmtSaldo->execute([':id' => $pedido_id]);
    $valores = $stmtSaldo->fetch(PDO::FETCH_ASSOC);

    if (!$valores) {
        throw new Exception("El pedido no existe.");
    }

    $saldo_pendiente = $valores['total_pedido'] - $valores['total_pagado'];

    // 2. Si hay saldo, insertar en 'pagos' usando tus columnas: id_pg_pedido, monto, fecha
    if ($saldo_pendiente > 0) {
        $sqlPago = "INSERT INTO pagos (id_pg_pedido, monto, fecha) 
                    VALUES (:id_pedido, :monto, NOW())";
        $stmtPago = $pdo->prepare($sqlPago);
        $stmtPago->execute([
            ':id_pedido' => $pedido_id,
            ':monto' => $saldo_pendiente
        ]);
    }

    // 3. Actualizar el estado del pedido a 'Entregado'
    $sqlUpdate = "UPDATE pedidos SET estado = 'Entregado' WHERE id = :id";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([':id' => $pedido_id]);

    $pdo->commit();

    header("Location: /unideportes-system/views/mis_pedidos.php?status=success");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: /unideportes-system/views/mis_pedidos.php?status=error");
    exit;
}