<?php
// controllers/marcar_entrega.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin', 'colaborador', 'vendedor']);
$pdo = app();

$venta_id = intval($_GET['id'] ?? 0);

if ($venta_id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE ventas SET estado = 'Entregado' WHERE id = ? AND estado = 'En Camino'");
        $stmt->execute([$venta_id]);
        
        header("Location: ../views/seguimiento_entregas.php?success=entregado");
        exit();
    } catch (Exception $e) {
        header("Location: ../views/seguimiento_entregas.php?error=1");
        exit();
    }
}

header("Location: ../views/seguimiento_entregas.php");
exit();