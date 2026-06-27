<?php
// controllers/get_clientes_ajax.php
// Devuelve la lista actualizada de clientes en formato JSON
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();

try {
    $stmt = $pdo->query("
        SELECT id, nombre_completo, nit_cedula, direccion, barrio, ciudad, referencia_entrega
        FROM clientes
        WHERE estado = 'activo'
        ORDER BY nombre_completo ASC
    ");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'clientes' => $clientes
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}