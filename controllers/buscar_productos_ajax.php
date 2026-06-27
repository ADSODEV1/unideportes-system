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
    SELECT id, nombre, referencia, stock 
    FROM productos 
    WHERE (nombre LIKE :q OR referencia LIKE :q)
    AND estado = 'activo'
    ORDER BY nombre ASC
    LIMIT 5
");
$stmt->execute(['q' => "%{$query}%"]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));