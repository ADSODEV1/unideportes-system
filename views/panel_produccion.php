<?php
// views/panel_produccion.php
// Panel de gestión de línea de fabricación (Taller)
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['admin']);

$pdo = app();

// CONSULTA DE PEDIDOS ACTIVOS EN PRODUCCIÓN
try {
    $sql = "SELECT p.*, 
                   p.total_pedido, 
                   c.nombre_completo as cliente_nombre,
                   (p.abono + IFNULL((SELECT SUM(pa.monto) FROM pagos pa WHERE pa.id_pg_pedido = p.id), 0)) AS total_abonado
            FROM pedidos p 
            LEFT JOIN clientes c ON p.cliente_id = c.id 
            WHERE p.estado IN ('En Corte', 'En Confección', 'En Acabado')
            ORDER BY p.fecha_entrega ASC";
            
    $stmt_pedidos = $pdo->query($sql);
    $pedidos_activos = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pedidos_activos = [];
    $error_msg = "Error al consultar la línea de producción: " . $e->getMessage();
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Línea de Fabricación</h1>
                <p>Monitorea las prendas en confección y gestiona las fases operativas del taller.</p>
            </div>
            <a href="panel_admin.php" class="btn-secondary">← Volver al Panel</a>
        </div>

        <!-- MENSAJE DE ERROR -->
        <?php if (isset($error_msg)): ?>
            <div class="alert-error"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <!-- ALERTA DE SIN PEDIDOS -->
        <?php if (empty($pedidos_activos)): ?>
            <div class="empty-state">
                <span class="empty-icon">🧵</span>
                <h2>Sin órdenes en fabricación</h2>
                <p>No hay pedidos activos en la línea de producción en este momento.</p>
                <a href="nuevo_pedido.php" class="btn-primary">+ Crear Nuevo Pedido</a>
            </div>
        <?php else: ?>
            
            <!-- RESUMEN RÁPIDO -->
            <div class="stats-grid">
                <?php
                $en_corte = count(array_filter($pedidos_activos, fn($p) => $p['estado'] === 'En Corte'));
                $en_confeccion = count(array_filter($pedidos_activos, fn($p) => $p['estado'] === 'En Confección'));
                $en_acabado = count(array_filter($pedidos_activos, fn($p) => $p['estado'] === 'En Acabado'));
                ?>
                <div class="stat-card stat-warning">
                    <h3>En Corte</h3>
                    <p><?= $en_corte ?></p>
                </div>
                <div class="stat-card stat-blue">
                    <h3>En Confección</h3>
                    <p><?= $en_confeccion ?></p>
                </div>
                <div class="stat-card stat-green">
                    <h3>En Acabado</h3>
                    <p><?= $en_acabado ?></p>
                </div>
            </div>

            <!-- TABLA DE PEDIDOS EN PRODUCCIÓN -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>OP #</th>
                            <th>Cliente</th>
                            <th>Fecha Entrega</th>
                            <th>Estado</th>
                            <th class="text-right">Cuentas</th>
                            <th>Prendas a Confeccionar</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pedidos_activos as $pedido): 
                            $p_id = $pedido['id'];
                            
                            // Buscar detalles del pedido
                            $stmtD = $pdo->prepare("SELECT dp.*, prod.nombre FROM detalle_pedido dp 
                                                    LEFT JOIN productos prod ON dp.producto_id = prod.id 
                                                    WHERE dp.pedido_id = ?");
                            $stmtD->execute([$p_id]);
                            $detalles = $stmtD->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Cálculo de cuentas
                            $total_cuenta = floatval($pedido['total_pedido'] ?? 0);
                            $abono_real = floatval($pedido['total_abonado'] ?? 0);
                            $saldo_real = $total_cuenta - $abono_real;

                            // Clase del badge según estado
                            $clase_badge = match($pedido['estado']) {
                                'En Corte' => 'badge-warning',
                                'En Confección' => 'badge-info',
                                'En Acabado' => 'badge-success',
                                default => 'badge-info'
                            };

                            // Verificar si la fecha está vencida
                            $fechaEntrega = strtotime($pedido['fecha_entrega']);
                            $hoy = strtotime(date('Y-m-d'));
                            $esVencido = $fechaEntrega < $hoy;
                        ?>
                            <tr class="<?= $esVencido ? 'fila-vencida' : '' ?>">
                                <td>
                                    <strong>#<?= $pedido['id'] ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($pedido['cliente_nombre'] ?? 'Cliente General') ?>
                                </td>
                                <td>
                                    <?php if ($esVencido): ?>
                                        <span class="fecha-vencida">
                                            🚨 VENCIDO<br>
                                            <span class="text-small"><?= date('d/m/Y', $fechaEntrega) ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="fecha-normal">
                                            📅 <?= date('d/m/Y', $fechaEntrega) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $clase_badge ?>">
                                        <?= htmlspecialchars($pedido['estado']) ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <span class="text-success">
                                        Abonó: $<?= number_format($abono_real, 0, ',', '.') ?>
                                    </span><br>
                                    <?php if ($saldo_real > 0): ?>
                                        <span class="text-danger">
                                            Debe: $<?= number_format($saldo_real, 0, ',', '.') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-success">✅ Pagado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <ul class="lista-detalles">
                                        <?php foreach($detalles as $det): ?>
                                            <li>
                                                <strong>(x<?= intval($det['cantidad']) ?>)</strong>
                                                <?= htmlspecialchars($det['nombre'] ?? 'Prenda') ?>
                                                <span class="text-small">
                                                    Talla: <?= htmlspecialchars($det['talla']) ?> | 
                                                    Color: <?= htmlspecialchars($det['color']) ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td class="text-center">
                                    <form action="../controllers/cambiar_estado_pedido.php" method="POST" class="form-estado">
                                        <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                        <select name="nuevo_estado" class="select-estado" onchange="this.form.submit()">
                                            <option value="">-- Avanzar --</option>
                                            <option value="En Corte">✂️ Mover a Corte</option>
                                            <option value="En Confección">🪡 Mover a Costura</option>
                                            <option value="En Acabado">✨ Mover a Acabado</option>
                                            <option value="Terminado">📦 ¡Ir a Tienda!</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </main>
</div>

<style>
/* ============================================
   PANEL DE PRODUCCIÓN - ESTILOS SIMPLIFICADOS
   ============================================ */

/* Encabezado */
.page-header {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.page-header h1 {
    color: #1e293b;
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0;
}

.page-header p {
    color: #64748b;
    margin: 5px 0 0 0;
    font-size: 0.95rem;
}

/* Alerta de error */
.alert-error {
    padding: 12px 16px;
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}

/* Grid de estadísticas */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
    font-size: 2rem;
    font-weight: bold;
    color: #1e293b;
}

.stat-warning { border-left-color: #f59e0b; }
.stat-blue { border-left-color: #3b82f6; }
.stat-green { border-left-color: #10b981; }

/* Estado vacío */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.empty-icon {
    font-size: 3.5rem;
    display: block;
    margin-bottom: 15px;
}

.empty-state h2 {
    color: #1e293b;
    margin-bottom: 8px;
    font-size: 1.4rem;
}

.empty-state p {
    color: #64748b;
    max-width: 500px;
    margin: 0 auto 20px;
    line-height: 1.5;
}

/* Tabla */
.table-container {
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
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

.text-right { text-align: right; }
.text-center { text-align: center; }

/* Fila vencida */
.fila-vencida {
    background: #fef2f2 !important;
}

.fila-vencida:hover {
    background: #fee2e2 !important;
}

/* Fechas */
.fecha-vencida {
    color: #dc2626;
    font-weight: bold;
    font-size: 0.95rem;
}

.fecha-normal {
    color: #059669;
    font-size: 0.95rem;
}

.text-small {
    font-size: 0.8rem;
    color: #64748b;
}

.text-success {
    color: #059669;
    font-weight: 500;
    font-size: 0.85rem;
}

.text-danger {
    color: #dc2626;
    font-weight: bold;
    font-size: 0.85rem;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

/* Lista de detalles */
.lista-detalles {
    list-style: none;
    padding: 0;
    margin: 0;
    font-size: 0.85rem;
}

.lista-detalles li {
    margin-bottom: 6px;
    padding: 4px 0;
    border-bottom: 1px dashed #e2e8f0;
}

.lista-detalles li:last-child {
    border-bottom: none;
}

.lista-detalles .text-small {
    display: block;
    margin-top: 2px;
}

/* Select de cambio de estado */
.form-estado {
    display: inline-block;
    margin: 0;
}

.select-estado {
    padding: 6px 10px;
    font-size: 0.85rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 160px;
}

.select-estado:hover {
    border-color: #2563eb;
}

.select-estado:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .data-table {
        font-size: 0.85rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 10px 8px;
    }
    
    .select-estado {
        width: 100%;
        min-width: auto;
    }
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>