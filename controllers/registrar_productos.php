<?php
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();

require_login(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/productos.php');
}

$nombre = trim($_POST['nombre'] ?? '');
$referencia = trim($_POST['referencia'] ?? '');
$talla = trim($_POST['talla'] ?? '');
$stock = intval($_POST['stock'] ?? 0);
$precio = floatval($_POST['precio'] ?? 0);

if ($nombre === '' || $referencia === '' || $precio <= 0) {
    redirect('../views/productos.php?error=datos_invalidos');
}

try {
    $sql = "INSERT INTO productos (nombre, referencia, talla, stock, precio) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$nombre, $referencia, $talla, $stock, $precio]);

    if ($success) {
        redirect('../views/inventario.php?success=producto_registrado');
    }

    redirect('../views/productos.php?error=fallo_en_registro');
} catch (PDOException $e) {
    redirect('../views/productos.php?error=fallo_en_registro');
}
?>