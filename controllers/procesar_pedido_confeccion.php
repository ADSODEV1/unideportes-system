<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin', 'colaborador', 'vendedor']);
$pdo = app();
$rol = $_SESSION['rol'] ?? $_SESSION['role'] ?? 'vendedor';
$usuario_id = intval($_SESSION['user_id'] ?? $_SESSION['vendedor_id'] ?? 0);
$MAX_DESC = ['admin' => 1.00, 'colaborador' => 0.10, 'vendedor' => 0.05];
function ir($url) { header("Location: $url"); exit; }

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Método no permitido.');
    
    $carrito = json_decode($_POST['ventaJSON'] ?? $_POST['carritoJSON'] ?? '[]', true) ?: [];
    $abono = floatval($_POST['abono'] ?? 0);
    $fecha_ent = trim($_POST['fecha_entrega'] ?? '');
    
    if (empty($carrito)) throw new Exception('Agregue al menos una prenda.');
    if (!$fecha_ent) throw new Exception('Fecha de entrega requerida.');
    if ($usuario_id <= 0) throw new Exception('Sesión de vendedor no válida.');

    $pdo->beginTransaction();

    // ---- 1) Cliente existente o nuevo ----
    $flag = trim($_POST['cliente_id_hidden'] ?? '');
    if ($flag === 'NUEVO') {
        $nombre = trim($_POST['nuevo_cliente_nombre_completo'] ?? '');
        $nit = trim($_POST['nuevo_cliente_nit_cedula'] ?? '');
        if (!$nombre || !$nit) throw new Exception('Cliente nuevo: nombre y NIT obligatorios.');
        $chk = $pdo->prepare("SELECT id FROM clientes WHERE nit_cedula = ?");
        $chk->execute([$nit]);
        if ($chk->fetch()) throw new Exception('El NIT/Cédula ya está registrado.');
        $pdo->prepare("INSERT INTO clientes (codigo_descriptivo, nombre_completo, nit_cedula, telefono, estado) VALUES (?,?,?,?, 'activo')")
            ->execute(['CLI-' . substr(md5(uniqid()), 0, 4), $nombre, $nit, trim($_POST['nuevo_cliente_telefono'] ?? '')]);
        $cliente_id = intval($pdo->lastInsertId());
    } else {
        $cliente_id = intval($flag);
    }
    if ($cliente_id <= 0) throw new Exception('Seleccione un cliente.');

    // ---- 2) Bucle anti-fraude (el servidor recalcula, el JS no manda) ----
    $maxDesc = $MAX_DESC[$rol] ?? 0.05;
    $total = 0;
    $lineas = [];
    $resumen = [];
    $st = $pdo->prepare("SELECT tipo_prenda, precio_base FROM precios_base_confeccion WHERE id = ? AND activo = 1");
    
    foreach ($carrito as $it) {
        $tid = intval($it['tipo_prenda_id'] ?? 0);
        $cant = intval($it['cantidad'] ?? 0);
        $neg = floatval($it['precio_unitario'] ?? $it['precio_negociado'] ?? $it['precio'] ?? 0);
        
        if ($tid <= 0 || $cant <= 0) throw new Exception('Ítem inválido.');
        
        $st->execute([$tid]);
        $cat = $st->fetch(PDO::FETCH_ASSOC);
        if (!$cat) throw new Exception('Prenda fuera de catálogo o desactivada.');
        
        $piso = floatval($cat['precio_base']) * (1 - $maxDesc);
        if ($neg < $piso) {
            throw new Exception("{$cat['tipo_prenda']}: precio bajo el mínimo ($" . number_format($piso, 0, ',', '.') . ").");
        }
        
        $total += $neg * $cant;
        $lineas[] = [$tid, $cant, $neg, floatval($cat['precio_base']), trim($it['color'] ?? ''), trim($it['talla'] ?? ''), trim($it['comentario_vendedor'] ?? '')];
        $resumen[] = "{$cat['tipo_prenda']} x$cant";
    }

    // ---- 3) Control de dinero: 50% ----
    if ($abono < $total * 0.50) {
        throw new Exception('Abono mínimo: 50% ($' . number_format($total * 0.5, 0, ',', '.') . ').');
    }

    // ---- 4) Tres INSERT coordinados ----
   $pdo->prepare("INSERT INTO pedidos (cliente_id, vendedor_id, detalle, cantidad, total_pedido, abono, saldo_pendiente,
    estado, fecha_entrega, tipo_entrega, direccion_entrega, barrio_entrega, ciudad_entrega, observaciones_entrega)
    VALUES (?,?,?,?,?,?,NULL,'En Corte',?,?,?,?,?,?)")
    ->execute([
        $cliente_id,
        $usuario_id,
        implode(', ', $resumen),
        array_sum(array_column($lineas, 1)),
        $total,
        0,
        $fecha_ent,
        trim($_POST['tipo_entrega'] ?? 'Tienda'),
        trim($_POST['direccion_entrega'] ?? '') ?: null,
        trim($_POST['barrio_entrega'] ?? '') ?: null,
        trim($_POST['ciudad_entrega'] ?? 'Sogamoso') ?: null,
        trim($_POST['observaciones_entrega'] ?? '') ?: null
    ]);
    $pid = $pdo->lastInsertId();

    $d = $pdo->prepare("INSERT INTO detalle_pedido (pedido_id, tipo_prenda_id, cantidad, precio_unitario, precio_base_ref, color, talla, comentario_vendedor) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($lineas as $l) {
        $d->execute(array_merge([$pid], $l));
    }

    $pdo->prepare("INSERT INTO pagos (id_pg_pedido, monto, metodo_pago, fecha) VALUES (?,?,?,NOW())")
        ->execute([$pid, $abono, trim($_POST['metodo_pago'] ?? 'Efectivo')]);
    
    $pdo->commit();
    ir("../views/pedido_exitoso.php?id=$pid");
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("procesar_pedido_confeccion: " . $e->getMessage());
    ir("../views/venta_mayorista.php?error=" . urlencode($e->getMessage()));
}