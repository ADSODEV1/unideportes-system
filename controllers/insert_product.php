<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ProductoModel.php';

require_login(['admin']);
$conn = app();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/productos.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/productos.php');
    exit();
}

$data = [
    'nombre' => trim($_POST['nombre'] ?? ''),
    'referencia' => trim($_POST['referencia'] ?? ''),
    'talla' => trim($_POST['talla'] ?? ''),
    'stock' => intval($_POST['stock'] ?? 0),
    'precio' => floatval($_POST['precio'] ?? 0.0),
];

if ($data['nombre'] === '' || $data['referencia'] === '' || $data['precio'] <= 0) {
    header('Location: ../views/productos.php?error=datos_invalidos');
    exit();
}

if (existeReferenciaProducto($conn, $data['referencia'])) {
    header('Location: ../views/productos.php?error=referencia_duplicada');
    exit();
}

if (crearProducto($conn, $data)) {
    header('Location: ../views/inventario.php?success=producto_registrado');
    exit();
}

header('Location: ../views/productos.php?error=fallo_en_registro');
exit();
