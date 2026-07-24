<?php
// controllers/confirmar_entrega_domicilio.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin', 'colaborador', 'vendedor']);
$pdo = app();

$venta_id = intval($_POST['venta_id'] ?? 0);
$recibido_por = trim($_POST['recibido_por'] ?? '');

if ($venta_id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE ventas SET 
            estado = 'Entregado',
            fecha_entrega_real = NOW(),
            entregado_por = ?
            WHERE id = ? AND estado = 'En Camino'");
        $stmt->execute([$recibido_por, $venta_id]);
        
        header("Location: ../views/seguimiento_entregas.php?success=1");
        exit();
    } catch (Exception $e) {
        header("Location: ../views/seguimiento_entregas.php?error=1");
        exit();
    }
}
header("Location: ../views/seguimiento_entregas.php");
exit();
