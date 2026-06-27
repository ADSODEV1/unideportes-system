<?php
// controllers/registrar_pago_venta.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $venta_id = intval($_POST['venta_id'] ?? 0);
    $monto = floatval($_POST['monto'] ?? 0);
    $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';
    
    if ($venta_id > 0 && $monto > 0) {
        try {
            // Registrar el pago
            $stmt = $pdo->prepare("INSERT INTO pagos_venta (venta_id, monto, metodo_pago) VALUES (?, ?, ?)");
            $stmt->execute([$venta_id, $monto, $metodo_pago]);
            
            // Redirigir de vuelta al panel
            header("Location: /unideportes-system/views/ventas_mayoristas_pendientes.php?success=pago_registrado");
            exit();
            
        } catch (Exception $e) {
            header("Location: /unideportes-system/views/ventas_mayoristas_pendientes.php?error=" . urlencode($e->getMessage()));
            exit();
        }
    }
}

header("Location: /unideportes-system/views/ventas_mayoristas_pendientes.php");
exit();
