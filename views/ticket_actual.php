<?php
// views/ticket_actual.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();

// Asegurar que el usuario tiene permisos para ver tickets
require_login(['vendedor', 'colaborador', 'admin']);

// 1. OBTENER Y VALIDAR EL ID DE LA VENTA DESDE LA URL
$venta_id = !empty($_GET['id']) ? intval($_GET['id']) : 0;

if ($venta_id <= 0) {
    header("Location: /unideportes-system/views/nueva_venta.php?error=" . urlencode("ID de ticket inválido o no especificado."));
    exit();
}

try {
    // 2. CONSULTAR LOS DATOS MAESTROS DE LA VENTA
    $sqlVenta = "SELECT v.*, 
                        c.nombre_completo AS cliente, 
                        c.nit_cedula AS cliente_documento, 
                        c.telefono AS cliente_telefono,
                        IFNULL(NULLIF(CONCAT(u.name, ' ', u.lastname), ' '), u.username) AS vendedor
                 FROM ventas v
                 INNER JOIN clientes c ON v.cliente_id = c.id
                 INNER JOIN usuarios u ON v.vendedor_id = u.id
                 WHERE v.id = ?";
                 
    $stmtVenta = $pdo->prepare($sqlVenta);
    $stmtVenta->execute([$venta_id]);
    $venta = $stmtVenta->fetch(PDO::FETCH_ASSOC);

    // Si la venta no existe en la BD, detenemos el proceso de forma segura
    if (!$venta) {
        throw new Exception("El ticket solicitado no existe en el sistema.");
    }

    // 3. CONSULTAR EL DETALLE DE LOS PRODUCTOS COMPRADOS
    $sqlDetalles = "SELECT dv.*, p.nombre, p.referencia 
                    FROM detalle_venta dv
                    INNER JOIN productos p ON dv.producto_id = p.id
                    WHERE dv.venta_id = ?";
                    
    $stmtDetalles = $pdo->prepare($sqlDetalles);
    $stmtDetalles->execute([$venta_id]);
    $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    header("Location: /unideportes-system/views/nueva_venta.php?error=" . urlencode($e->getMessage()));
    exit();
}

// 4. CONFIGURACIÓN DEL ENTORNO VISUAL
$pagina_actual = basename($_SERVER['PHP_SELF']);
$base = "/unideportes-system";
include(__DIR__ . "/header.php");
?>

<style>
@media print {
    /* Ocultar elementos globales innecesarios para el soporte físico */
    header, 
    .sidebar-container, 
    #sidebar,
    .sidebar,
    .alert,
    .action-footer,
    footer,
    nav {
        display: none !important;
    }
    
    /* Reajustar contenedores para ocupar el 100% de la hoja */
    .container, 
    .admin-layout, 
    .main-content-panel {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        box-shadow: none !important;
        background: transparent !important;
    }

    .page-header h1 {
        font-size: 1.6rem !important;
        margin-top: 10px !important;
    }

    /* Forzar fondo blanco y bordes limpios en la tabla */
    .table-responsive {
        border: none !important;
        padding: 0 !important;
        background: #fff !important;
    }

    /* Mantener legibilidad cromática en navegadores basados en Webkit/Blink */
    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        background-color: #ffffff !important;
        color: #000000 !important;
        font-size: 11pt !important;
    }
    
    .data-table th {
        background-color: #f1f5f9 !important;
        color: #000000 !important;
    }
}
</style>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        <div class="page-header" style="margin-bottom: 20px;">
            <h1>Ticket de Venta</h1>
            <p>Confirmación detallada de la transacción registrada.</p>
        </div>

        <?php if (!empty($_GET['success']) && $_GET['success'] === 'venta_registrada'): ?>
            <div class="alert alert-success" style="margin-bottom: 20px; padding: 12px; background: #d1fae5; color: #065f46; border: 1px solid #10b981; border-radius: 8px;">
                Venta registrada correctamente. Tickets generados: <?= htmlspecialchars($venta['ticket_numero']) ?>.
            </div>
        <?php endif; ?>

        <div class="table-responsive" style="margin-bottom: 25px; padding: 20px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-between;">
                
                <div style="min-width: 220px; flex: 1;">
                    <h3 style="color: #1e293b; margin-bottom: 10px; font-size: 1.1rem; border-bottom: 2px solid #c91a25; padding-bottom: 4px;">Datos de la Venta</h3>
                    <p><strong>Ticket:</strong> <?= htmlspecialchars($venta['ticket_numero'] ?? 'T' . str_pad($venta['id'], 6, '0', STR_PAD_LEFT)) ?></p>
                    <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])) ?></p>
                    <p><strong>Vendedor:</strong> <?= htmlspecialchars($venta['vendedor']) ?></p>
                </div>
                
                <div style="min-width: 220px; flex: 1;">
                    <h3 style="color: #1e293b; margin-bottom: 10px; font-size: 1.1rem; border-bottom: 2px solid #c91a25; padding-bottom: 4px;">Cliente</h3>
                    <p><strong><?= htmlspecialchars($venta['cliente']) ?></strong></p>
                    <p><strong>NIT/Cédula:</strong> <?= htmlspecialchars($venta['cliente_documento']) ?></p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($venta['cliente_telefono'] ?: '-') ?></p>
                </div>

                <div style="min-width: 220px; flex: 1;">
                    <h3 style="color: #1e293b; margin-bottom: 10px; font-size: 1.1rem; border-bottom: 2px solid #c91a25; padding-bottom: 4px;">Modalidad de Entrega</h3>
                    <p><strong>Tipo:</strong> <?= htmlspecialchars($venta['tipo_entrega'] ?? 'Tienda') ?></p>
                    <?php if (($venta['tipo_entrega'] ?? 'Tienda') === 'Domicilio'): ?>
                        <p style="margin-bottom: 2px;"><strong>Dirección:</strong> <?= htmlspecialchars($venta['direccion_entrega'] ?? '-') ?></p>
                        <p style="margin-bottom: 2px;"><strong>Barrio:</strong> <?= htmlspecialchars($venta['barrio_entrega'] ?? '-') ?></p>
                        <p style="margin-bottom: 2px;"><strong>Ciudad:</strong> <?= htmlspecialchars($venta['ciudad_entrega'] ?? 'Sogamoso') ?></p>
                        <?php if (!empty($venta['observaciones_entrega'])): ?>
                            <p style="margin-top: 5px; font-size: 0.9rem; color: #64748b;"><em>Obs: <?= htmlspecialchars($venta['observaciones_entrega']) ?></em></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p style="color: #64748b; font-size: 0.9rem; margin-top: 5px;"><em>Retiro físico en la tienda de Unideportes.</em></p>
                    <?php endif; ?>
                </div>
                
                <div style="min-width: 220px; flex: 1;">
                    <h3 style="color: #1e293b; margin-bottom: 10px; font-size: 1.1rem; border-bottom: 2px solid #c91a25; padding-bottom: 4px;">Pago</h3>
                    <p><strong>Método:</strong> <?= htmlspecialchars($venta['metodo_pago']) ?></p>
                    <?php if (!empty($venta['tipo_transferencia'])): ?>
                        <p><strong>Plataforma:</strong> <?= htmlspecialchars($venta['tipo_transferencia']) ?></p>
                    <?php endif; ?>
                    <p><strong>Cambio Entregado:</strong> $<?= number_format($venta['cambio'], 2, ',', '.') ?></p>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Referencia</th>
                        <th>Color</th>
                        <th>Talla</th>
                        <th>Comentario</th>
                        <th>Cantidad</th>
                        <th>Precio unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (is_array($detalles) && count($detalles) > 0): ?>
                        <?php foreach ($detalles as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nombre']) ?></td>
                                <td><?= htmlspecialchars($item['referencia']) ?></td>
                                <td><?= htmlspecialchars($item['color'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($item['talla'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($item['comentario_vendedor'] ?? '-') ?></td>
                                <td><?= intval($item['cantidad']) ?></td>
                                <td>$<?= number_format($item['precio_unitario'], 2, ',', '.') ?></td>
                                <td>$<?= number_format($item['subtotal'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 20px; color: #888;">No hay productos registrados en esta venta.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <?php if (floatval($venta['costo_envio'] ?? 0) > 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: right; color: #64748b; padding: 8px 15px;">Recargo Domicilio</td>
                            <td style="color: #64748b;">$<?= number_format($venta['costo_envio'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="4" style="text-align: right; font-weight: bold; padding: 15px; font-size: 1.1rem;">Total Venta</td>
                        <td style="font-weight: bold; color: #1e3a8a; font-size: 1.1rem;">$<?= number_format($venta['total_venta'], 2, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="action-footer" style="margin-top: 25px; gap: 10px; display: flex;">
            <button onclick="window.print();" class="btn-print" style="background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                🖨️ Imprimir Ticket
            </button>
            <a href="/unideportes-system/views/nueva_venta.php" class="btn-primary" style="text-decoration: none; background: #c91a25; color: white; padding: 10px 20px; border-radius: 6px; font-weight: 600;">Nueva venta</a>
            <a href="/unideportes-system/views/panel_vendedor.php" class="btn-secondary" style="text-decoration: none; background: #64748b; color: white; padding: 10px 20px; border-radius: 6px; font-weight: 600;">Volver al panel</a>
        </div>
    </main>
</div>

<?php include(__DIR__ . "/footer.php"); ?>