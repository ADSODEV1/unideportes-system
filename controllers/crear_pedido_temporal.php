<?php
// controllers/crear_pedido_temporal.php
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método no permitido');

    if (session_status() === PHP_SESSION_NONE) session_start();
    require_login(['vendedor','colaborador','admin']);

    // Conexión MySQLi (compatibilidad con procesar_pedido)
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

    // Recibir datos mínimos
    $cliente_id = isset($_POST['cliente_id']) && $_POST['cliente_id'] !== 'NUEVO' && ctype_digit(strval($_POST['cliente_id'])) ? intval($_POST['cliente_id']) : 0;
    $vendedor_id = intval($_SESSION['user_id'] ?? $_SESSION['vendedor_id'] ?? 0);
    $fecha_entrega = !empty($_POST['fecha_entrega']) ? trim($_POST['fecha_entrega']) : date('Y-m-d', strtotime('+15 days'));
    $tipo_entrega = $_POST['tipo_entrega'] ?? 'Tienda';
    $direccion = !empty($_POST['direccion_entrega']) ? trim($_POST['direccion_entrega']) : null;
    $barrio = !empty($_POST['barrio_entrega']) ? trim($_POST['barrio_entrega']) : null;
    $ciudad = !empty($_POST['ciudad_entrega']) ? trim($_POST['ciudad_entrega']) : null;
    $observaciones = !empty($_POST['observaciones_entrega']) ? trim($_POST['observaciones_entrega']) : null;

    if ($vendedor_id <= 0) throw new Exception('Vendedor no válido');
    // Permitimos cliente_id = 0 para ordenes temporales creadas desde UI sin cliente seleccionado.

    // Insert temporal
    $sql = "INSERT INTO pedidos (cliente_id, total_pedido, estado, fecha_entrega, vendedor_id, abono, saldo_pendiente, tipo_entrega, direccion_entrega, barrio_entrega, ciudad_entrega, observaciones_entrega, detalle)
            VALUES (?, 0, 'En Corte', ?, ?, 0, 0, ?, ?, ?, ?, ?, 'Pedido temporal creado desde UI')";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) throw new Exception('Error preparando consulta: ' . mysqli_error($db));
    mysqli_stmt_bind_param($stmt, 'isissssss', $cliente_id, $fecha_entrega, $vendedor_id, $tipo_entrega, $direccion, $barrio, $ciudad, $observaciones);
    if (!mysqli_stmt_execute($stmt)) throw new Exception('Error al crear pedido: ' . mysqli_stmt_error($stmt));
    $pedido_id = mysqli_insert_id($db);
    mysqli_stmt_close($stmt);
    mysqli_close($db);

    echo json_encode(['success' => true, 'pedido_id' => intval($pedido_id)]);
    exit();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit();
}
