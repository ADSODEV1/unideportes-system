<?php
// views/pedido_exitoso.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();
$conn = connection();

// Validar que llegue el ID del pedido
$pedido_id = !empty($_GET['id']) ? intval($_GET['id']) : 0;
if ($pedido_id === 0) {
    header("Location: venta_mayorista.php");
    exit();
}

// 1. Obtener datos del pedido y del cliente
try {
    $stmt = $conn->prepare("SELECT p.*, c.nombre_completo AS cliente_nombre, c.telefono
                            FROM pedidos p
                            LEFT JOIN clientes c ON p.cliente_id = c.id
                            WHERE p.id = ?");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        die("El pedido solicitado no existe.");
    }

    // 2. Detalle de prendas
    $stmtD = $conn->prepare("SELECT dp.cantidad, dp.precio_unitario, dp.color, dp.talla,
                                     dp.comentario_vendedor,
                                     COALESCE(prod.nombre, p2.detalle, 'Producto personalizado') AS producto_nombre
                              FROM detalle_pedido dp
                              LEFT JOIN productos prod ON prod.id = dp.producto_id
                              LEFT JOIN pedidos p2 ON p2.id = dp.pedido_id
                              WHERE dp.pedido_id = ?");
    $stmtD->execute([$pedido_id]);
    $detalles = $stmtD->fetchAll(PDO::FETCH_ASSOC);

    $total_real = array_sum(array_map(fn($d) => $d['cantidad'] * $d['precio_unitario'], $detalles));
    if ($total_real <= 0) {
        $total_real = floatval($pedido['total_pedido'] ?? 0);
    }
    $abono_real   = floatval($pedido['abono'] ?? 0);
    $saldo_real   = max(0, $total_real - $abono_real);

} catch (Exception $e) {
    die("Error al consultar la base de datos: " . $e->getMessage());
}

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">

        <div class="page-header header-dashboard" style="margin-bottom: 28px;">
            <div>
                <h1 style="color: #047857;">¡Pedido #<?= $pedido_id ?> Registrado con Éxito! ✅</h1>
                <p style="color: var(--text-light);">La orden fue enviada a la línea de confección.</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button onclick="window.print();" class="btn-secondary" style="padding: 10px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; border: 1px solid var(--border);">🖨️ Imprimir Ticket</button>
                <a href="venta_mayorista.php" class="btn-primary" style="padding: 10px 18px; border-radius: 8px; font-weight: 600; text-decoration: none; background: var(--navy); color: #fff;">➕ Nueva Orden</a>
            </div>
        </div>

        <!-- Resumen financiero -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 28px;">
            <div class="venta-container" style="text-align: center; padding: 20px;">
                <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-light); margin-bottom: 6px;">Total Pedido</div>
                <div style="font-size: 1.6rem; font-weight: 800; color: var(--navy);">$<?= number_format($total_real, 0, ',', '.') ?></div>
            </div>
            <div class="venta-container" style="text-align: center; padding: 20px;">
                <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-light); margin-bottom: 6px;">Abono</div>
                <div style="font-size: 1.6rem; font-weight: 800; color: #047857;">$<?= number_format($abono_real, 0, ',', '.') ?></div>
            </div>
            <div class="venta-container" style="text-align: center; padding: 20px;">
                <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-light); margin-bottom: 6px;">Saldo Pendiente</div>
                <div style="font-size: 1.6rem; font-weight: 800; color: #dc2626;">$<?= number_format($saldo_real, 0, ',', '.') ?></div>
            </div>
        </div>

        <!-- Datos del pedido -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px; flex-wrap: wrap;">
            <div class="venta-container" style="padding: 20px;">
                <h3 style="margin: 0 0 14px; font-size: 1rem; color: var(--navy);">Cliente</h3>
                <p style="margin: 0 0 6px;"><strong>Nombre:</strong> <?= htmlspecialchars($pedido['cliente_nombre'] ?? 'Cliente General') ?></p>
                <p style="margin: 0;"><strong>Teléfono:</strong> <?= htmlspecialchars($pedido['telefono'] ?? 'N/A') ?></p>
            </div>
            <div class="venta-container" style="padding: 20px;">
                <h3 style="margin: 0 0 14px; font-size: 1rem; color: var(--navy);">Entrega</h3>
                <p style="margin: 0 0 6px;"><strong>Tipo:</strong> <?= htmlspecialchars($pedido['tipo_entrega'] ?? 'Tienda') ?></p>
                <p style="margin: 0;"><strong>Fecha estimada:</strong> <?= date('d/m/Y', strtotime($pedido['fecha_entrega'])) ?></p>
                <?php if (!empty($pedido['descripcion'])): ?>
                    <p style="margin: 8px 0 0; font-size: 0.88rem; color: var(--text-light);">📝 <?= htmlspecialchars($pedido['descripcion']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla de prendas -->
        <div class="venta-container" style="margin-bottom: 28px; overflow-x: auto;">
            <h3 style="margin: 0 0 16px; font-size: 1rem; color: var(--navy);">🧵 Detalle de Fabricación</h3>
            <?php if (!empty($detalles)): ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--navy); color: #fff; text-align: left;">
                        <th style="padding: 12px;">Producto / Prenda</th>
                        <th style="padding: 12px; text-align: center;">Talla</th>
                        <th style="padding: 12px; text-align: center;">Color</th>
                        <th style="padding: 12px; text-align: right;">Precio u.</th>
                        <th style="padding: 12px; text-align: center;">Cant.</th>
                        <th style="padding: 12px; text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $det): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 12px;">
                            <strong><?= htmlspecialchars($det['producto_nombre']) ?></strong>
                            <?php if (!empty($det['comentario_vendedor'])): ?>
                                <span style="display: block; font-size: 0.82rem; color: var(--text-light);">📝 <?= htmlspecialchars($det['comentario_vendedor']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem;"><?= htmlspecialchars($det['talla'] ?: '—') ?></span>
                        </td>
                        <td style="padding: 12px; text-align: center;"><?= htmlspecialchars($det['color'] ?: '—') ?></td>
                        <td style="padding: 12px; text-align: right;">$<?= number_format($det['precio_unitario'], 0, ',', '.') ?></td>
                        <td style="padding: 12px; text-align: center; font-weight: 700;"><?= intval($det['cantidad']) ?></td>
                        <td style="padding: 12px; text-align: right; font-weight: 700;">$<?= number_format($det['precio_unitario'] * $det['cantidad'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc;">
                        <td colspan="5" style="padding: 12px; text-align: right; font-weight: 700;">Total:</td>
                        <td style="padding: 12px; text-align: right; font-weight: 800; color: var(--navy); font-size: 1.1rem;">$<?= number_format($total_real, 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
            <?php else: ?>
                <p style="color: var(--text-light); padding: 20px 0;">No hay prendas registradas en este pedido.</p>
            <?php endif; ?>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end; flex-wrap: wrap;">
            <button onclick="window.print();" style="padding: 10px 20px; background: #fff; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; cursor: pointer; color: var(--text);">🖨️ Imprimir Ticket</button>
            <a href="venta_mayorista.php" style="padding: 10px 20px; background: var(--navy); color: #fff; border-radius: 8px; font-weight: 600; text-decoration: none;">➕ Nueva Orden Mayorista</a>
            <a href="pedidos_admin.php" style="padding: 10px 20px; background: #047857; color: #fff; border-radius: 8px; font-weight: 600; text-decoration: none;">🏭 Ver en Producción</a>
        </div>

    </main>
</div>

<?php include(__DIR__ . '/footer.php'); ?>