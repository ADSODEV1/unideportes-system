<?php
// controllers/procesar_entrega_controller.php
require_once __DIR__ . '/../config/bootstrap.php';

$pdo = app();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['admin', 'vendedor', 'colaborador']);

// Acepta tanto GET (legacy) como POST (nuevo formulario con pago)
$pedido_id    = intval($_POST['pedido_id'] ?? $_GET['id'] ?? 0);
$pago_recibido = isset($_POST['pago_recibido']) ? floatval($_POST['pago_recibido']) : null;
$metodo_pago  = trim($_POST['metodo_pago'] ?? 'Efectivo');
$accion       = trim($_POST['accion'] ?? 'entregar');

if ($pedido_id <= 0) {
    header("Location: /unideportes-system/views/mis_pedidos.php?status=error");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Calcular saldo pendiente usando la misma logica de la vista:
    //    prioriza pedidos.saldo_pendiente y usa calculo matematico como respaldo.
    $sqlSaldo = "SELECT
                    COALESCE(dt.total_detalle, p.total_pedido, 0) AS total_pedido,
                    COALESCE(p.abono, 0) AS abono_inicial,
                    p.saldo_pendiente AS saldo_pendiente_guardado,
                    IFNULL(SUM(pa.monto), 0) AS total_pagado
                 FROM pedidos p
                 LEFT JOIN (
                     SELECT pedido_id, SUM(cantidad * precio_unitario) AS total_detalle
                     FROM detalle_pedido
                     GROUP BY pedido_id
                 ) dt ON dt.pedido_id = p.id
                 LEFT JOIN pagos pa ON p.id = pa.id_pg_pedido
                 WHERE p.id = :id
                 GROUP BY p.id, dt.total_detalle, p.total_pedido, p.abono, p.saldo_pendiente";

    $stmtSaldo = $pdo->prepare($sqlSaldo);
    $stmtSaldo->execute([':id' => $pedido_id]);
    $valores = $stmtSaldo->fetch(PDO::FETCH_ASSOC);

    if (!$valores) {
        throw new Exception("El pedido no existe.");
    }

    $saldo_matematico = max(0, floatval($valores['total_pedido']) - (floatval($valores['abono_inicial']) + floatval($valores['total_pagado'])));
    $saldo_calculado = isset($valores['saldo_pendiente_guardado'])
        ? max(0, floatval($valores['saldo_pendiente_guardado']))
        : $saldo_matematico;

    if ($accion === 'abonar') {
        // Registrar pago parcial o total sin entregar automaticamente.
        if ($pago_recibido === null || $pago_recibido <= 0) {
            throw new Exception("Debe ingresar un monto de pago mayor a cero.");
        }

        if ($pago_recibido > $saldo_calculado) {
            throw new Exception("El abono no puede ser mayor al saldo pendiente actual.");
        }

        $saldo_anterior = $saldo_calculado;

        $sqlPago = "INSERT INTO pagos (id_pg_pedido, monto, fecha) VALUES (:id_pedido, :monto, NOW())";
        $stmtPago = $pdo->prepare($sqlPago);
        $stmtPago->execute([':id_pedido' => $pedido_id, ':monto' => $pago_recibido]);

        $nuevo_saldo = max(0, $saldo_calculado - $pago_recibido);
        $sqlUpdate = "UPDATE pedidos SET saldo_pendiente = :saldo WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([':saldo' => $nuevo_saldo, ':id' => $pedido_id]);

        // Mantener detalle_pedido sincronizado con la cartera unificada.
        $sqlSyncDetalle = "UPDATE detalle_pedido
                           SET saldo_pendiente = :saldo,
                               estado_cartera = CASE WHEN :saldo <= 0 THEN 'Pagado' ELSE 'Por Pagar' END
                           WHERE pedido_id = :id";
        $stmtSyncDetalle = $pdo->prepare($sqlSyncDetalle);
        $stmtSyncDetalle->execute([':saldo' => $nuevo_saldo, ':id' => $pedido_id]);

        $pdo->commit();
        header("Location: /unideportes-system/views/mis_pedidos.php?status=pago_success&monto=" . urlencode((string)$pago_recibido) . "&saldo_anterior=" . urlencode((string)$saldo_anterior) . "&saldo_actual=" . urlencode((string)$nuevo_saldo));
        exit;
    }

    if ($accion === 'entregar') {
        // Solo permitir entrega cuando no hay saldo pendiente.
        if ($saldo_calculado > 0) {
            throw new Exception("No se puede entregar: el pedido aun tiene saldo pendiente.");
        }

        $sqlUpdate = "UPDATE pedidos SET estado = 'Entregado', saldo_pendiente = 0 WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([':id' => $pedido_id]);

        $sqlSyncDetalle = "UPDATE detalle_pedido
                           SET saldo_pendiente = 0,
                               estado_cartera = 'Pagado'
                           WHERE pedido_id = :id";
        $stmtSyncDetalle = $pdo->prepare($sqlSyncDetalle);
        $stmtSyncDetalle->execute([':id' => $pedido_id]);

        $pdo->commit();
        header("Location: /unideportes-system/views/mis_pedidos.php?status=success");
        exit;
    }

    throw new Exception("Accion no valida.");

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: /unideportes-system/views/mis_pedidos.php?status=error&msg=" . urlencode($e->getMessage()));
    exit;
}