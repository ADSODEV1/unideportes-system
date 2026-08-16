<?php
// controllers/aumentar_stock.php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ProductoModel.php';

// 🔐 Solo admin y colaborador pueden aumentar stock
require_login(['admin', 'colaborador']);

$pdo = app();

// 1. Solo aceptar peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/inventario.php');
}

// 2. Validar ID del producto
$producto_id = intval($_POST['producto_id'] ?? 0);
if ($producto_id <= 0) {
    redirect('../views/inventario.php?error=producto_invalido');
}

// 3. Validar cantidad
$cantidad = intval($_POST['cantidad'] ?? 0);
if ($cantidad <= 0) {
    // 🆕 CORREGIDO: Ruta relativa hacia la carpeta views/
    redirect("../views/detalle_prod.php?id={$producto_id}&error=cantidad_invalida");
}

// 4. Verificar que el producto existe
$producto = obtenerProductoPorId($pdo, $producto_id);
if (!$producto) {
    redirect('../views/inventario.php?error=producto_no_encontrado');
}

// 5. 🆕 Aumentar stock en la base de datos
if (aumentarStockProducto($pdo, $producto_id, $cantidad)) {
    // 🆕 CORREGIDO: Ruta relativa hacia la carpeta views/
    redirect("../views/detalle_prod.php?id={$producto_id}&success=stock_actualizado");
} else {
    // 🆕 CORREGIDO: Ruta relativa hacia la carpeta views/
    redirect("../views/detalle_prod.php?id={$producto_id}&error=no_se_pudo_actualizar");
}
