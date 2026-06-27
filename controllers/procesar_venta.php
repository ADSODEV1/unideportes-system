<?php
// controllers/procesar_venta.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

$pdo = app();
require_login(['vendedor', 'colaborador', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/nueva_venta.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. DATOS BÁSICOS
    $cliente_id = !empty($_POST['cliente_id']) ? intval($_POST['cliente_id']) : null;
    $tipo_entrega = $_POST['tipo_entrega'] ?? 'Tienda';
    $tipo_venta = $_POST['tipo_venta'] ?? 'directa';
    $es_mayorista = ($tipo_venta === 'mayorista');

    if (empty($cliente_id) && empty($_POST['nuevo_cliente_nombre_completo'])) {
        $cliente_id = 1;
    }

    // 2. GESTIÓN DE CLIENTE
    if (empty($cliente_id) && !empty($_POST['nuevo_cliente_nombre_completo'])) {
        $nit_cedula = trim($_POST['nuevo_cliente_nit_cedula']);
        
        $stmtCheck = $pdo->prepare("SELECT id FROM clientes WHERE nit_cedula = ?");
        $stmtCheck->execute([$nit_cedula]);
        $clienteExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($clienteExistente) {
            $cliente_id = $clienteExistente['id'];
        } else {
            $stmtCli = $pdo->prepare("INSERT INTO clientes (nombre_completo, nit_cedula, telefono, email, tipo_cliente) VALUES (?, ?, ?, ?, ?)");
            $stmtCli->execute([
                trim($_POST['nuevo_cliente_nombre_completo']),
                $nit_cedula,
                !empty($_POST['nuevo_cliente_telefono']) ? trim($_POST['nuevo_cliente_telefono']) : null,
                !empty($_POST['nuevo_cliente_email']) ? trim($_POST['nuevo_cliente_email']) : null,
                $_POST['nuevo_cliente_tipo_cliente'] ?? 'Individual'
            ]);
            $cliente_id = $pdo->lastInsertId();
        }
    }

    // 3. PROCESAR CARRITO
    $json_raw = $_POST['venta_json'] ?? ($_POST['ventaJSON'] ?? null);
    
    if (empty($json_raw)) {
        throw new Exception("El carrito de compras está vacío.");
    }
    
    $productos_carrito = json_decode($json_raw, true);
    
    if (!is_array($productos_carrito) || count($productos_carrito) === 0) {
        throw new Exception("El formato del carrito es inválido.");
    }

    // 4. CALCULAR TOTALES
    $subtotal = 0;
    $total_unidades = 0;
    
    foreach ($productos_carrito as $item) {
        $precio_unitario = floatval($item['precio_unitario'] ?? $item['precio'] ?? 0);
        $cantidad = intval($item['cantidad'] ?? 0);
        $subtotal += $precio_unitario * $cantidad;
        $total_unidades += $cantidad;
    }

    // 5. DESCUENTO MAYORISTA
    $descuento_monto = 0;
    $factor_descuento = 0;
    
    if ($es_mayorista) {
        if ($total_unidades >= 20) {
            $factor_descuento = 0.10;
        } elseif ($total_unidades >= 10) {
            $factor_descuento = 0.05;
        }
        $descuento_monto = $subtotal * $factor_descuento;
    }
    
    $total_venta = $subtotal - $descuento_monto;

    // 6. DATOS DE ENVÍO
    $costo_envio = 0.00;
    $direccion_entrega = null;
    $barrio_entrega = null;
    $ciudad_entrega = null;
    $observaciones_entrega = null;
    
    if ($tipo_entrega === 'Domicilio') {
        $costo_envio = 5000.00;
        $total_venta += $costo_envio;
        $direccion_entrega = !empty($_POST['direccion_entrega']) ? trim($_POST['direccion_entrega']) : null;
        $barrio_entrega = !empty($_POST['barrio_entrega']) ? trim($_POST['barrio_entrega']) : null;
        $ciudad_entrega = !empty($_POST['ciudad_entrega']) ? trim($_POST['ciudad_entrega']) : 'Sogamoso';
        $observaciones_entrega = !empty($_POST['observaciones_entrega']) ? trim($_POST['observaciones_entrega']) : null;
    }

    // 7. MÉTODO DE PAGO
    $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';
    $tipo_transferencia = null;
    
    if ($metodo_pago === 'Transferencia') {
        $tipo_transferencia = $_POST['tipo_transferencia_final'] ?? ($_POST['tipo_transferencia'] ?? 'Nequi');
        $tipo_transferencia = !empty($tipo_transferencia) ? trim($tipo_transferencia) : 'Nequi';
    }
    
    $paga_con = !empty($_POST['paga_con']) ? floatval($_POST['paga_con']) : 0;
    $cambio = 0.00;
    
    if ($metodo_pago === 'Efectivo' && $paga_con >= $total_venta) {
        $cambio = $paga_con - $total_venta;
    }

    // 8. VALIDAR STOCK
    foreach ($productos_carrito as $item) {
        $producto_id = intval($item['producto_id'] ?? $item['id'] ?? 0);
        
        if ($producto_id <= 0) {
            throw new Exception("ID de producto inválido en el carrito.");
        }
        
        $stmtStockCheck = $pdo->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmtStockCheck->execute([$producto_id]);
        $stockProducto = $stmtStockCheck->fetchColumn();
        
        if (intval($stockProducto) < intval($item['cantidad'] ?? 0)) {
            throw new Exception("Stock insuficiente para el producto ID: {$producto_id}");
        }
    }

    // 9. LÓGICA DE ABONO Y ESTADO
    $abono = 0;
    $saldo_pendiente = 0;
    $estado_venta = 'Entregado';
    
    // ✅ CORRECCIÓN: Siempre usar la fecha del formulario
    $fecha_entrega = !empty($_POST['fecha_entrega']) ? $_POST['fecha_entrega'] : date('Y-m-d', strtotime('+7 days'));
    
    $observaciones_venta_mayor = !empty($_POST['observaciones_venta_mayor']) 
        ? trim($_POST['observaciones_venta_mayor']) 
        : (!empty($_POST['observaciones_pedido']) ? trim($_POST['observaciones_pedido']) : null);
    
    if ($es_mayorista) {
        $abono = floatval($_POST['abono'] ?? 0);
        
        // ✅ CORRECCIÓN: Validar abono mínimo del 50%
        $minimo_abono = $total_venta * 0.50;
        
        if ($abono < $minimo_abono) {
            $pdo->rollBack();
            header("Location: ../views/venta_mayorista.php?error=abono_minimo_50");
            exit();
        }
        
        $saldo_pendiente = $total_venta - $abono;
        
        // ✅ CORRECCIÓN CRÍTICA: Si hay saldo pendiente, SIEMPRE es 'Pendiente'
        if ($saldo_pendiente > 0) {
            $estado_venta = 'Pendiente';
        } else {
            $estado_venta = 'Entregado';
        }
        
    } else {
        // Venta directa: siempre entregado
        $estado_venta = 'Entregado';
        $abono = $total_venta;
        $saldo_pendiente = 0;
    }

    // 10. GENERAR TICKET
    $vendedor_id = $_SESSION['user_id'] ?? ($_SESSION['usuario_id'] ?? 1);
    $ticket_numero = ($es_mayorista ? 'M-' : 'T-') . date('YmdHis') . '-' . rand(100, 999);

    // 11. INSERTAR VENTA
    $sqlVenta = "INSERT INTO ventas (
        ticket_numero, cliente_id, vendedor_id, total_venta, descuento_monto, tipo_venta,
        metodo_pago, tipo_entrega, costo_envio,
        direccion_entrega, barrio_entrega, ciudad_entrega, observaciones_entrega,
        cambio, tipo_transferencia, abono, saldo_pendiente, estado, observaciones_venta_mayor,
        fecha_entrega, fecha_venta
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmtVenta = $pdo->prepare($sqlVenta);
    $stmtVenta->execute([
        $ticket_numero,
        $cliente_id,
        $vendedor_id,
        $total_venta,
        $descuento_monto,
        $tipo_venta,
        $metodo_pago,
        $tipo_entrega,
        $costo_envio,
        $direccion_entrega,
        $barrio_entrega,
        $ciudad_entrega,
        $observaciones_entrega,
        $cambio,
        $tipo_transferencia,
        $abono,
        $saldo_pendiente,
        $estado_venta,
        $observaciones_venta_mayor,
        $fecha_entrega
    ]);
    
    // ✅ AHORA SÍ EXISTE $venta_id
    $venta_id = $pdo->lastInsertId();

    // ✅ CORRECCIÓN: Insertar en pagos_venta DESPUÉS de obtener $venta_id
    if ($es_mayorista && $abono > 0) {
        $stmtPagoVenta = $pdo->prepare("
            INSERT INTO pagos_venta (venta_id, monto, metodo_pago, fecha) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmtPagoVenta->execute([
            $venta_id,  // ✅ AHORA SÍ EXISTE
            $abono,
            $metodo_pago
        ]);
    }

    // 11.1 ACTUALIZAR CÓDIGO DESCRIPTIVO
    $codigo_descriptivo = 'VEN-' . str_pad($venta_id, 6, '0', STR_PAD_LEFT);
    $stmtUpdateCodigo = $pdo->prepare("UPDATE ventas SET codigo_descriptivo = ? WHERE id = ?");
    $stmtUpdateCodigo->execute([$codigo_descriptivo, $venta_id]);

    // 12. INSERTAR DETALLES
    $sqlDetalle = "INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal, color, talla, comentario_vendedor) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtDetalle = $pdo->prepare($sqlDetalle);
    
    foreach ($productos_carrito as $item) {
        $producto_id = intval($item['producto_id'] ?? $item['id'] ?? 0);
        $cantidad = intval($item['cantidad'] ?? 0);
        $precio_u = floatval($item['precio_unitario'] ?? $item['precio'] ?? 0);
        
        $subtotal_item = $precio_u * $cantidad;
        
        $color = !empty($item['color']) ? trim($item['color']) : null;
        $talla = !empty($item['talla']) ? trim($item['talla']) : null;
        
        $comentario = !empty($item['comentario_vendedor']) 
            ? trim($item['comentario_vendedor']) 
            : (!empty($item['comentario']) ? trim($item['comentario']) : null);
        
        $stmtDetalle->execute([
            $venta_id,
            $producto_id,
            $cantidad,
            $precio_u,
            $subtotal_item,
            $color,
            $talla,
            $comentario
        ]);
        
        // ✅ DESCONTAR STOCK
        $stmtUpdateStock = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
        $stmtUpdateStock->execute([$cantidad, $producto_id]);
    }

    // 13. CONFIRMAR Y REDIRIGIR
    $pdo->commit();
    
    if ($es_mayorista) {
        header("Location: ../views/ticket_mayorista.php?id=" . $venta_id);
    } else {
        header("Location: ../views/ticket_actual.php?id=" . $venta_id);
    }
    exit();
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $error_msg = urlencode($e->getMessage());
    $redirect_url = $es_mayorista ? '../views/venta_mayorista.php' : '../views/nueva_venta.php';
    header("Location: " . $redirect_url . "?error=" . $error_msg);
    exit();
}