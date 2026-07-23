<?php
// controllers/procesar_venta.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

$pdo = app();
require_login(['vendedor', 'colaborador', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $cliente_id = !empty($_POST['cliente_id']) ? intval($_POST['cliente_id']) : null;
        $tipo_entrega = $_POST['tipo_entrega'] ?? 'Tienda';
        
        if (empty($cliente_id) && empty($_POST['nuevo_cliente_nombre_completo'])) {
            $cliente_id = 1;
        }

        // 1. GESTIÓN DE CLIENTE
        if (empty($cliente_id) && !empty($_POST['nuevo_cliente_nombre_completo'])) {
            $nit_cedula = trim($_POST['nuevo_cliente_nit_cedula']);
            $stmtCheck = $pdo->prepare("SELECT id FROM clientes WHERE nit_cedula = ?");
            $stmtCheck->execute([$nit_cedula]);
            $clienteExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($clienteExistente) {
                $cliente_id = $clienteExistente['id'];
            } else {
                $codigo_descriptivo = generarCodigoDescriptivoCliente();
                $sqlCli = "INSERT INTO clientes (codigo_descriptivo, nombre_completo, nit_cedula, telefono, email, tipo_cliente, direccion, barrio, ciudad, referencia_entrega, estado) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtCli = $pdo->prepare($sqlCli);
                $stmtCli->execute([
                    $codigo_descriptivo,
                    trim($_POST['nuevo_cliente_nombre_completo']),
                    $nit_cedula,
                    !empty($_POST['nuevo_cliente_telefono']) ? trim($_POST['nuevo_cliente_telefono']) : null,
                    !empty($_POST['nuevo_cliente_email']) ? trim($_POST['nuevo_cliente_email']) : null,
                    $_POST['nuevo_cliente_tipo_cliente'] ?? 'Individual',
                    !empty($_POST['nuevo_cliente_direccion']) ? trim($_POST['nuevo_cliente_direccion']) : null,
                    !empty($_POST['nuevo_cliente_barrio']) ? trim($_POST['nuevo_cliente_barrio']) : null,
                    !empty($_POST['nuevo_cliente_ciudad']) ? trim($_POST['nuevo_cliente_ciudad']) : 'Sogamoso',
                    !empty($_POST['nuevo_cliente_referencia_entrega']) ? trim($_POST['nuevo_cliente_referencia_entrega']) : null,
                    'activo'
                ]);
                $cliente_id = $pdo->lastInsertId();
            }
        }

        // 2. CÁLCULO DE TOTALES
        $total_final = floatval($_POST['total_venta'] ?? 0);
        $costo_envio = 0.00;
        $direccion_entrega = null; $barrio_entrega = null; $ciudad_entrega = null; $observaciones_entrega = null;

        if ($tipo_entrega === 'Domicilio') {
            $costo_envio = 5000.00;
            $total_final += $costo_envio;
            $direccion_entrega = !empty($_POST['direccion_entrega']) ? trim($_POST['direccion_entrega']) : null;
            $barrio_entrega = !empty($_POST['barrio_entrega']) ? trim($_POST['barrio_entrega']) : null;
            $ciudad_entrega = !empty($_POST['ciudad_entrega']) ? trim($_POST['ciudad_entrega']) : 'Sogamoso';
            $observaciones_entrega = !empty($_POST['observaciones_entrega']) ? trim($_POST['observaciones_entrega']) : null;
        }

        $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';
        $tipo_transferencia = null;
        if ($metodo_pago === 'Transferencia') {
            $tipo_transferencia = $_POST['tipo_transferencia_final'] ?? ($_POST['tipo_transferencia'] ?? 'Nequi');
            $tipo_transferencia = !empty($tipo_transferencia) ? trim($tipo_transferencia) : 'Nequi';
        }
        
        $paga_con = !empty($_POST['paga_con']) ? floatval($_POST['paga_con']) : 0;
        $cambio = 0.00;
        if ($metodo_pago === 'Efectivo' && $paga_con >= $total_final) {
            $cambio = $paga_con - $total_final;
        }

        $vendedor_id = $_SESSION['user_id'] ?? ($_SESSION['usuario_id'] ?? 1);
        $ticket_numero = 'T-' . date('ymd') . '-' . rand(10, 99);
        $codigo_descriptivo_venta = 'V-' . rand(1000, 9999);

        // Detectar venta mayorista
        $es_mayorista = ($_POST['venta_tipo'] ?? '') === 'mayorista';
        $abono = $es_mayorista ? floatval($_POST['abono'] ?? 0) : 0;
        $saldo_pendiente = $es_mayorista ? ($total_final - $abono) : 0;
        
        // Estado de la venta
        if ($tipo_entrega === 'Domicilio') {
            $estado_venta = 'En Camino';
        } else {
            $estado_venta = ($saldo_pendiente > 0) ? 'Pendiente' : 'Entregado';
        }

        $observaciones_pedido = !empty($_POST['observaciones_pedido']) ? trim($_POST['observaciones_pedido']) : null;
        $fecha_entrega = ($es_mayorista && $abono > 0) ? date('Y-m-d', strtotime('+15 days')) : null;

        // 3. REGISTRAR VENTA (Con todos los campos de pago y estado)
        $sqlVenta = "INSERT INTO ventas (
                codigo_descriptivo, ticket_numero, cliente_id, vendedor_id, total_venta,
                metodo_pago, tipo_entrega, costo_envio,
                direccion_entrega, barrio_entrega, ciudad_entrega, observaciones_entrega,
                cambio, tipo_transferencia, referencia_pago, ultimos_4_digitos, banco_emisor,
                estado, saldo_pendiente, fecha_venta, fecha_entrega
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        $stmtVenta = $pdo->prepare($sqlVenta);
        $stmtVenta->execute([
            $codigo_descriptivo_venta, $ticket_numero, $cliente_id, $vendedor_id, $total_final,
            $metodo_pago, $tipo_entrega, $costo_envio, $direccion_entrega, $barrio_entrega,
            $ciudad_entrega, $observaciones_entrega, $cambio, $tipo_transferencia,
            !empty($_POST['referencia_pago']) ? trim($_POST['referencia_pago']) : null,
            !empty($_POST['ultimos_4_digitos']) ? substr(trim($_POST['ultimos_4_digitos']), -4) : null,
            !empty($_POST['banco_emisor']) ? trim($_POST['banco_emisor']) : null,
            $estado_venta, $saldo_pendiente, $fecha_entrega
        ]);

        $venta_id = $pdo->lastInsertId();

        // 4. PROCESAR DETALLES Y STOCK
        $json_raw = $_POST['venta_json'] ?? ($_POST['ventaJSON'] ?? null);
        if (empty($json_raw)) throw new Exception("El carrito de compras está vacío.");

        $productos_carrito = json_decode($json_raw, true);
        if (!is_array($productos_carrito) || count($productos_carrito) === 0) throw new Exception("Formato de carrito inválido.");

        $sqlDetalle = "INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal, color, talla, comentario_vendedor) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtDetalle = $pdo->prepare($sqlDetalle);
        $stmtSelectStock = $pdo->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");

        foreach ($productos_carrito as $item) {
            $producto_id = intval($item['id']);
            $cantidad = intval($item['cantidad']);
            $precio_u = floatval($item['precio']);
            $subtotal = $cantidad * $precio_u;
            $color = !empty($item['color']) ? trim($item['color']) : null;
            $talla = !empty($item['talla']) ? trim($item['talla']) : null;
            $comentario = !empty($item['comentario']) ? trim($item['comentario']) : null;

            $stmtSelectStock->execute([$producto_id]);
            $currentStock = $stmtSelectStock->fetchColumn();
            $currentStock = $currentStock !== false ? intval($currentStock) : null;

            if ($currentStock === null) throw new Exception("Producto no encontrado (ID: {$producto_id}).");
            if ($currentStock < $cantidad) throw new Exception("Stock insuficiente para el producto ID: {$producto_id}.");

            $stmtDetalle->execute([$venta_id, $producto_id, $cantidad, $precio_u, $subtotal, $color, $talla, $comentario]);

            // ✅ AQUÍ ESTÁ: Lógica de Venta Mayorista PRESERVADA
            if ($es_mayorista && $abono > 0) {
                $sqlPedido = "INSERT INTO pedidos (cliente_id, vendedor_id, detalle, descripcion, cantidad, total_pedido, abono, saldo_pendiente, estado, fecha_entrega) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'En Corte', ?)";
                $stmtPedido = $pdo->prepare($sqlPedido);
                $stmtPedido->execute([
                    $cliente_id, $vendedor_id, "Venta mayorista - " . date('Y-m-d H:i'),
                    $observaciones_pedido, $cantidad, $subtotal, $abono, $saldo_pendiente, $fecha_entrega
                ]);
                $pedido_id = $pdo->lastInsertId();

                $sqlDetallePedido = "INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario, color, talla, comentario_vendedor) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmtDetallePedido = $pdo->prepare($sqlDetallePedido);
                $stmtDetallePedido->execute([$pedido_id, $producto_id, $cantidad, $precio_u, $color, $talla, $comentario]);
            }
        }

        $pdo->commit();
        header("Location: ../views/ticket_actual.php?id=" . $venta_id . "&success=venta_registrada");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        try {
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            @file_put_contents($logDir . '/procesar_venta_errors.log', json_encode(['time' => date('c'), 'message' => $e->getMessage(), 'post' => array_filter($_POST)]) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Exception $ignore) {}
        
        header("Location: ../views/nueva_venta.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../views/nueva_venta.php");
    exit();
}