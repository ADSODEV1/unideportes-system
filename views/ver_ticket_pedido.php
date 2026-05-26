<?php
// views/ver_ticket_pedido.php
require_once __DIR__ . '/../config/bootstrap.php';

$pdo = app();
$id = intval($_GET['id'] ?? 0);

// Traer la información combinada del pedido, el cliente y sus pagos
$stmt = $pdo->prepare("SELECT p.*, c.nombre_completo, c.nit_cedula, c.telefono,
                       (SELECT SUM(monto) FROM pagos WHERE id_pg_pedido = p.id) as total_abonado
                       FROM pedidos p
                       INNER JOIN clientes c ON p.cliente_id = c.id
                       WHERE p.id = ?");
$stmt->execute([$id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die("El ticket solicitado no existe.");
}

$saldo_pendiente = $pedido['total_pedido'] - $pedido['total_abonado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket_Pedido_<?= $pedido['id'] ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; margin: 0; padding: 10px; width: 280px; }
        .text-center { text-align: center; }
        .linea { border-top: 1px dashed #000; margin: 8px 0; }
        .totales table { width: 100%; }
        .totales td { padding: 2px 0; }
        .btn-print { background: #1e293b; color: white; border: none; padding: 8px 15px; border-radius: 4px; font-weight: bold; cursor: pointer; margin-bottom: 15px; width: 100%; }
        @media print { .no-print { display: none; } body { padding: 0; width: 100%; } }
    </style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">🖨️ Imprimir Comprobante</button>
    <a href="pedidos_admin.php" class="btn-print no-print" style="display:block; text-align:center; background:#64748b; text-decoration:none;">Volver a Producción</a>

    <div class="text-center">
        <h2>UNIDEPORTES</h2>
        <p>CONFECCIÓN DEPORTIVA MAYORISTA<br>NIT: 123.456.789-0<br>Sogamoso, Boyacá</p>
    </div>

    <div class="linea"></div>
    <p><strong>TICKET DE PRODUCCIÓN:</strong> #<?= $pedido['id'] ?></p>
    <p><strong>FECHA REGISTRO:</strong> <?= date('d/m/Y H:i') ?></p>
    <p><strong>FECHA ENTREGA:</strong> <?= date('d/m/Y', strtotime($pedido['fecha_entrega'])) ?></p>
    <div class="linea"></div>

    <p><strong>CLIENTE:</strong> <?= htmlspecialchars($pedido['nombre_completo']) ?><br>
    <strong>NIT/CC:</strong> <?= htmlspecialchars($pedido['nit_cedula']) ?><br>
    <strong>TEL:</strong> <?= htmlspecialchars($pedido['telefono'] ?: 'N/A') ?></p>
    <div class="linea"></div>

    <p><strong>DETALLE:</strong><br><?= htmlspecialchars($pedido['detalle']) ?></p>
    <?php if(!empty($pedido['descripcion'])): ?>
        <p><strong>NOTAS DE TALLAS:</strong><br><em><?= htmlspecialchars($pedido['descripcion']) ?></em></p>
    <?php endif; ?>
    <p><strong>CANTIDAD TOTAL:</strong> <?= (int)$pedido['cantidad'] ?> Unidades.</p>
    <div class="linea"></div>

    <div class="totales">
        <table>
            <tr>
                <td><strong>VALOR TOTAL:</strong></td>
                <td style="text-align: right;"><strong>$<?= number_format($pedido['total_pedido'], 2) ?></strong></td>
            </tr>
            <tr>
                <td>ABONO INICIAL:</td>
                <td style="text-align: right;">$<?= number_format($pedido['total_abonado'], 2) ?></td>
            </tr>
            <tr style="font-size: 13px;">
                <td><strong>SALDO DEBE:</strong></td>
                <td style="text-align: right;"><strong>$<?= number_format($saldo_pendiente, 2) ?></strong></td>
            </tr>
        </table>
    </div>

    <div class="linea"></div>
    <div class="text-center" style="font-size: 10px; margin-top: 15px;">
        <p>Conserve este ticket para reclamar su pedido en el punto de venta.</p>
        <p>¡Gracias por confiar en Unideportes!</p>
    </div>

</body>
</html>