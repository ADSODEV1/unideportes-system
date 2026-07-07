<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
$pdo = app();

// 1. CONTROL DE FILTROS (Por defecto el mes actual)
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t');

// 2. CONSULTA 1: KPIs Generales (Total ingresos, Total transacciones, Promedio por ticket)
$sql_kpis = "SELECT 
                SUM(total_venta) as total_ingresos, 
                COUNT(id) as total_transacciones,
                AVG(total_venta) as promedio_ticket
             FROM ventas 
             WHERE DATE(fecha_venta) BETWEEN :inicio AND :fin";
$stmt = $pdo->prepare($sql_kpis);
$stmt->execute(['inicio' => $fecha_inicio, 'fin' => $fecha_fin]);
$kpis = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. CONSULTA 2: Ventas agrupadas por Método de Pago
$sql_metodos = "SELECT metodo_pago, SUM(total_venta) as total, COUNT(id) as cantidad
                FROM ventas 
                WHERE DATE(fecha_venta) BETWEEN :inicio AND :fin
                GROUP BY metodo_pago";
$stmt_metodos = $pdo->prepare($sql_metodos);
$stmt_metodos->execute(['inicio' => $fecha_inicio, 'fin' => $fecha_fin]);
$reporte_metodos = $stmt_metodos->fetchAll(PDO::FETCH_ASSOC);

// 4. CONSULTA 3: Listado detallado de Ventas (Con joins a clientes y usuarios/vendedores)
$sql_detallado = "SELECT 
                    v.id,
                    v.ticket_numero,
                    v.fecha_venta,
                    c.nombre_completo as cliente,
                    u.username as vendedor,
                    v.metodo_pago,
                    v.tipo_entrega,
                    v.total_venta
                  FROM ventas v
                  INNER JOIN clientes c ON v.cliente_id = c.id
                  INNER JOIN usuarios u ON v.vendedor_id = u.id
                  WHERE DATE(v.fecha_venta) BETWEEN :inicio AND :fin
                  ORDER BY v.fecha_venta DESC";
$stmt_detallado = $pdo->prepare($sql_detallado);
$stmt_detallado->execute(['inicio' => $fecha_inicio, 'fin' => $fecha_fin]);
$ventas_detalladas = $stmt_detallado->fetchAll(PDO::FETCH_ASSOC);

// 5. CONSULTA 4: Top 5 Productos más vendidos (Calculando cantidad * precio_unitario como subtotal)
$sql_top_prod = "SELECT 
                    p.nombre, 
                    p.referencia, 
                    SUM(dv.cantidad) as total_vendido, 
                    SUM(dv.cantidad * dv.precio_unitario) as total_recaudado
                 FROM detalle_venta dv
                 INNER JOIN productos p ON dv.producto_id = p.id
                 INNER JOIN ventas v ON dv.venta_id = v.id
                 WHERE DATE(v.fecha_venta) BETWEEN :inicio AND :fin
                 GROUP BY p.id
                 ORDER BY total_vendido DESC
                 LIMIT 5";
$stmt_top = $pdo->prepare($sql_top_prod);
$stmt_top->execute(['inicio' => $fecha_inicio, 'fin' => $fecha_fin]);
$top_productos = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        <div class="content-header" style="margin-bottom: 20px; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 15px; align-items: flex-start;">
            <div>
                <h1>📈 Reportes de Ventas</h1>
                <p style="color: #64748b; margin-top: 8px; max-width: 640px;">Analiza ingresos, transacciones, métodos de pago y desempeño comercial del período seleccionado.</p>
            </div>
            <a href="panel_admin.php" class="btn-secondary" style="text-decoration: none; white-space: nowrap;">Volver al panel</a>
        </div>

        <div class="search-bar" style="margin-bottom: 25px; padding: 20px; background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; align-items: flex-end;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; width: 100%;">
                <div>
                    <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 6px;">Fecha inicial</label>
                    <input type="date" name="fecha_inicio" form="filtroVentas" value="<?= htmlspecialchars($fecha_inicio) ?>" style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background: white;">
                </div>
                <div>
                    <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 6px;">Fecha final</label>
                    <input type="date" name="fecha_fin" form="filtroVentas" value="<?= htmlspecialchars($fecha_fin) ?>" style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background: white;">
                </div>
            </div>
            <button type="submit" form="filtroVentas" class="btn-primary" style="margin-left: auto;">Filtrar reporte</button>
        </div>

        <form id="filtroVentas" method="GET" action="reportes_ventas.php"></form>

        <div class="row" style="gap: 20px; margin-bottom: 30px;">
            <div class="col-md-4" style="min-width: 250px;">
                <div class="card bg-success text-white border-0 shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase opacity-75">Total Ingresos</h6>
                            <h3 class="mb-0">$<?= number_format($kpis['total_ingresos'] ?? 0, 0, ',', '.'); ?></h3>
                        </div>
                        <span style="font-size: 2.1rem; opacity: 0.65;">💰</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="min-width: 250px;">
                <div class="card bg-primary text-white border-0 shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase opacity-75">Transacciones</h6>
                            <h3 class="mb-0"><?= number_format($kpis['total_transacciones'] ?? 0, 0, ',', '.'); ?> Facturas</h3>
                        </div>
                        <span style="font-size: 2.1rem; opacity: 0.65;">🧾</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="min-width: 250px;">
                <div class="card bg-warning text-dark border-0 shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase opacity-75">Ticket Promedio</h6>
                            <h3 class="mb-0">$<?= number_format($kpis['promedio_ticket'] ?? 0, 0, ',', '.'); ?></h3>
                        </div>
                        <span style="font-size: 2.1rem; opacity: 0.65;">📊</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" style="gap: 20px; margin-bottom: 30px;">
            <div class="col-md-5" style="min-width: 300px;">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold">Ingresos por método de pago</div>
                    <div class="card-body">
                        <p class="text-muted" style="font-size:0.95rem; margin-bottom: 12px;">Resumen de ventas agrupadas según la forma de cobro.</p>
                        <ul class="list-group list-group-flush">
                            <?php if (empty($reporte_metodos)): ?>
                                <li class="list-group-item text-center text-muted">No hay registros en este periodo</li>
                            <?php endif; ?>
                            <?php foreach ($reporte_metodos as $metodo): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <span class="fw-bold"><?= htmlspecialchars($metodo['metodo_pago']); ?></span>
                                        <small class="text-muted d-block"><?= htmlspecialchars($metodo['cantidad']); ?> operaciones</small>
                                    </div>
                                    <span class="badge bg-secondary rounded-pill">$<?= number_format($metodo['total'], 0, ',', '.'); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-7" style="min-width: 300px;">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold">Top 5 productos más vendidos</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Ref</th>
                                        <th class="text-center">Cant. vendida</th>
                                        <th class="text-end">Ingresos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_productos)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-3">Sin datos de ventas de catálogo</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($top_productos as $prod): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= htmlspecialchars($prod['nombre']); ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($prod['referencia']); ?></span></td>
                                            <td class="text-center fw-bold"><?= htmlspecialchars($prod['total_vendido']); ?></td>
                                            <td class="text-end text-success fw-bold">$<?= number_format($prod['total_recaudado'], 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 fw-bold">Ventas detalladas</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="tabla-maestra" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Vendedor</th>
                                <th>Método</th>
                                <th>Entrega</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ventas_detalladas)): ?>
                                <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding: 24px;">No hay ventas registradas en este rango de fechas.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($ventas_detalladas as $venta): ?>
                                <tr>
                                    <td><?= htmlspecialchars($venta['ticket_numero']); ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($venta['fecha_venta']))); ?></td>
                                    <td><?= htmlspecialchars($venta['cliente']); ?></td>
                                    <td><?= htmlspecialchars($venta['vendedor']); ?></td>
                                    <td><?= htmlspecialchars($venta['metodo_pago']); ?></td>
                                    <td><?= htmlspecialchars($venta['tipo_entrega']); ?></td>
                                    <td class="text-end">$<?= number_format($venta['total_venta'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include(__DIR__ . '/footer.php'); ?>

