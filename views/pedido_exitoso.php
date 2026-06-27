<?php
// views/pedido_exitoso.php
// Página de confirmación de pedido registrado exitosamente
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();

// Validar que llegue el ID del pedido
$pedido_id = !empty($_GET['id']) ? intval($_GET['id']) : 0;
if ($pedido_id === 0) {
    header("Location: panel_produccion.php");
    exit();
}

// 1. Obtener datos del pedido y del cliente
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               c.nombre_completo as cliente_nombre, 
               c.telefono, 
               c.email,
               (p.abono + IFNULL((SELECT SUM(pa.monto) FROM pagos pa WHERE pa.id_pg_pedido = p.id), 0)) AS total_abonado
        FROM pedidos p 
        LEFT JOIN clientes c ON p.cliente_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        die("El pedido solicitado no existe.");
    }

    // Calcular saldo real
    $total_abonado = floatval($pedido['total_abonado'] ?? 0);
    $saldo_real = floatval($pedido['total_pedido']) - $total_abonado;

    // 2. Obtener el detalle de los productos del taller
    $stmtD = $pdo->prepare("
        SELECT dp.*, prod.nombre as producto_nombre 
        FROM detalle_pedido dp
        LEFT JOIN productos prod ON dp.producto_id = prod.id
        WHERE dp.pedido_id = ?
    ");
    $stmtD->execute([$pedido_id]);
    $detalles = $stmtD->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al consultar la base de datos: " . $e->getMessage());
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        
        <!-- ENCABEZADO DE ÉXITO -->
        <div class="success-header">
            <div class="success-icon">✅</div>
            <h1>¡Pedido Registrado con Éxito!</h1>
            <p>La orden ha sido enviada a la Línea de Confección</p>
        </div>

        <!-- RESUMEN FINANCIERO -->
        <div class="stats-grid">
            <div class="stat-card stat-blue">
                <h3>Total Pedido</h3>
                <p>$<?= number_format($pedido['total_pedido'], 0, ',', '.') ?></p>
            </div>
            <div class="stat-card stat-green">
                <h3>Abono Inicial</h3>
                <p>$<?= number_format($total_abonado, 0, ',', '.') ?></p>
            </div>
            <div class="stat-card stat-red">
                <h3>Saldo Pendiente</h3>
                <p>$<?= number_format($saldo_real, 0, ',', '.') ?></p>
            </div>
        </div>

        <!-- DATOS DEL CLIENTE Y ENTREGA -->
        <div class="info-grid">
            <div class="info-card">
                <h2 class="section-subtitle">👤 Datos del Cliente</h2>
                <div class="info-row">
                    <span class="info-label">Nombre:</span>
                    <span class="info-value"><?= htmlspecialchars($pedido['cliente_nombre'] ?? 'Cliente General') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value"><?= htmlspecialchars($pedido['telefono'] ?? 'N/A') ?></span>
                </div>
                <?php if (!empty($pedido['email'])): ?>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?= htmlspecialchars($pedido['email']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="info-card">
                <h2 class="section-subtitle">📦 Datos de Entrega</h2>
                <div class="info-row">
                    <span class="info-label">Tipo:</span>
                    <span class="info-value"><?= htmlspecialchars($pedido['tipo_entrega']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha Estimada:</span>
                    <span class="info-value fecha-entrega">
                        📅 <?= date('d/m/Y', strtotime($pedido['fecha_entrega'])) ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado:</span>
                    <span class="badge badge-info"><?= htmlspecialchars($pedido['estado']) ?></span>
                </div>
            </div>
        </div>

        <!-- DETALLE DE FABRICACIÓN -->
        <div class="card-section">
            <h2 class="section-subtitle">🧵 Detalle de Fabricación (Taller)</h2>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Talla</th>
                            <th class="text-center">Color</th>
                            <th class="text-center">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($detalles as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['producto_nombre']) ?></strong>
                                    <?php if(!empty($row['comentario_vendedor'])): ?>
                                        <br><span class="text-small">
                                            * Nota: <?= htmlspecialchars($row['comentario_vendedor']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="talla-badge"><?= htmlspecialchars($row['talla']) ?></span>
                                </td>
                                <td class="text-center">
                                    <?= htmlspecialchars($row['color']) ?>
                                </td>
                                <td class="text-center">
                                    <strong><?= intval($row['cantidad']) ?></strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- BOTONES DE ACCIÓN -->
        <div class="form-actions">
            <button onclick="window.print();" class="btn-secondary">
                🖨️ Imprimir Ticket
            </button>
            <a href="ver_ticket_pedido.php?id=<?= $pedido_id ?>" class="btn-primary">
                🔎 Ver Ticket Completo
            </a>
            <a href="panel_produccion.php" class="btn-success">
                ➕ Nueva Orden
            </a>
        </div>

    </main>
</div>

<style>
/* ============================================
   PEDIDO EXITOSO - ESTILOS SIMPLIFICADOS
   ============================================ */

/* Encabezado de éxito */
.success-header {
    text-align: center;
    padding: 30px 20px;
    background: #f0fdf4;
    border: 2px solid #10b981;
    border-radius: 12px;
    margin-bottom: 25px;
}

.success-icon {
    font-size: 4rem;
    margin-bottom: 10px;
}

.success-header h1 {
    color: #065f46;
    font-size: 1.8rem;
    margin: 0 0 8px 0;
    font-weight: 700;
}

.success-header p {
    color: #047857;
    margin: 0;
    font-size: 1rem;
}

/* Grid de estadísticas */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    padding: 18px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 4px solid;
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 8px 0;
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card p {
    margin: 0;
    font-size: 1.8rem;
    font-weight: bold;
    color: #1e293b;
}

.stat-blue { border-left-color: #2563eb; }
.stat-green { border-left-color: #10b981; }
.stat-red { border-left-color: #ef4444; }

/* Grid de información */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 25px;
}

.info-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
}

.section-subtitle {
    color: #475569;
    font-size: 1.05rem;
    font-weight: 600;
    margin: 0 0 15px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid #e2e8f0;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: #64748b;
    font-size: 0.9rem;
    font-weight: 500;
}

.info-value {
    color: #1e293b;
    font-size: 0.9rem;
    font-weight: 600;
    text-align: right;
}

.fecha-entrega {
    color: #2563eb;
    font-weight: 700;
}

/* Sección tipo tarjeta */
.card-section {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

/* Tabla */
.table-container {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: #f1f5f9;
    border-bottom: 2px solid #e2e8f0;
}

.data-table th {
    padding: 14px;
    text-align: left;
    color: #475569;
    font-weight: 600;
    font-size: 0.9rem;
}

.data-table td {
    padding: 14px;
    border-bottom: 1px solid #e2e8f0;
    color: #334155;
    vertical-align: top;
}

.data-table tbody tr:hover {
    background: #f8fafc;
}

.text-center {
    text-align: center;
}

.text-small {
    font-size: 0.85rem;
    color: #64748b;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.talla-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #f1f5f9;
    color: #334155;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid #e2e8f0;
}

/* Botones */
.btn-primary {
    padding: 10px 20px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-block;
    transition: background 0.2s;
}

.btn-primary:hover {
    background: #1d4ed8;
}

.btn-secondary {
    padding: 10px 20px;
    background: white;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.btn-success {
    padding: 10px 20px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-block;
    transition: background 0.2s;
}

.btn-success:hover {
    background: #059669;
}

/* Acciones del formulario */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 10px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

/* Estilos de impresión */
@media print {
    .sidebar-panel, 
    .main-header, 
    .form-actions,
    footer {
        display: none !important;
    }
    
    .main-content-panel {
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
    }
    
    .success-header {
        border: 2px solid #000;
    }
    
    @page {
        size: letter;
        margin: 10mm;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card p {
        font-size: 1.5rem;
    }
    
    .data-table {
        font-size: 0.85rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 10px 8px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions > * {
        width: 100%;
        text-align: center;
    }
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>