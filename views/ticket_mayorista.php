<?php
// views/ticket_mayorista.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['admin', 'colaborador', 'vendedor']);

$pdo = app();
$venta_id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT v.*, c.nombre_completo, c.nit_cedula, c.telefono,
IFNULL(NULLIF(CONCAT(u.name, ' ', u.lastname), ' '), u.username) AS vendedor
FROM ventas v
INNER JOIN clientes c ON v.cliente_id = c.id
INNER JOIN usuarios u ON v.vendedor_id = u.id
WHERE v.id = ?");
$stmt->execute([$venta_id]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    die("El ticket solicitado no existe.");
}

$stmtDetalles = $pdo->prepare("SELECT dv.*, p.nombre, p.referencia
FROM detalle_venta dv
INNER JOIN productos p ON dv.producto_id = p.id
WHERE dv.venta_id = ?");
$stmtDetalles->execute([$venta_id]);
$detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($detalles as $item) {
    $subtotal += floatval($item['precio_unitario']) * intval($item['cantidad']);
}

$descuento = floatval($venta['descuento_monto'] ?? 0);
$total_venta = floatval($venta['total_venta']);
$abono = floatval($venta['abono'] ?? 0);
$saldo_pendiente = floatval($venta['saldo_pendiente'] ?? 0);

$rol = $_SESSION['role'] ?? 'vendedor';
$panel_volver = ($rol === 'admin') ? 'panel_admin.php' : 'panel_vendedor.php';

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>
    
    <main class="main-content-panel">
        
        <!-- BOTONES DE ACCIÓN -->
        <div class="ticket-actions">
            <button onclick="window.print()" class="btn-primary">🖨️ Imprimir</button>
            <a href="<?= $panel_volver ?>" class="btn-secondary">← Volver</a>
            <a href="/unideportes-system/views/venta_mayorista.php" class="btn-secondary">+ Nueva Venta</a>
        </div>

        <!-- TICKET -->
        <div class="ticket-container">
            
            <!-- ENCABEZADO -->
            <div class="ticket-header">
                <h1>UNIDEPORTES</h1>
                <p>Sogamoso, Boyacá | Tel: 3185509709</p>
                <p class="ticket-type">VENTA MAYORISTA</p>
            </div>

            <!-- INFORMACIÓN DE VENTA Y CLIENTE -->
            <div class="info-section">
                <div>
                    <strong>Venta</strong>
                    <span class="venta-codigo">
                        <?= htmlspecialchars($venta['codigo_descriptivo'] ?? 'M-' . str_pad($venta['id'], 6, '0', STR_PAD_LEFT)) ?>
                    </span>
                </div>
                <div>
                    <strong>Fecha</strong>
                    <span><?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])) ?></span>
                </div>
                <div>
                    <strong>Cliente</strong>
                    <span><?= htmlspecialchars($venta['nombre_completo']) ?></span>
                </div>
                <div>
                    <strong>Documento</strong>
                    <span><?= htmlspecialchars($venta['nit_cedula']) ?></span>
                </div>
                <div>
                    <strong>Vendedor</strong>
                    <span><?= htmlspecialchars($venta['vendedor']) ?></span>
                </div>
                <div>
                    <strong>Pago</strong>
                    <span><?= htmlspecialchars($venta['metodo_pago']) ?></span>
                </div>
                <?php if (!empty($venta['ticket_numero'])): ?>
                <div>
                    <strong>Ticket Interno</strong>
                    <span class="text-small"><?= htmlspecialchars($venta['ticket_numero']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($venta['fecha_entrega'])): ?>
                <div>
                    <strong>Fecha de Entrega</strong>
                    <span><?= date('d/m/Y', strtotime($venta['fecha_entrega'])) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- TABLA DE PRODUCTOS -->
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Ref.</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-right">P. Unit.</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($detalles as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nombre']) ?></td>
                            <td><?= htmlspecialchars($item['referencia']) ?></td>
                            <td class="text-center"><?= intval($item['cantidad']) ?></td>
                            <td class="text-right">$<?= number_format($item['precio_unitario'], 0, ',', '.') ?></td>
                            <td class="text-right text-bold">$<?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- OBSERVACIONES -->
            <?php if (!empty($venta['observaciones_venta_mayor'])): ?>
                <div class="observaciones-box">
                    <strong>Observaciones:</strong><br>
                    <em><?= htmlspecialchars($venta['observaciones_venta_mayor']) ?></em>
                </div>
            <?php endif; ?>

            <!-- TOTALES -->
            <div class="totals-box">
                <div class="total-line">
                    <span>Subtotal:</span>
                    <span>$<?= number_format($subtotal, 0, ',', '.') ?></span>
                </div>
                <?php if ($descuento > 0): ?>
                    <div class="total-line text-warning">
                        <span>Descuento Mayorista:</span>
                        <span>-$<?= number_format($descuento, 0, ',', '.') ?></span>
                    </div>
                <?php endif; ?>
                <?php if (floatval($venta['costo_envio'] ?? 0) > 0): ?>
                    <div class="total-line">
                        <span>Envío:</span>
                        <span>$<?= number_format($venta['costo_envio'], 0, ',', '.') ?></span>
                    </div>
                <?php endif; ?>
                <div class="total-line total-final">
                    <span>TOTAL:</span>
                    <span>$<?= number_format($total_venta, 0, ',', '.') ?></span>
                </div>
                <div class="total-line">
                    <span>Abono Inicial:</span>
                    <span class="text-success">$<?= number_format($abono, 0, ',', '.') ?></span>
                </div>
            </div>

            <!-- SALDO PENDIENTE -->
            <?php if ($saldo_pendiente > 0): ?>
                <div class="saldo-box">
                    <h3>SALDO PENDIENTE</h3>
                    <div class="monto">$<?= number_format($saldo_pendiente, 0, ',', '.') ?></div>
                    <p>Fecha límite de pago: <?= date('d/m/Y', strtotime($venta['fecha_entrega'])) ?></p>
                </div>
            <?php endif; ?>

            <!-- FOOTER LEGAL -->
            <div class="footer-legal">
                <p>Comprobante de venta mayorista y abono inicial</p>
                <p>Conserve este ticket para reclamar su pedido</p>
                <p class="text-small">Proyecto ADSO - SENA 2026</p>
            </div>

        </div>
    </main>
</div>

<style>
/* ============================================
   TICKET MAYORISTA - ESTILOS SIMPLIFICADOS
   ============================================ */

/* Ticket container */
.ticket-container {
    max-width: 800px;
    margin: 20px auto;
    background: white;
    padding: 30px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

/* Header del ticket */
.ticket-header {
    text-align: center;
    padding: 20px;
    border-bottom: 3px solid #1e293b;
    margin-bottom: 25px;
}

.ticket-header h1 {
    margin: 0;
    font-size: 1.8rem;
    color: #1e293b;
    letter-spacing: 3px;
}

.ticket-header p {
    margin: 5px 0 0 0;
    color: #64748b;
    font-size: 0.9rem;
}

.ticket-type {
    color: #ca8a04;
    font-weight: bold;
    margin-top: 8px;
    font-size: 1rem;
}

/* Sección de información */
.info-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8fafc;
    border-radius: 6px;
    font-size: 0.9rem;
}

.info-section div {
    display: flex;
    flex-direction: column;
}

.info-section strong {
    color: #475569;
    font-size: 0.8rem;
    text-transform: uppercase;
    margin-bottom: 3px;
}

.info-section span {
    color: #1e293b;
    font-weight: 500;
}

.venta-codigo {
    font-size: 1.1rem;
    font-weight: bold;
    color: #1e293b;
}

/* Tabla de productos */
.products-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    font-size: 0.9rem;
}

.products-table thead {
    background: #1e293b;
    color: white;
}

.products-table th {
    padding: 10px 8px;
    text-align: left;
    font-size: 0.85rem;
    font-weight: 600;
}

.products-table td {
    padding: 8px;
    border-bottom: 1px solid #e2e8f0;
}

.products-table tbody tr:nth-child(even) {
    background: #f8fafc;
}

.products-table tbody tr:hover {
    background: #f1f5f9;
}

/* Cajas de totales */
.totals-box {
    background: #fef3c7;
    border: 2px solid #f59e0b;
    padding: 20px;
    border-radius: 6px;
    margin-top: 20px;
}

.total-line {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    font-size: 0.95rem;
}

.total-line.total-final {
    font-size: 1.3rem;
    font-weight: bold;
    color: #047857;
    border-top: 2px solid #f59e0b;
    margin-top: 10px;
    padding-top: 10px;
}

/* Observaciones */
.observaciones-box {
    background: #eff6ff;
    border-left: 4px solid #3b82f6;
    padding: 12px;
    margin: 15px 0;
    font-size: 0.9rem;
    border-radius: 4px;
}

/* Saldo pendiente */
.saldo-box {
    background: #fee2e2;
    border: 2px solid #dc2626;
    padding: 15px;
    border-radius: 6px;
    margin-top: 15px;
    text-align: center;
}

.saldo-box h3 {
    margin: 0 0 8px 0;
    color: #dc2626;
    font-size: 1rem;
}

.saldo-box .monto {
    font-size: 1.5rem;
    font-weight: bold;
    color: #dc2626;
}

.saldo-box p {
    margin: 8px 0 0 0;
    font-size: 0.85rem;
    color: #64748b;
}

/* Footer legal */
.footer-legal {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
    font-size: 0.85rem;
    color: #64748b;
}

.footer-legal p {
    margin: 0 0 5px 0;
}

/* Botones de acción */
.ticket-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

/* Utilidades */
.text-center { text-align: center; }
.text-right { text-align: right; }
.text-bold { font-weight: bold; }
.text-small { font-size: 0.85rem; color: #64748b; }
.text-warning { color: #ca8a04; }
.text-success { color: #059669; font-weight: 600; }

/* Estilos de impresión */
@media print {
    .ticket-actions, header, .sidebar-panel, footer, nav {
        display: none !important;
    }
    
    .ticket-container {
        border: none;
        padding: 0;
        max-width: 100%;
    }
    
    @page {
        size: letter;
        margin: 10mm;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .info-section {
        grid-template-columns: 1fr;
    }
    
    .ticket-actions {
        flex-direction: column;
    }
    
    .ticket-actions > * {
        width: 100%;
        text-align: center;
    }
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>