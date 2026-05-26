<?php
// insert_product.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();

// 1. SEGURIDAD: Solo admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=acceso_denegado");
    exit();
}

// 2. VERIFICAR QUE LOS DATOS LLEGUEN POR POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre     = trim($_POST['nombre'] ?? '');
    $referencia = trim($_POST['referencia'] ?? '');
    $talla      = trim($_POST['talla'] ?? '');
    $stock      = intval($_POST['stock'] ?? 0);
    $precio     = floatval($_POST['precio'] ?? 0.00);

    // Validación básica en backend
    if (empty($nombre) || empty($referencia)) {
        header("Location: registrar_productos.php?error=campos_vacios");
        exit();
    }

    // Autogenerar código descriptivo incremental basado en el total actual
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM productos");
    $totalProds = $stmtCount->fetchColumn();
    $codigoDescriptivo = 'PROD-' . str_pad($totalProds + 1, 4, '0', STR_PAD_LEFT);

    try {
        // 3. SENTENCIA CON PDO
        $sql = "INSERT INTO productos (codigo_descriptivo, nombre, referencia, talla, stock, precio) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$codigoDescriptivo, $nombre, $referencia, $talla, $stock, $precio])) {
            header("Location: inventario.php?success=producto_registrado");
            exit();
        } else {
            header("Location: registrar_productos.php?error=fallo_en_registro");
            exit();
        }
    } catch (PDOException $e) {
        // Manejo por si la referencia está duplicada (es un campo UNIQUE en tu base de datos)
        header("Location: registrar_productos.php?error=referencia_duplicada");
        exit();
    }
} else {
    header("Location: inventario.php");
    exit();
}