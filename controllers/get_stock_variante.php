<?php
// controllers/get_stock_variante.php
// Devuelve el stock disponible de una combinación producto+color+talla

session_start();
require_once __DIR__ . '/../config/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');

require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();

// Recibir parámetros
$producto_id = intval($_GET['producto_id'] ?? 0);
$color = trim($_GET['color'] ?? '');
$talla = trim($_GET['talla'] ?? '');

if ($producto_id <= 0) {
    echo json_encode(['error' => 'ID de producto inválido', 'stock' => 0]);
    exit();
}

try {
    // Buscar el producto específico con ese color y talla
    $sql = "SELECT id, nombre, color, talla, stock, precio 
            FROM productos 
            WHERE id = ? AND stock > 0";
    
    $params = [$producto_id];
    
    // Si se especificó color, filtrar por color
    if (!empty($color)) {
        $sql .= " AND (color = ? OR color IS NULL)";
        $params[] = $color;
    }
    
    // Si se especificó talla, filtrar por talla
    if (!empty($talla)) {
        $sql .= " AND (talla = ? OR talla IS NULL)";
        $params[] = $talla;
    }
    
    $sql .= " LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($producto) {
        echo json_encode([
            'success' => true,
            'stock' => intval($producto['stock']),
            'precio' => floatval($producto['precio']),
            'color' => $producto['color'] ?? 'Sin color',
            'talla' => $producto['talla'] ?? 'Sin talla'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'stock' => 0,
            'message' => 'No hay stock disponible de esta variante'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'stock' => 0]);
}