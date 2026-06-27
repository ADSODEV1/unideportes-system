<?php
// controllers/marcar_venta_mayorista_entregada.php
// Marca una venta mayorista como entregada
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin', 'colaborador', 'vendedor']);

$pdo = app();
$venta_id = intval($_GET['id'] ?? 0);

if ($venta_id > 0) {
    try {
        // Verificar que no haya saldo pendiente
        $stmt = $pdo->prepare("
            SELECT total_venta, abono,
            IFNULL((SELECT SUM(monto) FROM pagos_venta WHERE venta_id = ?), 0) as pagos_extra
            FROM ventas WHERE id = ?
        ");
        $stmt->execute([$venta_id, $venta_id]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $saldo = $venta['total_venta'] - $venta['abono'] - $venta['pagos_extra'];
        
        if ($saldo > 0) {
            header("Location: /unideportes-system/views/ventas_mayoristas_pendientes.php?status=error_saldo");
            exit();
        }
        
        // Marcar como entregado
        $stmt = $pdo->prepare("UPDATE ventas SET estado = 'Entregado' WHERE id = ?");
        $stmt->execute([$venta_id]);
        
        header("Location: /unideportes-system/views/ventas_mayoristas_pendientes.php?status=entregado");
        exit();
        
    } catch (Exception $e) {
        header("Location: /unideportes-system/views/ventas_mayoristas_pendientes.php?status=error");
        exit();
    }
}

header("Location: /unideportes-system/views/ventas_mayoristas_pendientes.php");
exit();