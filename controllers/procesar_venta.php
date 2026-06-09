<?php
// controllers/procesar_venta.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

$pdo = app();

// Asegurar que el usuario tiene permisos mediante la función global optimizada
require_login(['vendedor', 'colaborador', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Iniciamos una transacción para asegurar consistencia total (Todo o Nada)
        $pdo->beginTransaction();

        $cliente_id = !empty($_POST['cliente_id']) ? intval($_POST['cliente_id']) : null;
        $tipo_entrega = $_POST['tipo_entrega'] ?? 'Tienda';
        
        // Si no se seleccionó cliente y tampoco se creó uno nuevo, asignamos Cliente General (ID 1)
        if (empty($cliente_id) && empty($_POST['nuevo_cliente_nombre_completo'])) {
            $cliente_id = 1; 
        }

        // 1. GESTIÓN DE CLIENTE (NUEVO O EXISTENTE)
        if (empty($cliente_id) && !empty($_POST['nuevo_cliente_nombre_completo'])) {
            $nit_cedula = trim($_POST['nuevo_cliente_nit_cedula']);
            
            // Verificar si la cédula/NIT ya existe para reutilizar el ID y no duplicar registros
            $stmtCheck = $pdo->prepare("SELECT id FROM clientes WHERE nit_cedula = ?");
            $stmtCheck->execute([$nit_cedula]);
            $clienteExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($clienteExistente) {
                $cliente_id = $clienteExistente['id'];
            } else {
                $sqlCli = "INSERT INTO clientes (nombre_completo, nit_cedula, telefono, email, tipo_cliente) 
                           VALUES (?, ?, ?, ?, ?)";
                $stmtCli = $pdo->prepare($sqlCli);
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

        // 2. CÁLCULO DE TOTALES, ENVÍO Y CAMBIO
        $total_final = floatval($_POST['total_venta'] ?? 0);
        $costo_envio = 0.00;

        // Inicializamos las variables de envío en NULL (por si es retiro en Tienda)
        $direccion_entrega     = null;
        $barrio_entrega        = null;
        $ciudad_entrega        = null;
        $observaciones_entrega = null;

        if ($tipo_entrega === 'Domicilio') {
            $costo_envio = 5000.00; // Recargo estándar de envío en Sogamoso
            $total_final += $costo_envio; 

            // Capturamos los campos específicos enviados desde el formulario
            $direccion_entrega     = !empty($_POST['direccion_entrega']) ? trim($_POST['direccion_entrega']) : null;
            $barrio_entrega        = !empty($_POST['barrio_entrega']) ? trim($_POST['barrio_entrega']) : null;
            $ciudad_entrega        = !empty($_POST['ciudad_entrega']) ? trim($_POST['ciudad_entrega']) : 'Sogamoso';
            $observaciones_entrega = !empty($_POST['observaciones_entrega']) ? trim($_POST['observaciones_entrega']) : null;
        }

        $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';
        
        // CORREGIDO: Soporta el name 'tipo_transferencia_final' del JS dinámico o el select básico
        $tipo_transferencia = null;
        if ($metodo_pago === 'Transferencia') {
            $tipo_transferencia = $_POST['tipo_transferencia_final'] ?? ($_POST['tipo_transferencia'] ?? 'Nequi');
            $tipo_transferencia = !empty($tipo_transferencia) ? trim($tipo_transferencia) : 'Nequi';
        }
        
        // Cálculo del cambio en efectivo
        $paga_con = !empty($_POST['paga_con']) ? floatval($_POST['paga_con']) : 0;
        $cambio = 0.00;
        if ($metodo_pago === 'Efectivo' && $paga_con >= $total_final) {
            $cambio = $paga_con - $total_final;
        }

        // Recuperar el ID del vendedor activo en la sesión (Obligatorio NOT NULL)
        $vendedor_id = $_SESSION['user_id'] ?? ($_SESSION['usuario_id'] ?? 1); 

        // Generar un número de ticket único bajo el patrón T-YYYYMMDDHHMMSS-RAND
        $ticket_numero = 'T-' . date('YmdHis') . '-' . rand(100, 999);

        // Detectar si es venta mayorista
        $es_mayorista = ($_POST['venta_tipo'] ?? '') === 'mayorista';
        $abono = $es_mayorista ? floatval($_POST['abono'] ?? 0) : 0;
        $saldo_pendiente = $es_mayorista ? ($total_final - $abono) : 0;
        // Guardar observaciones para AMBAS ventas (mayorista y regular)
        $observaciones_pedido = !empty($_POST['observaciones_pedido']) ? trim($_POST['observaciones_pedido']) : null;

        // Si es mayorista con abono, calcular fecha de entrega (15 días)
        $fecha_entrega = null;
        if ($es_mayorista && $abono > 0) {
            $fecha_entrega = date('Y-m-d', strtotime('+15 days'));
        }

        // 3. REGISTRAR EN LA TABLA VENTAS
        $sqlVenta = "INSERT INTO ventas (
                        ticket_numero, cliente_id, vendedor_id, total_venta, 
                        metodo_pago, tipo_entrega, costo_envio, 
                        direccion_entrega, barrio_entrega, ciudad_entrega, observaciones_entrega, 
                        cambio, tipo_transferencia, fecha_venta
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmtVenta = $pdo->prepare($sqlVenta);
        $stmtVenta->execute([
            $ticket_numero,
            $cliente_id,
            $vendedor_id,
            $total_final,
            $metodo_pago,
            $tipo_entrega,
            $costo_envio,
            $direccion_entrega,
            $barrio_entrega,
            $ciudad_entrega,
            $observaciones_entrega,
            $cambio,
            $tipo_transferencia
        ]);
        
        $venta_id = $pdo->lastInsertId();

        // 4. PROCESAR DETALLES DEL CARRITO Y STOCK
        // CORREGIDO: Mapea tanto 'venta_json' como 'ventaJSON' provenientes de la sincronización JS
        $json_raw = $_POST['venta_json'] ?? ($_POST['ventaJSON'] ?? null);

        if (empty($json_raw)) {
            throw new Exception("El carrito de compras está vacío.");
        }

        $productos_carrito = json_decode($json_raw, true);

        if (!is_array($productos_carrito) || count($productos_carrito) === 0) {
            throw new Exception("El formato del carrito de compras es inválido o no se pudo procesar.");
        }

        $sqlDetalle = "INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal, color, talla, comentario_vendedor) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtDetalle = $pdo->prepare($sqlDetalle);

        $sqlRestarStock = "UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?";
        $stmtStock = $pdo->prepare($sqlRestarStock);

        foreach ($productos_carrito as $item) {
            $producto_id = intval($item['id']); 
            $cantidad    = intval($item['cantidad']);
            $precio_u    = floatval($item['precio']); 
            $subtotal    = $cantidad * $precio_u;
            $color       = !empty($item['color']) ? trim($item['color']) : null;
            $talla       = !empty($item['talla']) ? trim($item['talla']) : null;
            $comentario  = !empty($item['comentario']) ? trim($item['comentario']) : null;

            // Registrar el ítem vendido en la tabla intermedia
            $stmtDetalle->execute([
                $venta_id,
                $producto_id,
                $cantidad,
                $precio_u,
                $subtotal,
                $color,
                $talla,
                $comentario
            ]);

            // Descontar del inventario físico de la fábrica validando stock concurrente
            $stmtStock->execute([$cantidad, $producto_id, $cantidad]);
            
            if ($stmtStock->rowCount() === 0) {
                throw new Exception("Stock insuficiente para uno de los productos seleccionados. La operación fue cancelada.");
            }

            // Si es venta mayorista con abono, crear pedido y detalle_pedido
            if ($es_mayorista && $abono > 0) {
                // Crear el pedido
                $sqlPedido = "INSERT INTO pedidos (cliente_id, vendedor_id, detalle, descripcion, cantidad, total_pedido, abono, saldo_pendiente, estado, fecha_entrega) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'En Corte', ?)";
                $stmtPedido = $pdo->prepare($sqlPedido);
                $stmtPedido->execute([
                    $cliente_id,
                    $vendedor_id,
                    "Venta mayorista - " . date('Y-m-d H:i'),
                    $observaciones_pedido,
                    $cantidad,
                    $subtotal,
                    $abono,
                    $saldo_pendiente,
                    $fecha_entrega
                ]);
                $pedido_id = $pdo->lastInsertId();

                // Registrar en detalle_pedido con color y talla
                $sqlDetallePedido = "INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario, color, talla, comentario_vendedor) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmtDetallePedido = $pdo->prepare($sqlDetallePedido);
                $stmtDetallePedido->execute([
                    $pedido_id,
                    $producto_id,
                    $cantidad,
                    $precio_u,
                    $color,
                    $talla,
                    $comentario
                ]);
            }
        }

        // Si todo el proceso se ejecutó correctamente, consolidamos de forma atómica en la BD
        $pdo->commit();
        
        // Redirección inmediata a la vista del Ticket de Venta
        header("Location: ../views/ticket_actual.php?id=" . $venta_id);
        exit();

    } catch (Exception $e) {
        // Cancelamos cualquier inserción parcial si algo falla en el ciclo protegiendo la integridad
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: ../views/nueva_venta.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../views/nueva_venta.php");
    exit();
}