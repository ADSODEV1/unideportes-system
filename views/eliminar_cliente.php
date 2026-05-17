<?php
session_start();
require_once __DIR__ . '/../config/connection.php';
$conn = connection();

if (!isset($_SESSION['username'])) {
    header('Location: /unideportes-system/public/index.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: clientes.php?error=id_invalido');
    exit();
}

$id = intval($_GET['id']);

// No eliminar si el cliente tiene pedidos asociados
$check = $conn->prepare('SELECT 1 FROM pedidos WHERE cliente_id = ? LIMIT 1');
if ($check) {
    $check->bind_param('i', $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        header('Location: clientes.php?error=cliente_tiene_pedidos');
        exit();
    }
    $check->close();
}

$stmt = $conn->prepare('DELETE FROM clientes WHERE id = ?');
if ($stmt) {
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

header('Location: clientes.php?msj=cliente_eliminado');
exit();
