<?php
// controllers/procesar_pedido.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Intentar usar la conexión del archivo del sistema
        @include_once '../config/connection.php'; 
        
        $db = null;
        if (isset($conn) && $conn instanceof mysqli) { $db = $conn; }
        elseif (isset($conexion) && $conexion instanceof mysqli) { $db = $conexion; }
        
        // 2. PLAN DE RESPALDO: Si no se detectó la variable, nos conectamos directamente aquí
        if (!$db) {
            // Ajusta los datos si usas una contraseña o puerto diferente en tu XAMPP
            $host = "localhost";
            $user = "root";
            $password = ""; 
            $database = "unideportes"; 
            
            $db = mysqli_connect($host, $user, $password, $database);
            
            if (!$db) {
                throw new Exception("Error de conexión directa a la base de datos: " . mysqli_connect_error());
            }
            mysqli_set_charset($db, "utf8mb4");
        }

        // Desactivar el autocommit para iniciar una transacción segura
        mysqli_begin_transaction($db);

        // 3. Recibir datos generales del formulario
        $cliente_id = !empty($_POST['cliente_id']) ? intval($_POST['cliente_id']) : null;
        $vendedor_id = !empty($_POST['vendedor_id']) ? intval($_POST['vendedor_id']) : null; 
        $total_pedido = !empty($_POST['total_pedido']) ? floatval($_POST['total_pedido']) : 0;
        $abono = !empty($_POST['abono']) ? floatval($_POST['abono']) : 0;
        $saldo_pendiente = max(0, $total_pedido - $abono);
        
        // Datos de entrega
        $tipo_entrega = $_POST['tipo_entrega'] ?? 'Tienda';
        $direccion_entrega = !empty($_POST['direccion_entrega']) ? $_POST['direccion_entrega'] : null;
        $barrio_entrega = !empty($_POST['barrio_entrega']) ? $_POST['barrio_entrega'] : null;
        $ciudad_entrega = !empty($_POST['ciudad_entrega']) ? $_POST['ciudad_entrega'] : null;
        $observaciones_entrega = !empty($_POST['observaciones_entrega']) ? $_POST['observaciones_entrega'] : null;

        // Fecha de entrega estimada (15 días por defecto)
        $fecha_entrega = $_POST['fecha_entrega'] ?? date('Y-m-d', strtotime('+15 days'));
        if (empty($fecha_entrega)) { $fecha_entrega = date('Y-m-d', strtotime('+15 days')); }
        $detalle_texto = 'Pedido de confección mayorista';

        // 4. Insertar el encabezado en la tabla 'pedidos'
        $sqlPedido = "INSERT INTO pedidos (
            cliente_id, total_pedido, estado, fecha_entrega, vendedor_id, abono, saldo_pendiente,
            tipo_entrega, direccion_entrega, barrio_entrega, ciudad_entrega, observaciones_entrega, detalle
        ) VALUES (?, ?, 'En Corte', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($db, $sqlPedido);
        if (!$stmt) {
            throw new Exception("Error preparando la consulta de pedidos: " . mysqli_error($db));
        }

        mysqli_stmt_bind_param(
            $stmt, 
            "idssisssssss", 
            $cliente_id, $total_pedido, $fecha_entrega, $vendedor_id, $abono, $saldo_pendiente,
            $tipo_entrega, $direccion_entrega, $barrio_entrega, $ciudad_entrega, $observaciones_entrega, $detalle_texto
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error al insertar el pedido: " . mysqli_stmt_error($stmt));
        }

        // Obtener el ID de la orden generada
        $pedido_id = mysqli_insert_id($db); 
        mysqli_stmt_close($stmt);

        // 5. Decodificar los productos del carrito que vienen desde JavaScript
        $venta_json = $_POST['venta_json'] ?? '[]';
        $items = json_decode($venta_json, true);

        if (!empty($items) && is_array($items)) {
            $sqlDetalle = "INSERT INTO detalle_pedido (
                pedido_id, producto_id, cantidad, precio_unitario, color, talla, comentario_vendedor
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmtDetalle = mysqli_prepare($db, $sqlDetalle);
            if (!$stmtDetalle) {
                throw new Exception("Error preparando la consulta de detalle: " . mysqli_error($db));
            }

            foreach ($items as $item) {
                $p_id = intval($item['producto_id']);
                $cant = intval($item['cantidad']);
                $precio = floatval($item['precio_unitario']);
                $col = !empty($item['color']) ? $item['color'] : 'Por definir';
                $tal = !empty($item['talla']) ? $item['talla'] : 'Estándar';
                $comen = !empty($item['comentario_vendedor']) ? $item['comentario_vendedor'] : '';

                mysqli_stmt_bind_param($stmtDetalle, "iiidsss", $pedido_id, $p_id, $cant, $precio, $col, $tal, $comen);
                
                if (!mysqli_stmt_execute($stmtDetalle)) {
                    throw new Exception("Error al insertar detalle del producto: " . mysqli_stmt_error($stmtDetalle));
                }
            }
            mysqli_stmt_close($stmtDetalle);
        }

        // Si todo salió bien, guardamos definitivamente en la base de datos
        mysqli_commit($db);
        
        // Cerramos la conexión limpia
        mysqli_close($db);
        
        // Redireccionar con éxito al panel de producción
        header("Location: ../views/pedido_exitoso.php?id=" . $pedido_id);
        exit();

    } catch (Exception $e) {
        if (isset($db) && $db) {
            mysqli_rollback($db);
            mysqli_close($db);
        }
        // Regresa mostrando el mensaje de error real en pantalla
        header("Location: ../views/linea_confeccion.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../views/linea_confeccion.php");
    exit();
}