<?php
// controllers/registrar_pago_venta_mayorista.php
// Registra un abono adicional para una venta mayorista
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin', 'colaborador', 'vendedor']);

$pdo = app();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $venta_id = intval($_POST['venta_id'] ?? 0);
    $monto = floatval($_POST['monto'] ?? 0);
    $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';
    
    if ($venta_id > 0 && $monto > 0) {
        try {
            // Registrar el pago en la tabla pagos_venta
            $stmt = $pdo->prepare("INSERT INTO pagos_venta (venta_id, monto, metodo_pago) VALUES (?, ?, ?)");
            $stmt->execute([$venta_id, $monto, $metodo_pago]);
            
            header("Location: /unideportes-system/views/ventas_mayoristas_pendientes.php?status=pago_registrado");
            exit();
            
        } catch (Exception $e) {
            header("Location: /unideportes-system/views/ventas_mayoristas_pendientes.php?status=error");
            exit();
        }
    }
}

header("Location: /unideportes-system/views/ventas_mayoristas_pendientes.php");
exit();