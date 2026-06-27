<?php
// views/mis_ventas.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();
$vendedor_id = $_SESSION['user_id'] ?? 0;

// 1. CONSULTA PARA TARJETAS DE RESUMEN
try {
    $sqlResumen = "SELECT 
                    COUNT(*) as total_ventas, 
                    IFNULL(SUM(total_venta), 0) as total_ingresos, 
                    IFNULL(AVG(total_venta), 0) as promedio_venta 
                   FROM ventas 
                   WHERE vendedor_id = :vendedor_id";
    $stmtResumen = $pdo->prepare($sqlResumen);
    $stmtResumen->execute(['vendedor_id' => $vendedor_id]);
    $resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $resumen = ['total_ventas' => 0, 'total_ingresos' => 0, 'promedio_venta' => 0];
}

// 2. CONSULTA PARA HISTORIAL DE VENTAS
try {
    $sqlVentas = "SELECT v.*, c.nombre_completo as cliente_nombre 
                  FROM ventas v
                  INNER JOIN clientes c ON v.cliente_id = c.id
                  WHERE v.vendedor_id = :vendedor_id
                  ORDER BY v.fecha_venta DESC";
    $stmtVentas = $pdo->prepare($sqlVentas);
    $stmtVentas->execute(['vendedor_id' => $vendedor_id]);
    $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $ventas = [];
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Mis Ventas</h1>
                <p>Historial completo de transacciones realizadas por ti.</p>
            </div>
            <a href="panel_vendedor.php" class="btn-secondary">← Volver al Panel</a>
        </div>

        <!-- TARJETAS DE RESUMEN -->
        <div class="stats-grid">
            <div class="stat-card stat-blue">
                <h3>Total Ventas</h3>
                <p><?= $resumen['total_ventas'] ?></p>
            </div>
            <div class="stat-card stat-green">
                <h3>Total Ingresos</h3>
                <p>$<?= number_format($resumen['total_ingresos'], 0, ',', '.') ?></p>
            </div>
            <div class="stat-card stat-amber">
                <h3>Promedio Venta</h3>
                <p>$<?= number_format($resumen['promedio_venta'], 0, ',', '.') ?></p>
            </div>
        </div>

        <!-- TABLA DE HISTORIAL -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Fecha/Hora</th>
                        <th>Cliente</th>
                        <th>Método</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ventas)): ?>
                        <?php foreach ($ventas as $venta): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($venta['ticket_numero'] ?? 'S/N') ?></strong>
                                </td>
                                <td>
                                    <span class="text-small">
                                        <?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($venta['cliente_nombre']) ?>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?= htmlspecialchars($venta['metodo_pago']) ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <strong>$<?= number_format($venta['total_venta'], 0, ',', '.') ?></strong>
                                </td>
                                <td class="text-center">
                                    <a href="ticket_actual.php?id=<?= $venta['id'] ?>" class="link-action">
                                        🔎 Ver Ticket
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-results">
                                <span class="empty-icon">📊</span>
                                <p>No has realizado ventas aún.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<style>
/* ============================================
   MIS VENTAS - ESTILOS SIMPLIFICADOS
   ============================================ */

/* Grid de estadísticas */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 4px solid;
}

.stat-card h3 {
    margin: 0 0 8px 0;
    font-size: 0.9rem;
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

.stat-blue {
    border-left-color: #2563eb;
}

.stat-green {
    border-left-color: #10b981;
}

.stat-amber {
    border-left-color: #f59e0b;
}

/* Utilidades de texto */
.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

.text-small {
    font-size: 0.85rem;
    color: #64748b;
}

/* Enlaces de acción */
.link-action {
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: color 0.2s;
}

.link-action:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 768px) {
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
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>