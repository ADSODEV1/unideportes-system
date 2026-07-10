<?php
// views/ticket_actual.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();

require_login(['vendedor', 'colaborador', 'admin']);

$venta_id = !empty($_GET['id']) ? intval($_GET['id']) : 0;

if ($venta_id <= 0) {
    header("Location: /unideportes-system/views/nueva_venta.php?error=" . urlencode("ID de ticket inválido."));
    exit();
}

try {
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

    if (!$venta) {
        throw new Exception("El ticket no existe.");
    }

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

include(__DIR__ . "/header.php");
?>

<style>
/* TICKET MINIMALISTA */
.ticket-container {
    background: var(--card);
    padding: 30px;
    border-radius: var(--radius);
    border: 1px solid var(--border);
}

.ticket-header {
    text-align: center;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--border);
    margin-bottom: 25px;
}

.ticket-header h1 {
    color: var(--text);
    font-size: 1.8rem;
    margin: 0 0 5px 0;
}

.ticket-header .subtitle {
    color: var(--text-light);
    font-size: 0.9rem;
}

.ticket-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
    padding-bottom: 25px;
    border-bottom: 1px solid var(--border);
}

.info-section h3 {
    color: var(--text);
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0 0 10px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-section p {
    margin: 4px 0;
    font-size: 0.9rem;
    color: var(--text-light);
}

.info-section strong {
    color: var(--text);
}

/* Tabla minimalista */
.ticket-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 25px;
}

.ticket-table th {
    background: var(--input-bg);
    color: var(--text);
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 0.85rem;
    border-bottom: 2px solid var(--border);
}

.ticket-table td {
    padding: 12px;
    border-bottom: 1px solid var(--border);
    color: var(--text);
    font-size: 0.9rem;
}

.ticket-table tfoot td {
    font-weight: 600;
    border-bottom: none;
}

.ticket-table .total-row td {
    font-size: 1.1rem;
    color: var(--text);
    border-top: 2px solid var(--border);
    padding-top: 15px;
}

/* Botones minimalistas */
.ticket-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    padding-top: 25px;
    border-top: 1px solid var(--border);
}

.btn-print {
    background: var(--primary);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: var(--radius-sm);
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-print:hover {
    background: var(--primary-dark);
}

.btn-secondary {
    background: var(--input-bg);
    color: var(--text);
    padding: 10px 20px;
    border-radius: var(--radius-sm);
    text-decoration: none;
    font-weight: 600;
    transition: background 0.2s;
}

.btn-secondary:hover {
    background: var(--border);
}

/* Alerta minimalista */
.alert-minimal {
    padding: 12px;
    background: var(--primary-light);
    color: var(--primary);
    border-radius: var(--radius-sm);
    margin-bottom: 20px;
    font-weight: 500;
}

/* PRINT */
@media print {
    header, .sidebar-panel, footer, nav, .ticket-actions {
        display: none !important;
    }
    
    body {
        background: white !important;
        color: black !important;
    }
    
    .container, .admin-layout, .main-content-panel {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        box-shadow: none !important;
    }
    
    .ticket-container {
        border: none !important;
        padding: 0 !important;
    }
    
    .alert-minimal {
        background: #f0f0f0 !important;
        color: black !important;
        border: 1px solid #ccc !important;
    }
}
</style>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        <div class="ticket-container">
            
            <!-- Encabezado -->
            <div class="ticket-header">
                <h1>TICKET DE VENTA</h1>
                <p class="subtitle">Unideportes - Sistema de Gestión</p>
            </div>

            <!-- Alerta de éxito -->
            <?php if (!empty($_GET['success']) && $_GET['success'] === 'venta_registrada'): ?>
                <div class="alert-minimal">
                    ✓ Venta registrada correctamente - Ticket: <?= htmlspecialchars($venta['ticket_numero']) ?>
                </div>
            <?php endif; ?>

            <!-- Información principal -->
            <div class="ticket-info-grid">
                <div class="info-section">
                    <h3>Datos de la Venta</h3>
                    <p><strong>Ticket:</strong> <?= htmlspecialchars($venta['ticket_numero']) ?></p>
                    <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])) ?></p>
                    <p><strong>Vendedor:</strong> <?= htmlspecialchars($venta['vendedor']) ?></p>
                </div>
                
                <div class="info-section">
                    <h3>Cliente</h3>
                    <p><strong><?= htmlspecialchars($venta['cliente']) ?></strong></p>
                    <p><strong>NIT:</strong> <?= htmlspecialchars($venta['cliente_documento']) ?></p>
                    <?php if (!empty($venta['cliente_telefono'])): ?>
                        <p><strong>Tel:</strong> <?= htmlspecialchars($venta['cliente_telefono']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="info-section">
                    <h3>Entrega</h3>
                    <p><strong>Tipo:</strong> <?= htmlspecialchars($venta['tipo_entrega']) ?></p>
                    <?php if ($venta['tipo_entrega'] === 'Domicilio'): ?>
                        <p><?= htmlspecialchars($venta['direccion_entrega']) ?></p>
                        <p><?= htmlspecialchars($venta['barrio_entrega']) ?></p>
                        <p><?= htmlspecialchars($venta['ciudad_entrega']) ?></p>
                    <?php else: ?>
                        <p>Retiro en tienda</p>
                    <?php endif; ?>
                </div>
                
                <div class="info-section">
                    <h3>Pago</h3>
                    <p><strong>Método:</strong> <?= htmlspecialchars($venta['metodo_pago']) ?></p>
                    <?php if (!empty($venta['tipo_transferencia'])): ?>
                        <p><?= htmlspecialchars($venta['tipo_transferencia']) ?></p>
                    <?php endif; ?>
                    <?php if ($venta['cambio'] > 0): ?>
                        <p><strong>Cambio:</strong> $<?= number_format($venta['cambio'], 0, ',', '.') ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabla de productos -->
            <table class="ticket-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Referencia</th>
                        <th>Cant.</th>
                        <th>Precio Unit.</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nombre']) ?></td>
                            <td><?= htmlspecialchars($item['referencia']) ?></td>
                            <td><?= intval($item['cantidad']) ?></td>
                            <td>$<?= number_format($item['precio_unitario'], 0, ',', '.') ?></td>
                            <td style="text-align: right;">$<?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <?php if ($venta['costo_envio'] > 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: right;">Domicilio:</td>
                            <td style="text-align: right;">$<?= number_format($venta['costo_envio'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right; font-size: 1.2rem;">TOTAL:</td>
                        <td style="text-align: right; font-size: 1.2rem;">$<?= number_format($venta['total_venta'], 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Botones -->
            <div class="ticket-actions">
                <button onclick="window.print();" class="btn-print">
                    🖨️ Imprimir
                </button>
                <a href="/unideportes-system/views/nueva_venta.php" class="btn-secondary">
                    Nueva Venta
                </a>
                <a href="/unideportes-system/views/panel_vendedor.php" class="btn-secondary">
                    Volver al Panel
                </a>
            </div>

        </div>
    </main>
</div>

<?php include(__DIR__ . "/footer.php"); ?>