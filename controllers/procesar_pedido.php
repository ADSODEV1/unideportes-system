<?php
// controllers/procesar_pedido.php

require_once __DIR__ . '/../config/bootstrap.php';

function resolverProductoPedido(mysqli $db, int $producto_id, string $producto_nombre, string $color, string $talla, float $precio_unitario): int {
    if ($producto_id > 0) {
        $productoEncontrado = 0;
        $stmtExiste = mysqli_prepare($db, 'SELECT id FROM productos WHERE id = ? LIMIT 1');
        if ($stmtExiste) {
            mysqli_stmt_bind_param($stmtExiste, 'i', $producto_id);
            mysqli_stmt_execute($stmtExiste);
            mysqli_stmt_bind_result($stmtExiste, $productoEncontrado);
            if (mysqli_stmt_fetch($stmtExiste)) {
                mysqli_stmt_close($stmtExiste);
                return (int) $productoEncontrado;
            }
            mysqli_stmt_close($stmtExiste);
        }
    }

    $productoEncontrado = 0;
    $stmtBuscar = mysqli_prepare($db, 'SELECT id FROM productos WHERE nombre = ? LIMIT 1');
    if ($stmtBuscar) {
        mysqli_stmt_bind_param($stmtBuscar, 's', $producto_nombre);
        mysqli_stmt_execute($stmtBuscar);
        mysqli_stmt_bind_result($stmtBuscar, $productoEncontrado);
        if (mysqli_stmt_fetch($stmtBuscar)) {
            mysqli_stmt_close($stmtBuscar);
            return (int) $productoEncontrado;
        }
        mysqli_stmt_close($stmtBuscar);
    }

    $referencia = 'MAY-' . date('YmdHis') . '-' . random_int(1000, 9999);
    $categoria = 'Confeccion';
    $material = 'No definido';
    $genero = 'Unisex';
    // Los productos auxiliares creados desde pedidos mayoristas no pertenecen al catalogo activo.
    $estado = 'inactivo';
    $descripcion = 'Producto generado automaticamente desde pedido mayorista';
    $stock = 0;

    $stmtCrear = mysqli_prepare(
        $db,
        'INSERT INTO productos (nombre, referencia, categoria, color, material, genero, estado, descripcion, talla, stock, precio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$stmtCrear) {
        throw new Exception('No se pudo preparar el producto auxiliar: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param(
        $stmtCrear,
        'sssssssssid',
        $producto_nombre,
        $referencia,
        $categoria,
        $color,
        $material,
        $genero,
        $estado,
        $descripcion,
        $talla,
        $stock,
        $precio_unitario
    );

    if (!mysqli_stmt_execute($stmtCrear)) {
        throw new Exception('No se pudo crear el producto auxiliar: ' . mysqli_stmt_error($stmtCrear));
    }

    $nuevoProductoId = mysqli_insert_id($db);
    mysqli_stmt_close($stmtCrear);

    return (int) $nuevoProductoId;
}

function sincronizarAbonoEnPagos(mysqli $db, int $pedido_id, float $abono): void {
    if ($pedido_id <= 0 || $abono <= 0) {
        return;
    }

    $totalPagos = 0.0;
    $stmtPagos = mysqli_prepare($db, 'SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE id_pg_pedido = ?');
    if (!$stmtPagos) {
        throw new Exception('Error preparando suma de pagos: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmtPagos, 'i', $pedido_id);
    mysqli_stmt_execute($stmtPagos);
    mysqli_stmt_bind_result($stmtPagos, $totalPagos);
    mysqli_stmt_fetch($stmtPagos);
    mysqli_stmt_close($stmtPagos);

    $faltanteAbono = round($abono - floatval($totalPagos), 2);
    if ($faltanteAbono <= 0) {
        return;
    }

    $stmtInsertPago = mysqli_prepare($db, 'INSERT INTO pagos (id_pg_pedido, monto, fecha) VALUES (?, ?, NOW())');
    if (!$stmtInsertPago) {
        throw new Exception('Error preparando insercion de pago inicial: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmtInsertPago, 'id', $pedido_id, $faltanteAbono);
    if (!mysqli_stmt_execute($stmtInsertPago)) {
        throw new Exception('Error insertando pago inicial desde abono: ' . mysqli_stmt_error($stmtInsertPago));
    }
    mysqli_stmt_close($stmtInsertPago);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        require_login(['vendedor', 'colaborador', 'admin']);

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

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Desactivar el autocommit para iniciar una transacción segura
        mysqli_begin_transaction($db);

        // 3. Recibir datos generales del formulario
        $cliente_id_raw = $_POST['cliente_id'] ?? '';
        $cliente_id = null;
        $nuevo_cliente = false;

        if ($cliente_id_raw === 'NUEVO') {
            $nuevo_cliente = true;
        } elseif (!empty($cliente_id_raw) && ctype_digit(strval($cliente_id_raw))) {
            $cliente_id = intval($cliente_id_raw);
        }

        $vendedor_id = intval($_SESSION['user_id'] ?? $_SESSION['vendedor_id'] ?? 0);
        if ($vendedor_id <= 0) {
            throw new Exception("No se pudo determinar el vendedor activo. Inicie sesión nuevamente.");
        }

        $total_pedido = !empty($_POST['total_venta']) ? floatval($_POST['total_venta']) : 0;
        $abono = !empty($_POST['abono']) ? floatval($_POST['abono']) : 0;
        $saldo_pendiente = max(0, $total_pedido - $abono);
        
        // Datos de entrega
        $tipo_entrega = $_POST['tipo_entrega'] ?? 'Tienda';
        $direccion_entrega = !empty($_POST['direccion_entrega']) ? trim($_POST['direccion_entrega']) : null;
        $barrio_entrega = !empty($_POST['barrio_entrega']) ? trim($_POST['barrio_entrega']) : null;
        $ciudad_entrega = !empty($_POST['ciudad_entrega']) ? trim($_POST['ciudad_entrega']) : null;
        $observaciones_entrega = !empty($_POST['observaciones_entrega']) ? trim($_POST['observaciones_entrega']) : null;

        // Fecha de entrega estimada desde formulario o 15 días por defecto
        $fecha_entrega = !empty($_POST['fecha_entrega']) ? trim($_POST['fecha_entrega']) : date('Y-m-d', strtotime('+15 days'));
        if (empty($fecha_entrega)) {
            $fecha_entrega = date('Y-m-d', strtotime('+15 days'));
        }

        $detalle_texto = 'Pedido de confección mayorista';

        // 4. Decodificar primero los productos para consolidar el encabezado correctamente
        $venta_json = $_POST['venta_json'] ?? '[]';
        $items = json_decode($venta_json, true);

        if (!is_array($items) || empty($items)) {
            throw new Exception("El carrito de productos no puede estar vacío. Agregue al menos un producto para procesar el pedido.");
        }

        $cantidad_total = 0;
        $nombres_resumen = [];
        $lineas_descripcion = [];

        foreach ($items as $item) {
            $nombre_producto_item = trim($item['nombre'] ?? '');
            $cantidad_item = isset($item['cantidad']) ? intval($item['cantidad']) : 0;
            $precio_item = isset($item['precio_unitario']) ? floatval($item['precio_unitario']) : 0;
            $color_item = !empty($item['color']) ? trim($item['color']) : 'Por definir';
            $talla_item = !empty($item['talla']) ? trim($item['talla']) : 'Estándar';

            if ($nombre_producto_item === '' || $cantidad_item <= 0 || $precio_item <= 0) {
                throw new Exception("Uno de los productos del pedido es inválido. Revise su carrito.");
            }

            $cantidad_total += $cantidad_item;
            $nombres_resumen[] = $nombre_producto_item;
            $lineas_descripcion[] = $nombre_producto_item . ' x' . $cantidad_item . ' [' . $color_item . ' / ' . $talla_item . ']';
        }

        $nombres_resumen = array_values(array_unique($nombres_resumen));
        $detalle_texto = implode(', ', array_slice($nombres_resumen, 0, 3));
        if (count($nombres_resumen) > 3) {
            $detalle_texto .= ' +' . (count($nombres_resumen) - 3) . ' referencias';
        }

        $descripcion_texto = implode(' | ', $lineas_descripcion);
        if (!empty($_POST['observaciones_pedido'])) {
            $descripcion_texto .= ' | Obs: ' . trim($_POST['observaciones_pedido']);
        }

        // Validaciones básicas del pedido
        // Si se proporciona un pedido existente (creado previamente desde UI), permitimos que el total
        // pueda ser 0 en esta etapa porque los detalles ya pueden existir en la BD. Sin embargo,
        // al procesar definitivamente la venta (submit final), el cliente aún debe enviar un total > 0.
        $existing_pedido_id = isset($_POST['pedido_id']) && ctype_digit(strval($_POST['pedido_id'])) ? intval($_POST['pedido_id']) : 0;
        if ($existing_pedido_id <= 0 && $total_pedido <= 0) {
            throw new Exception("El total del pedido debe ser mayor a cero.");
        }
        if ($abono < 0) {
            throw new Exception("El abono no puede ser negativo.");
        }
        if ($tipo_entrega === 'Domicilio' && (empty($direccion_entrega) || empty($barrio_entrega) || empty($ciudad_entrega))) {
            throw new Exception("Para entrega a domicilio, dirección, barrio y ciudad son obligatorios.");
        }

        // Verificar que el vendedor exista en la base de datos
        $stmtV = mysqli_prepare($db, "SELECT id FROM usuarios WHERE id = ? LIMIT 1");
        if (!$stmtV) {
            throw new Exception("Error preparando validación de vendedor: " . mysqli_error($db));
        }
        mysqli_stmt_bind_param($stmtV, "i", $vendedor_id);
        mysqli_stmt_execute($stmtV);
        mysqli_stmt_store_result($stmtV);
        if (mysqli_stmt_num_rows($stmtV) === 0) {
            throw new Exception("El vendedor asignado no existe en el sistema.");
        }
        mysqli_stmt_close($stmtV);

        // Crear cliente nuevo si se solicitó
        if ($nuevo_cliente) {
            $nombre_nuevo = trim($_POST['nuevo_cliente_nombre_completo'] ?? '');
            $nit_nuevo = trim($_POST['nuevo_cliente_nit_cedula'] ?? '');
            $telefono_nuevo = trim($_POST['nuevo_cliente_telefono'] ?? '');
            $tipo_cliente_nuevo = trim($_POST['nuevo_cliente_tipo_cliente'] ?? 'Individual');

            if (empty($nombre_nuevo) || empty($nit_nuevo)) {
                throw new Exception("Debe completar el nombre y el NIT/Cédula del nuevo cliente.");
            }

            $stmtCliCheck = mysqli_prepare($db, "SELECT id FROM clientes WHERE nit_cedula = ? LIMIT 1");
            if (!$stmtCliCheck) {
                throw new Exception("Error preparando validación de cliente: " . mysqli_error($db));
            }
            mysqli_stmt_bind_param($stmtCliCheck, "s", $nit_nuevo);
            mysqli_stmt_execute($stmtCliCheck);
            mysqli_stmt_store_result($stmtCliCheck);
            if (mysqli_stmt_num_rows($stmtCliCheck) > 0) {
                mysqli_stmt_close($stmtCliCheck);
                throw new Exception("Ya existe un cliente registrado con ese NIT/Cédula.");
            }
            mysqli_stmt_close($stmtCliCheck);

            $stmtCreateClient = mysqli_prepare($db, "INSERT INTO clientes (nombre_completo, nit_cedula, telefono, tipo_cliente, estado) VALUES (?, ?, ?, ?, 'activo')");
            if (!$stmtCreateClient) {
                throw new Exception("Error preparando creación de cliente: " . mysqli_error($db));
            }
            mysqli_stmt_bind_param($stmtCreateClient, "ssss", $nombre_nuevo, $nit_nuevo, $telefono_nuevo, $tipo_cliente_nuevo);
            if (!mysqli_stmt_execute($stmtCreateClient)) {
                throw new Exception("Error al crear el cliente nuevo: " . mysqli_stmt_error($stmtCreateClient));
            }
            $cliente_id = mysqli_insert_id($db);
            mysqli_stmt_close($stmtCreateClient);
        }

        // Validar cliente existente
        if (empty($cliente_id) || $cliente_id <= 0) {
            throw new Exception("Debe seleccionar un cliente mayorista válido.");
        }

        $stmtC = mysqli_prepare($db, "SELECT id FROM clientes WHERE id = ? LIMIT 1");
        if (!$stmtC) {
            throw new Exception("Error preparando validación de cliente: " . mysqli_error($db));
        }
        mysqli_stmt_bind_param($stmtC, "i", $cliente_id);
        mysqli_stmt_execute($stmtC);
        mysqli_stmt_store_result($stmtC);
        if (mysqli_stmt_num_rows($stmtC) === 0) {
            throw new Exception("El cliente seleccionado no existe en la base de datos.");
        }
        mysqli_stmt_close($stmtC);

        // 5. Insertar o actualizar el encabezado en la tabla 'pedidos'
        $pedido_id = 0;
        if ($existing_pedido_id > 0) {
            // Validar que el pedido exista
            $check = mysqli_prepare($db, "SELECT id FROM pedidos WHERE id = ? LIMIT 1");
            if (!$check) throw new Exception("Error preparando validación de pedido existente: " . mysqli_error($db));
            mysqli_stmt_bind_param($check, 'i', $existing_pedido_id);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);
            if (mysqli_stmt_num_rows($check) === 0) {
                mysqli_stmt_close($check);
                throw new Exception("El pedido indicado no existe.");
            }
            mysqli_stmt_close($check);

            $sqlUpdatePedido = "UPDATE pedidos SET cliente_id = ?, total_pedido = ?, estado = 'En Corte', fecha_entrega = ?, vendedor_id = ?, abono = ?, saldo_pendiente = ?, tipo_entrega = ?, direccion_entrega = ?, barrio_entrega = ?, ciudad_entrega = ?, observaciones_entrega = ?, detalle = ?, descripcion = ?, cantidad = ? WHERE id = ?";
            $stmtUpdatePedido = mysqli_prepare($db, $sqlUpdatePedido);
            if (!$stmtUpdatePedido) {
                throw new Exception("Error preparando la actualización del pedido: " . mysqli_error($db));
            }

            mysqli_stmt_bind_param(
                $stmtUpdatePedido,
                "idsiddsssssssii",
                $cliente_id,
                $total_pedido,
                $fecha_entrega,
                $vendedor_id,
                $abono,
                $saldo_pendiente,
                $tipo_entrega,
                $direccion_entrega,
                $barrio_entrega,
                $ciudad_entrega,
                $observaciones_entrega,
                $detalle_texto,
                $descripcion_texto,
                $cantidad_total,
                $existing_pedido_id
            );

            if (!mysqli_stmt_execute($stmtUpdatePedido)) {
                throw new Exception("Error al actualizar el pedido: " . mysqli_stmt_error($stmtUpdatePedido));
            }

            mysqli_stmt_close($stmtUpdatePedido);
            $pedido_id = $existing_pedido_id;
        } else {
            $sqlPedido = "INSERT INTO pedidos (
                cliente_id, total_pedido, estado, fecha_entrega, vendedor_id, abono, saldo_pendiente,
                tipo_entrega, direccion_entrega, barrio_entrega, ciudad_entrega, observaciones_entrega,
                detalle, descripcion, cantidad
            ) VALUES (?, ?, 'En Corte', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($db, $sqlPedido);
            if (!$stmt) {
                throw new Exception("Error preparando la consulta de pedidos: " . mysqli_error($db));
            }

            mysqli_stmt_bind_param(
                $stmt, 
                "idsiddsssssssi", 
                $cliente_id, $total_pedido, $fecha_entrega, $vendedor_id, $abono, $saldo_pendiente,
                $tipo_entrega, $direccion_entrega, $barrio_entrega, $ciudad_entrega, $observaciones_entrega,
                $detalle_texto, $descripcion_texto, $cantidad_total
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error al insertar el pedido: " . mysqli_stmt_error($stmt));
            }

            // Obtener el ID de la orden generada
            $pedido_id = mysqli_insert_id($db); 
            mysqli_stmt_close($stmt);
        }

        $sqlDetalle = "INSERT INTO detalle_pedido (
            pedido_id, producto_id, cantidad, precio_unitario, color, talla, comentario_vendedor
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtDetalle = mysqli_prepare($db, $sqlDetalle);
        if (!$stmtDetalle) {
            throw new Exception("Error preparando la consulta de detalle: " . mysqli_error($db));
        }

        foreach ($items as $item) {
            $p_id = isset($item['producto_id']) ? intval($item['producto_id']) : 0;
            $nombre_producto = trim($item['nombre'] ?? '');
            $cant = intval($item['cantidad']);
            $precio = floatval($item['precio_unitario']);
            $col = !empty($item['color']) ? trim($item['color']) : 'Por definir';
            $tal = !empty($item['talla']) ? trim($item['talla']) : 'Estándar';
            $comen = !empty($item['comentario_vendedor']) ? trim($item['comentario_vendedor']) : '';

            if (empty($nombre_producto) || $cant <= 0 || $precio <= 0) {
                throw new Exception("Uno de los productos del pedido es inválido. Revise su carrito.");
            }

            $productoIdParaInsert = resolverProductoPedido($db, $p_id, $nombre_producto, $col, $tal, $precio);

            mysqli_stmt_bind_param($stmtDetalle, "iiidsss", $pedido_id, $productoIdParaInsert, $cant, $precio, $col, $tal, $comen);
            if (!mysqli_stmt_execute($stmtDetalle)) {
                throw new Exception("Error al insertar detalle del producto: " . mysqli_stmt_error($stmtDetalle));
            }
        }

        mysqli_stmt_close($stmtDetalle);

        // Si todo salió bien, guardamos definitivamente en la base de datos
        // Sincronizar el abono inicial del pedido en la tabla pagos.
        sincronizarAbonoEnPagos($db, $pedido_id, $abono);

        mysqli_commit($db);
        
        // Cerramos la conexión limpia
        mysqli_close($db);
        
        // Redireccionar con éxito al panel de producción
        header("Location: ../views/pedido_exitoso.php?id=" . $pedido_id);
        exit();

    } catch (Exception $e) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        @file_put_contents(
            $logDir . '/procesar_pedido_errors.log',
            json_encode([
                'time' => date('c'),
                'message' => $e->getMessage(),
                'post' => $_POST,
            ], JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        if (isset($db) && $db) {
            mysqli_rollback($db);
            mysqli_close($db);
        }
        // Regresa mostrando el mensaje de error real en pantalla
        header("Location: ../views/venta_mayorista.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../views/venta_mayorista.php");
    exit();
}