<?php
// controllers/insert_product.php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ProductoModel.php';

require_login(['admin']);
$conn = app();

// 1. FILTRO DE SEGURIDAD PARA EL MÉTODO DE ENVÍO
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/registrar_productos.php');
    exit();
}

// 2. CAPTURA Y SANITIZACIÓN DE DATOS
$data = [
    'nombre'     => trim($_POST['nombre'] ?? ''),
    'referencia' => trim($_POST['referencia'] ?? ''),
    'talla'      => trim($_POST['talla'] ?? ''),
    'categoria'  => trim($_POST['categoria'] ?? ''), 
    'stock'      => intval($_POST['stock'] ?? 0),
    'precio'     => floatval($_POST['precio'] ?? 0.0),
];


// 3. VALIDACIÓN ESTRICTA DE CAMPOS OBLIGATORIOS
if ($data['nombre'] === '' || $data['referencia'] === '' || $data['categoria']  === '' || $data['precio'] <= 0) {
    header('Location: ../views/registrar_productos.php?error=datos_invalidos');
    exit();
}

// 4. CONTROL DE DUPLICADOS EN BASE DE DATOS (Llamando al Modelo)
if (existeReferenciaProducto($conn, $data['referencia'])) {
    header('Location: ../views/registrar_productos.php?error=referencia_duplicada');
    exit();
}

// 5. INTENTO DE INSERCIÓN
if (crearProducto($conn, $data)) {
    header('Location: ../views/inventario.php?success=producto_registrado');
    exit();
}

// Si la base de datos falla por otra razón
header('Location: ../views/registrar_productos.php?error=fallo_en_registro');
exit();