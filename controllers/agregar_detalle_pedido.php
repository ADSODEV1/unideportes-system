<?php
// controllers/agregar_detalle_pedido.php
require_once __DIR__ . '/../config/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

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
    $estado = 'activo';
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

function sincronizarDetallePedidoCartera(mysqli $db, int $pedido_id): void {
    if ($pedido_id <= 0) {
        return;
    }

    $sqlSync = "UPDATE detalle_pedido dp
                INNER JOIN pedidos p ON p.id = dp.pedido_id
                LEFT JOIN (
                    SELECT id_pg_pedido, SUM(monto) AS total_pagado
                    FROM pagos
                    GROUP BY id_pg_pedido
                ) pg ON pg.id_pg_pedido = dp.pedido_id
                SET
                    dp.total_pedido = COALESCE(
                        (SELECT SUM(dp2.cantidad * dp2.precio_unitario) FROM detalle_pedido dp2 WHERE dp2.pedido_id = dp.pedido_id),
                        p.total_pedido,
                        0
                    ),
                    dp.abono_pedido = COALESCE(p.abono, 0),
                    dp.pagos_registrados = COALESCE(pg.total_pagado, 0),
                    dp.saldo_pendiente = GREATEST(
                        COALESCE(p.saldo_pendiente, 0),
                        COALESCE(
                            (SELECT SUM(dp2.cantidad * dp2.precio_unitario) FROM detalle_pedido dp2 WHERE dp2.pedido_id = dp.pedido_id),
                            p.total_pedido,
                            0
                        ) - (COALESCE(p.abono, 0) + COALESCE(pg.total_pagado, 0))
                    ),
                    dp.estado_cartera = CASE
                        WHEN GREATEST(
                            COALESCE(p.saldo_pendiente, 0),
                            COALESCE(
                                (SELECT SUM(dp2.cantidad * dp2.precio_unitario) FROM detalle_pedido dp2 WHERE dp2.pedido_id = dp.pedido_id),
                                p.total_pedido,
                                0
                            ) - (COALESCE(p.abono, 0) + COALESCE(pg.total_pagado, 0))
                        ) <= 0 THEN 'Pagado'
                        ELSE 'Por Pagar'
                    END
                WHERE dp.pedido_id = ?";

    $stmtSync = mysqli_prepare($db, $sqlSync);
    if (!$stmtSync) {
        throw new Exception('Error preparando sincronización de cartera: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmtSync, 'i', $pedido_id);
    if (!mysqli_stmt_execute($stmtSync)) {
        throw new Exception('Error sincronizando cartera en detalle_pedido: ' . mysqli_stmt_error($stmtSync));
    }

    mysqli_stmt_close($stmtSync);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método no permitido');
    if (session_status() === PHP_SESSION_NONE) session_start();
    require_login(['vendedor','colaborador','admin']);

    @include_once __DIR__ . '/../config/connection.php';
    $db = null;
    if (isset($conn) && $conn instanceof mysqli) $db = $conn;
    elseif (isset($conexion) && $conexion instanceof mysqli) $db = $conexion;
    if (!$db) {
        $host = 'localhost'; $user = 'root'; $password = ''; $database = 'unideportes';
        $db = mysqli_connect($host, $user, $password, $database);
        if (!$db) throw new Exception('Error de conexión a base de datos');
        mysqli_set_charset($db, 'utf8mb4');
    }

    $pedido_id = isset($_POST['pedido_id']) && ctype_digit(strval($_POST['pedido_id'])) ? intval($_POST['pedido_id']) : 0;
    if ($pedido_id <= 0) throw new Exception('pedido_id inválido');

    $producto_id = isset($_POST['producto_id']) && ctype_digit(strval($_POST['producto_id'])) ? intval($_POST['producto_id']) : 0;
    $producto_nombre = trim($_POST['producto_nombre'] ?? '');
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;
    $precio_unitario = isset($_POST['precio_unitario']) ? floatval($_POST['precio_unitario']) : 0;
    $color = trim($_POST['color'] ?? 'Por definir');
    $talla = trim($_POST['talla'] ?? 'Estándar');
    $comentario = trim($_POST['comentario_vendedor'] ?? '');

    if (empty($producto_nombre) || $cantidad <= 0 || $precio_unitario <= 0) throw new Exception('Datos de detalle incompletos');

    $producto_id = resolverProductoPedido($db, $producto_id, $producto_nombre, $color, $talla, $precio_unitario);

    $sql = "INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario, color, talla, comentario_vendedor) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) throw new Exception('Error preparando consulta: ' . mysqli_error($db));
    mysqli_stmt_bind_param($stmt, 'iiidsss', $pedido_id, $producto_id, $cantidad, $precio_unitario, $color, $talla, $comentario);
    if (!mysqli_stmt_execute($stmt)) throw new Exception('Error al insertar detalle: ' . mysqli_stmt_error($stmt));
    $detalle_id = mysqli_insert_id($db);
    mysqli_stmt_close($stmt);

    // Opcional: actualizar total en pedidos sumando montos actuales
    $sqlUpd = "UPDATE pedidos SET total_pedido = (SELECT COALESCE(SUM(cantidad * precio_unitario),0) FROM detalle_pedido WHERE pedido_id = ?), saldo_pendiente = (SELECT COALESCE(SUM(cantidad * precio_unitario),0) - COALESCE(abono,0) FROM pedidos WHERE id = ?) WHERE id = ?";
    $stmt2 = mysqli_prepare($db, $sqlUpd);
    if ($stmt2) {
        mysqli_stmt_bind_param($stmt2, 'iii', $pedido_id, $pedido_id, $pedido_id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
    }

    sincronizarDetallePedidoCartera($db, $pedido_id);

    mysqli_close($db);
    echo json_encode(['success' => true, 'detalle_id' => intval($detalle_id)]);
    exit();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit();
}
