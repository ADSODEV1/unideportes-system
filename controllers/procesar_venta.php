<?php
session_start();
include("connection.php");
$conn = connection();

// 1. SEGURIDAD: Verificar sesión y que los datos vengan por POST
if (!isset($_SESSION['username']) || !isset($_POST['producto_id'])) {
    header("Location: index.php");
    exit();
}

// Recibimos los datos
$cliente_id       = $_POST['cliente_id'];
$producto_id      = $_POST['producto_id'];
$cantidad_vendida = intval($_POST['cantidad']);
$vendedor_id      = $_SESSION['id_usuario']; // Asumiendo que guardas el ID al hacer login

// 2. CONSULTA DE PRECIO Y STOCK
$res_prod = mysqli_query($conn, "SELECT precio, stock, nombre FROM productos WHERE id = '$producto_id'");
$producto = mysqli_fetch_array($res_prod);

$precio_unitario = $producto['precio'];
$stock_actual    = $producto['stock'];
$total_venta     = $precio_unitario * $cantidad_vendida;

// 3. VALIDACIÓN CRÍTICA
if ($cantidad_vendida > $stock_actual) {
    header("Location: nueva_venta.php?error=stock_insuficiente");
    exit();
}

// 4. OPERACIÓN EN BASE DE DATOS (Transacción lógica)
// A. Registrar la venta
$sql_venta = "INSERT INTO ventas (cliente_id, vendedor_id, total_venta, fecha) 
              VALUES ('$cliente_id', '$vendedor_id', '$total_venta', NOW())";
$venta_ok = mysqli_query($conn, $sql_venta);

// B. Descontar stock solo si la venta se registró
if ($venta_ok) {
    $nuevo_stock = $stock_actual - $cantidad_vendida;
    $sql_update = "UPDATE productos SET stock = '$nuevo_stock' WHERE id = '$producto_id'";
    mysqli_query($conn, $sql_update);
    
    // ÉXITO: Redirección según el rol
    $destino = ($_SESSION['role'] == 'admin') ? "panel_admin.php" : "panel_vendedor.php";
    header("Location: $destino?success=venta_realizada");
} else {
    header("Location: nueva_venta.php?error=error_registro");
}
exit();
?>