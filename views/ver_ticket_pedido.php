<?php
// views/ver_ticket_pedido.php
require_once __DIR__ . '/../config/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_login(['admin', 'colaborador', 'vendedor']);

$pdo = app();
$id = intval($_GET['id'] ?? 0);

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

$rol = $_SESSION['role'] ?? 'vendedor';
$panel_volver = ($rol === 'admin') ? 'panel_admin.php' : 'panel_vendedor.php';

include(__DIR__ . "/header.php");
?>

<style>
.ticket-container {
    max-width: 800px;
    margin: 20px auto;
    background: white;
    padding: 30px;
    border: 2px solid #000;
}

.ticket-header {
    text-align: center;
    padding: 20px 0;
    border-bottom: 3px solid #000;
    margin-bottom: 25px;
}

.ticket-header h1 {
    margin: 0;
    font-size: 2rem;
    color: #000;
    letter-spacing: 3px;
    font-weight: 900;
}

.ticket-header p {
    margin: 5px 0 0 0;
    color: #000;
    font-size: 0.9rem;
}

.ticket-header .tipo {
    color: #000;
    font-weight: bold;
    margin-top: 10px;
    font-size: 1.1rem;
    text-transform: uppercase;
}

.info-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #000;
    font-size: 0.9rem;
}

.info-section div {
    display: flex;
    flex-direction: column;
}

.info-section strong {
    color: #000;
    font-size: 0.8rem;
    text-transform: uppercase;
    margin-bottom: 3px;
    font-weight: 700;
}

.info-section span {
    color: #000;
    font-weight: 500;
}

.estado-badge {
    display: inline-block;
    padding: 4px 12px;
    border: 2px solid #000;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    background: white;
    color: #000;
}

.detalle-box {
    border: 1px solid #000;
    padding: 15px;
    margin: 15px 0;
}

.detalle-box h4 {
    margin: 0 0 8px 0;
    color: #000;
    font-size: 1rem;
    font-weight: 700;
}

.detalle-box p {
    margin: 5px 0;
    color: #000;
}

.observaciones-box {
    border: 2px solid #000;
    padding: 12px;
    margin: 15px 0;
    font-size: 0.9rem;
}

.totals-box {
    border: 2px solid #000;
    padding: 20px;
    margin-top: 20px;
}

.total-line {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 1rem;
    border-bottom: 1px solid #ccc;
}

.total-line:last-child {
    border-bottom: none;
}

.total-line.abono {
    color: #000;
    font-weight: 600;
}

/* 🔴 SALDO PENDIENTE EN ROJO */
.saldo-box {
    border: 3px solid #dc2626;
    padding: 15px;
    margin-top: 15px;
    text-align: center;
    background: #fff;
}

.saldo-box h3 {
    margin: 0 0 8px 0;
    color: #dc2626;
    font-size: 1rem;
    font-weight: 700;
}

.saldo-box .monto {
    font-size: 1.8rem;
    font-weight: 900;
    color: #dc2626;
}

/* 🔵 FECHA DE ENTREGA EN AZUL */
.entrega-box {
    border: 2px solid #2563eb;
    padding: 15px;
    margin-top: 15px;
    text-align: center;
    background: #fff;
}

.entrega-box h3 {
    margin: 0 0 8px 0;
    color: #2563eb;
    font-size: 1rem;
    font-weight: 700;
}

.entrega-box .fecha {
    font-size: 1.5rem;
    font-weight: 900;
    color: #2563eb;
}

.entrega-box p {
    color: #2563eb;
}

.footer-legal {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #000;
    font-size: 0.85rem;
    color: #000;
}

.ticket-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.btn-ticket {
    padding: 10px 20px;
    border: 2px solid #000;
    background: white;
    color: #000;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.9rem;
}

.btn-ticket:hover {
    background: #000;
    color: white;
}

/* Cuando esté pagado completamente */
.pagado-box {
    border: 3px solid #059669;
    padding: 15px;
    margin-top: 15px;
    text-align: center;
    background: #fff;
}

.pagado-box h3 {
    margin: 0;
    color: #059669;
    font-weight: 900;
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
</style>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>
    
    <main class="main-content-panel">
        
        <div class="ticket-actions">
            <button onclick="window.print()" class="btn-ticket">🖨️ Imprimir</button>
            <a href="<?= $panel_volver ?>" class="btn-ticket">← Volver</a>
            <a href="/unideportes-system/views/nuevo_pedido.php" class="btn-ticket">+ Nuevo Pedido</a>
        </div>

        <div class="ticket-container">
            
            <div class="ticket-header">
                <h1>UNIDEPORTES</h1>
                <p>Sogamoso, Boyacá | Tel: 3185509709</p>
                <p class="tipo">PEDIDO DE CONFECCIÓN</p>
            </div>

            <div class="info-section">
                <div>
                    <strong>Ticket de Producción</strong>
                    <span>#<?= $pedido['id'] ?></span>
                </div>
                <div>
                    <strong>Fecha de Registro</strong>
                    <span><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></span>
                </div>
                <div>
                    <strong>Cliente</strong>
                    <span><?= htmlspecialchars($pedido['nombre_completo']) ?></span>
                </div>
                <div>
                    <strong>Documento</strong>
                    <span><?= htmlspecialchars($pedido['nit_cedula']) ?></span>
                </div>
                <div>
                    <strong>Estado</strong>
                    <span>
                        <span class="estado-badge"><?= htmlspecialchars($pedido['estado']) ?></span>
                    </span>
                </div>
                <div>
                    <strong>Teléfono</strong>
                    <span><?= htmlspecialchars($pedido['telefono'] ?: 'N/A') ?></span>
                </div>
            </div>

            <div class="detalle-box">
                <h4><?= htmlspecialchars($pedido['detalle']) ?></h4>
                <p><strong>Cantidad Total:</strong> <?= (int)$pedido['cantidad'] ?> Unidades</p>
            </div>

            <?php if(!empty($pedido['descripcion'])): ?>
                <div class="observaciones-box">
                    <strong>Observaciones de Confección:</strong><br>
                    <em><?= htmlspecialchars($pedido['descripcion']) ?></em>
                </div>
            <?php endif; ?>

            <div class="totals-box">
                <div class="total-line">
                    <span>Valor Total del Pedido:</span>
                    <span style="font-weight: 700;">$<?= number_format($pedido['total_pedido'], 0, ',', '.') ?></span>
                </div>
                <div class="total-line abono">
                    <span>Total Abonado:</span>
                    <span>$<?= number_format($pedido['total_abonado'], 0, ',', '.') ?></span>
                </div>
            </div>

            <?php if ($saldo_pendiente > 0): ?>
                <div class="saldo-box">
                    <h3>SALDO PENDIENTE</h3>
                    <div class="monto">$<?= number_format($saldo_pendiente, 0, ',', '.') ?></div>
                </div>
            <?php else: ?>
                <div class="pagado-box">
                    <h3>✅ PEDIDO PAGADO COMPLETAMENTE</h3>
                </div>
            <?php endif; ?>

            <div class="entrega-box">
                <h3>📅 FECHA DE ENTREGA ESTIMADA</h3>
                <div class="fecha"><?= date('d/m/Y', strtotime($pedido['fecha_entrega'])) ?></div>
                <p style="margin: 8px 0 0 0; font-size: 0.85rem;">Conserve este ticket para reclamar su pedido</p>
            </div>

            <div class="footer-legal">
                <p style="margin: 0 0 5px 0;">Comprobante de pedido de confección personalizada</p>
                <p style="margin: 0; font-size: 0.75rem;">Proyecto ADSO - SENA 2026</p>
            </div>

        </div>
        
    </main>
</div>

<?php include(__DIR__ . "/footer.php"); ?>