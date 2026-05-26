<?php
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();

require_login(['vendedor', 'colaborador', 'admin']);

$venta_id = intval(request('id'));
if ($venta_id <= 0) {
    redirect('/unideportes-system/views/panel_vendedor.php?error=id_invalido');
}

// MODIFICADO: Se agregaron los nuevos campos de entrega/domicilio a la consulta SQL
$stmt = $pdo->prepare(
    "SELECT v.id, v.ticket_numero, v.fecha_venta, v.metodo_pago, v.tipo_transferencia, COALESCE(v.cambio, 0) AS cambio, v.total_venta,
            v.tipo_entrega, v.direccion_entrega, v.barrio_entrega, v.ciudad_entrega, v.referencia_entrega AS observaciones_entrega,
            c.nombre_completo AS cliente, c.nit_cedula AS cliente_documento, c.telefono AS cliente_telefono,
            u.username AS vendedor
     FROM ventas v
     INNER JOIN clientes c ON v.cliente_id = c.id
     INNER JOIN usuarios u ON v.vendedor_id = u.id
     WHERE v.id = ?
     LIMIT 1"
);
$stmt->execute([$venta_id]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    redirect('/unideportes-system/views/panel_vendedor.php?error=venta_no_encontrada');
}

$stmtDetalles = $pdo->prepare(
    "SELECT dv.cantidad, dv.precio_unitario, dv.subtotal, p.nombre, p.referencia
     FROM detalle_venta dv
     INNER JOIN productos p ON dv.producto_id = p.id
     WHERE dv.venta_id = ?"
);
$stmtDetalles->execute([$venta_id]);
$detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

// Aquí se renderiza la vista visual que usa los datos de $venta
include __DIR__ . '/../views/ticket_actual.php';