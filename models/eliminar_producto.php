<?php
// eliminar_producto.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app(); // Usamos la conexión PDO unificada del ecosistema

// 1. SEGURIDAD: Solo Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: inventario.php?error=no_autorizado");
    exit();
}

// 2. RECIBIR EL ID
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // 3. SENTENCIA PREPARADA CON PDO (Seguridad informática avanzada)
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        header("Location: inventario.php?success=producto_eliminado");
        exit();
    } else {
        header("Location: inventario.php?error=fallo_eliminacion");
        exit();
    }
} else {
    header("Location: inventario.php");
    exit();
}