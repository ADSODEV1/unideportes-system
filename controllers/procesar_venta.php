<?php
// 1. ZONA DE SEGURIDAD: Configuracion, conexion y seguridad
session_start();
require_once __DIR__ . '/../config/connection.php';
$conn = connection();

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'] ?? '', ['vendedor', 'colaborador', 'admin'], true)) {
    header("Location: /unideportes-system/public/index.php?error=acceso_denegado");
    exit();
}

// 2. ZONA DE CAPTURA DE DATOS: Recepcion de variables desde el formulario (POST)
if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_POST['cliente_id']) || empty($_POST['venta_json']) || empty($_POST['metodo_pago'])) {
    header("Location: ../views/nueva_venta.php?error=datos_incompletos");
    exit();
}

$cliente_id = intval($_POST['cliente_id']);
$total_venta = floatval($_POST['total_venta']);
$venta_json = json_decode($_POST['venta_json'], true);

$metodo_pago = mysqli_real_escape_string($conn, $_POST['metodo_pago']);

// 3. ZONA DE VALIDACION: Verificacion de la integridad de los datos
$tipo_transferencia = "NULL";
if ($metodo_pago === 'Transferencia' && !empty($_POST['tipo_transferencia'])) {
    $tipo_transferencia = "'" . mysqli_real_escape_string($conn, $_POST['tipo_transferencia']) . "'";
}

$res_cliente = mysqli_query($conn, "SELECT id FROM clientes WHERE id = '$cliente_id'");
if (mysqli_num_rows($res_cliente) == 0) {
    header("Location: ../views/nueva_venta.php?error=cliente_inexistente");
    exit();
}

$res_vendedor = mysqli_query($conn, "SELECT id FROM usuarios WHERE username = '" . mysqli_real_escape_string($conn, $_SESSION['username']) . "'");
$vendedor = mysqli_fetch_array($res_vendedor);
$vendedor_id = $vendedor['id'] ?? null;

if (!$vendedor_id) {
    header("Location: ../views/nueva_venta.php?error=vendedor_inexistente");
    exit();
}

// 4. ZONA DE CONSULTAS SQL: Operacion transaccional y persistencia en la Base de Datos. 
mysqli_begin_transaction($conn);

try {
    // CREAR REGISTRO DE VENTA 
    $sql_venta = "INSERT INTO ventas (cliente_id, vendedor_id, total_venta, metodo_pago, tipo_transferencia, fecha_venta) 
                  VALUES ($cliente_id, $vendedor_id, $total_venta, '$metodo_pago', $tipo_transferencia, NOW())";
    
    if (!mysqli_query($conn, $sql_venta)) {
        throw new Exception("Error al registrar venta: " . mysqli_error($conn));
    }
    
    $venta_id = mysqli_insert_id($conn);
    
    // B. PROCESAR CADA DETALLE DE VENTA
    foreach ($venta_json as $detalle) {
        $producto_id = intval($detalle['producto_id']);
        $cantidad = intval($detalle['cantidad']);
        $precio_unitario = floatval($detalle['precio_unitario']);
        
        // Calculamos el subtotal directamente para asegurar consistencia matemática
        $subtotal = $cantidad * $precio_unitario;
        
        // Validar que el producto existe
        $res_prod = mysqli_query($conn, "SELECT stock FROM productos WHERE id = $producto_id");
        if (mysqli_num_rows($res_prod) == 0) {
            throw new Exception("Producto $producto_id no existe");
        }
        
        $prod = mysqli_fetch_array($res_prod);
        $stock_actual = $prod['stock'];
        
        // Validar stock suficiente
        if ($cantidad > $stock_actual) {
            throw new Exception("Stock insuficiente para el producto $producto_id. Disponible: $stock_actual, Solicitado: $cantidad");
        }
        
        // INSERTAR DETALLE DE VENTA
        $sql_detalle = "INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                        VALUES ($venta_id, $producto_id, $cantidad, $precio_unitario, $subtotal)";
        
        if (!mysqli_query($conn, $sql_detalle)) {
            throw new Exception("Error al registrar detalle: " . mysqli_error($conn));
        }
        
        // DESCONTAR DEL INVENTARIO
        $nuevo_stock = $stock_actual - $cantidad;
        $sql_update = "UPDATE productos SET stock = $nuevo_stock WHERE id = $producto_id";
        
        if (!mysqli_query($conn, $sql_update)) {
            throw new Exception("Error al actualizar inventario: " . mysqli_error($conn));
        }
    }
    
    // 5. ZONA DE CONFIRMACIÓN O REVERSIÓN: Cierre seguro de la transacción
    mysqli_commit($conn);
    
    // 7. REDIRIGIR CON ÉXITO
    header("Location: ../views/panel_vendedor.php?success=venta_registrada&id=$venta_id");
    exit();
    
} catch (Exception $e) {
    // REVERTIR TRANSACCIÓN EN CASO DE ERROR 
    mysqli_rollback($conn);
    
    $error = urlencode($e->getMessage());
    header("Location: ../views/nueva_venta.php?error=$error");
    exit();
}

mysqli_close($conn);
