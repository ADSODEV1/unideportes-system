<?php
// controllers/registrar_pago_pedido.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin', 'colaborador', 'vendedor']);

$pdo = app();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedido_id = intval($_POST['pedido_id'] ?? 0);
    $monto = floatval($_POST['monto'] ?? 0);
    $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';

    if ($pedido_id > 0 && $monto > 0) {
        try {
            //Insertar con todos los campos
            $stmt = $pdo->prepare("
                INSERT INTO pagos (id_pg_pedido, monto, metodo_pago, fecha) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$pedido_id, $monto, $metodo_pago]);

            header("Location: /unideportes-system/views/mis_pedidos.php?status=pago_registrado");
            exit();
        } catch (Exception $e) {
            error_log("Error registrando pago: " . $e->getMessage());
            header("Location: /unideportes-system/views/mis_pedidos.php?status=error");
            exit();
        }
    }
}

header("Location: /unideportes-system/views/mis_pedidos.php");
exit();