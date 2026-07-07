<?php
// views/ticket_actual.php
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

$rol = $_SESSION['role'] ?? 'vendedor';
$panel_volver = ($rol === 'admin') ? 'panel_admin.php' : 'panel_vendedor.php';

include(__DIR__ . "/header.php");
?>

<style>
/* Ticket simplificado - Proyecto SENA */
.ticket-container {
    max-width: 800px;
    margin: 20px auto;
    background: white;
    padding: 30px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

.ticket-header {
    text-align: center;
    padding: 20px;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 25px;
}

.ticket-header h1 {
    margin: 0;
    font-size: 1.8rem;
    color: #1e293b;
    letter-spacing: 2px;
}

.ticket-header p {
    margin: 5px 0 0 0;
    color: #64748b;
    font-size: 0.9rem;
}

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

.products-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    font-size: 0.9rem;
}

.products-table thead tr {
    background: #1e293b;
    color: white;
    text-align: left;
}

.products-table th {
    padding: 10px 8px;
    font-weight: 600;
    font-size: 0.85rem;
}

.products-table td {
    padding: 8px;
    border-bottom: 1px solid #e2e8f0;
}

.products-table tbody tr:hover {
    background: #f8fafc;
}

.totals-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
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

.total-line.final {
    font-size: 1.3rem;
    font-weight: bold;
    color: #2563eb;
    border-top: 2px solid #e2e8f0;
    margin-top: 10px;
    padding-top: 10px;
}

.delivery-info {
    background: #eff6ff;
    border-left: 3px solid #3b82f6;
    padding: 12px;
    margin: 15px 0;
    font-size: 0.9rem;
}

.footer-legal {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
    font-size: 0.85rem;
    color: #64748b;
}

.ticket-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.btn-ticket {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.btn-print {
    background: #2563eb;
    color: white;
}

.btn-print:hover {
    background: #1d4ed8;
}

.btn-back {
    background: #64748b;
    color: white;
}

.btn-back:hover {
    background: #475569;
}

.btn-new {
    background: #10b981;
    color: white;
}

.btn-new:hover {
    background: #059669;
}

@media print {
    .ticket-actions, header, .sidebar-panel, footer, nav {
        display: none !important;
    }
    .ticket-container {
        border: none;
        padding: 0;
        max-width: 100%;
        margin: 0;
    }
    @page {
        size: letter;
        margin: 10mm;
    }
}

@media (max-width: 768px) {
    .info-section {
        grid-template-columns: 1fr;
    }
    .ticket-actions {
        flex-direction: column;
    }
    .btn-ticket {
        width: 100%;
        text-align: center;
    }
}
</style>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>
    
    <main class="main-content-panel">
        
        <div class="ticket-actions">
            <button onclick="window.print()" class="btn-ticket btn-print">🖨️ Imprimir</button>
            <a href="<?= $panel_volver ?>" class="btn-ticket btn-back">← Volver</a>
            <a href="/unideportes-system/views/nueva_venta.php" class="btn-ticket btn-new">+ Nueva Venta</a>
        </div>

        <div class="ticket-container">
            
            <!-- HEADER -->
            <div class="ticket-header">
                <h1>UNIDEPORTES</h1>
                <p>NIT 46372005-1 | Carrera 12 # 12-27, Sogamoso, Boyacá | Tel: 3185509709</p>
                <p style="margin-top: 10px; font-weight: 600; color: #1e293b;">COMPROBANTE DE VENTA</p>
            </div>

            <!-- INFORMACIÓN DE VENTA Y CLIENTE -->
            <div class="info-section">
                <div>
                    <strong>Venta</strong>
                    <span><?= htmlspecialchars($venta['codigo_descriptivo'] ?? 'VEN-' . str_pad($venta['id'], 6, '0', STR_PAD_LEFT)) ?></span>
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
                    <strong>Ticket</strong>
                    <span style="font-size: 0.85rem; color: #64748b;"><?= htmlspecialchars($venta['ticket_numero']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- TABLA DE PRODUCTOS -->
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Ref.</th>
                        <th style="text-align: center;">Cant.</th>
                        <th style="text-align: right;">P. Unit.</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($detalles as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['nombre']) ?></td>
                        <td><?= htmlspecialchars($item['referencia']) ?></td>
                        <td style="text-align: center;"><?= intval($item['cantidad']) ?></td>
                        <td style="text-align: right;">$<?= number_format($item['precio_unitario'], 0, ',', '.') ?></td>
                        <td style="text-align: right; font-weight: 600;">$<?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- TOTALES -->
            <div class="totals-box">
                <?php if (floatval($venta['costo_envio'] ?? 0) > 0): ?>
                <div class="total-line">
                    <span>Envío:</span>
                    <span>$<?= number_format($venta['costo_envio'], 0, ',', '.') ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (floatval($venta['descuento_monto'] ?? 0) > 0): ?>
                <div class="total-line">
                    <span>Descuento:</span>
                    <span>-$<?= number_format($venta['descuento_monto'], 0, ',', '.') ?></span>
                </div>
                <?php endif; ?>
                
                <div class="total-line final">
                    <span>TOTAL:</span>
                    <span>$<?= number_format($venta['total_venta'], 0, ',', '.') ?></span>
                </div>
                
                <?php if (floatval($venta['cambio'] ?? 0) > 0): ?>
                <div class="total-line" style="margin-top: 8px;">
                    <span>Cambio:</span>
                    <span style="color: #10b981;">$<?= number_format($venta['cambio'], 0, ',', '.') ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- INFORMACIÓN DE ENTREGA -->
            <?php if ($venta['tipo_entrega'] === 'Domicilio'): ?>
            <div class="delivery-info">
                <strong style="display: block; margin-bottom: 5px;">📍 Entrega a domicilio:</strong>
                <?= htmlspecialchars($venta['direccion_entrega']) ?>, <?= htmlspecialchars($venta['barrio_entrega']) ?>
                <?php if (!empty($venta['observaciones_entrega'])): ?>
                <br><em style="color: #64748b;"><?= htmlspecialchars($venta['observaciones_entrega']) ?></em>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- FOOTER LEGAL -->
            <div class="footer-legal">
                <p style="margin: 0 0 5px 0; font-weight: 600;">Gracias por su compra</p>
                <p style="margin: 0; font-size: 0.75rem;">Proyecto ADSO - SENA 2026</p>
            </div>
            
        </div>
    </main>
</div>

<?php include(__DIR__ . "/footer.php"); ?>