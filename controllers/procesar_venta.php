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
// Generar número de factura secuencial FAC-XXXXXX
$stmtUltimaFactura = $pdo->query("SELECT ticket_numero FROM ventas WHERE ticket_numero LIKE 'FAC-%' ORDER BY id DESC LIMIT 1");
$ultimaFactura = $stmtUltimaFactura->fetchColumn();

if ($ultimaFactura) {
    // Extraer el número después de 'FAC-'
    $ultimoNumero = intval(substr($ultimaFactura, 4));
    $siguienteNumero = $ultimoNumero + 1;
} else {
    $siguienteNumero = 1;
}

$ticket_numero = 'FAC-' . str_pad($siguienteNumero, 6, '0', STR_PAD_LEFT);
$codigo_descriptivo_venta = 'V-' . str_pad($siguienteNumero, 6, '0', STR_PAD_LEFT);

        // Detectar venta mayorista
        $es_mayorista = ($_POST['venta_tipo'] ?? '') === 'mayorista';

        // Calcular descuento mayorista
        $json_raw = $_POST['venta_json'] ?? ($_POST['ventaJSON'] ?? null);
        $productos_carrito = json_decode($json_raw, true);
        $total_unidades = 0;
        $subtotal_productos = 0;

        if (is_array($productos_carrito)) {
            foreach ($productos_carrito as $item) {
                $total_unidades += intval($item['cantidad']);
                $subtotal_productos += floatval($item['precio']) * intval($item['cantidad']);
            }
        }

        $factor_descuento = 0;
        if ($total_unidades >= 20) $factor_descuento = 0.10;
        elseif ($total_unidades >= 10) $factor_descuento = 0.05;

        $descuento_monto = $es_mayorista ? ($subtotal_productos * $factor_descuento) : 0;

        // Estado de la venta (Como es de contado: Tienda = Entregado, Domicilio = En Camino)
        $estado_venta = ($tipo_entrega === 'Domicilio') ? 'En Camino' : 'Entregado';

        $observaciones_venta_mayor = !empty($_POST['observaciones_pedido']) ? trim($_POST['observaciones_pedido']) : null;
        $fecha_entrega = null;

        // 3. REGISTRAR VENTA (22 columnas exactas, SIN abono ni saldo_pendiente)
        $sqlVenta = "INSERT INTO ventas (
            codigo_descriptivo, ticket_numero, cliente_id, vendedor_id, total_venta,
            descuento_monto, tipo_venta, metodo_pago, tipo_entrega, costo_envio,
            direccion_entrega, barrio_entrega, ciudad_entrega, observaciones_entrega,
            observaciones_venta_mayor, cambio, tipo_transferencia, referencia_pago, 
            ultimos_4_digitos, banco_emisor, estado, fecha_entrega
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtVenta = $pdo->prepare($sqlVenta);
        $stmtVenta->execute([
            $codigo_descriptivo_venta,           // 1
            $ticket_numero,                      // 2
            $cliente_id,                         // 3
            $vendedor_id,                        // 4
            $total_final,                        // 5
            $descuento_monto,                    // 6
            $es_mayorista ? 'mayorista' : 'directa', // 7
            $metodo_pago,                        // 8
            $tipo_entrega,                       // 9
            $costo_envio,                        // 10
            $direccion_entrega,                  // 11
            $barrio_entrega,                     // 12
            $ciudad_entrega,                     // 13
            $observaciones_entrega,              // 14
            $observaciones_venta_mayor,          // 15
            $cambio,                             // 16
            $tipo_transferencia,                 // 17
            !empty($_POST['referencia_pago']) ? trim($_POST['referencia_pago']) : null, // 18
            !empty($_POST['ultimos_4_digitos']) ? substr(trim($_POST['ultimos_4_digitos']), -4) : null, // 19
            !empty($_POST['banco_emisor']) ? trim($_POST['banco_emisor']) : null, // 20
            $estado_venta,                       // 21
            $fecha_entrega                       // 22
        ]);

        $venta_id = $pdo->lastInsertId();

        // 4. PROCESAR DETALLES Y STOCK
        if (empty($json_raw)) throw new Exception("El carrito de compras está vacío.");
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
        }

        $pdo->commit();

        // Redirigir al ticket correcto
        if ($es_mayorista) {
            header("Location: ../views/ticket_mayorista.php?id=" . $venta_id . "&success=venta_registrada");
        } else {
            header("Location: ../views/ticket_actual.php?id=" . $venta_id . "&success=venta_registrada");
        }
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        try {
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            @file_put_contents($logDir . '/procesar_venta_errors.log', json_encode(['time' => date('c'), 'message' => $e->getMessage(), 'post' => array_filter($_POST)]) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Exception $ignore) {}

        // Redirección inteligente según el origen del formulario
        $origen = ($_POST['venta_tipo'] ?? '') === 'mayorista' ? 'venta_mayorista.php' : 'nueva_venta.php';
        header("Location: ../views/" . $origen . "?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../views/nueva_venta.php");
    exit();
}