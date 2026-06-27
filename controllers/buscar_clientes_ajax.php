<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$pdo = app();
$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, nombre_completo, nit_cedula 
    FROM clientes 
    WHERE (nombre_completo LIKE :q OR nit_cedula LIKE :q)
    AND estado = 'activo'
    ORDER BY nombre_completo ASC
    LIMIT 5
");
$stmt->execute(['q' => "%{$query}%"]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));