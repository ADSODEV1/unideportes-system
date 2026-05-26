<?php
// reportes_ventas.php
// Conexión interna (Ajusta según tu arquitectura, ej: include '../config/conexion.php')
try {
    $pdo = new PDO("mysql:host=localhost;dbname=unideportes;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Unideportes - Reporte de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
        <div>
            <h2 class="text-dark mb-1"><i class="bi bi-bar-chart-line-fill text-danger"></i> Reportes de Ventas</h2>
            <p class="text-muted mb-0">Análisis del rendimiento comercial y balance de caja de Unideportes.</p>
        </div>
        <div>
            <span class="badge bg-primary fs-6">Filtro Activo: <?php echo $fecha_inicio; ?> a <?php echo $fecha_fin; ?></span>
        </div>
    </div>

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Fecha Inicial</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Fecha Final</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-danger"><i class="bi bi-filter"></i> Filtrar Reporte</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase opacity-75">Total Ingresos</h6>
                        <h3 class="mb-0">$<?php echo number_format($kpis['total_ingresos'] ?? 0, 0, ',', '.'); ?></h3>
                    </div>
                    <i class="bi bi-currency-dollar fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase opacity-75">Transacciones</h6>
                        <h3 class="mb-0"><?php echo $kpis['total_transacciones']; ?> Facturas</h3>
                    </div>
                    <i class="bi bi-receipt fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase opacity-75">Ticket Promedio</h6>
                        <h3 class="mb-0">$<?php echo number_format($kpis['promedio_ticket'] ?? 0, 0, ',', '.'); ?></h3>
                    </div>
                    <i class="bi bi-calculator fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-5 mb-4 mb-md-0">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 fw-bold"><i class="bi bi-pie-chart"></i> Resumen Métodos de Pago</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php if(empty($reporte_metodos)): ?>
                            <li class="list-group-item text-center text-muted">No hay registros en este periodo</li>
                        <?php endif; ?>
                        <?php foreach($reporte_metodos as $metodo): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <span class="fw-bold"><?php echo $metodo['metodo_pago']; ?></span>
                                    <small class="text-muted d-block"><?php echo $metodo['cantidad']; ?> operaciones</small>
                                </div>
                                <span class="badge bg-secondary rounded-pill">$<?php echo number_format($metodo['total'], 0, ',', '.'); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 fw-bold"><i class="bi bi-star-fill text-warning"></i> Top 5 Productos Más Vendidos</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>Ref</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Recaudado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($top_productos)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-3">Sin datos de ventas de catálogo</td></tr>
                                <?php endif; ?>
                                <?php foreach($top_productos as $prod): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo $prod['nombre']; ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo $prod['referencia']; ?></span></td>
                                    <td class="text-center fw-bold"><?php echo $prod['total_vendido']; ?></td>
                                    <td class="text-end text-success fw-bold">$<?php echo number_format($prod['total_recaudado'], 0, ',', '.'); ?></td>
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
        <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-task text-danger"></i> Bitácora de Movimientos del Periodo</span>
            <button onclick="window.print();" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> Imprimir Informe</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nº Factura</th>
                            <th>Fecha/Hora</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Método</th>
                            <th>Entrega</th>
                            <th class="text-end">Total Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($ventas_detalladas)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Ninguna transacción efectuada en las fechas seleccionadas.</td></tr>
                        <?php endif; ?>
                        <?php foreach($ventas_detalladas as $v): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?php echo $v['ticket_numero'] ?? 'Directa POS (ID: '.$v['id'].')'; ?></td>
                            <td><?php echo date('d/m/Y g:i a', strtotime($v['fecha_venta'])); ?></td>
                            <td><?php echo $v['cliente']; ?></td>
                            <td><span class="badge bg-light text-secondary"><?php echo $v['vendedor']; ?></span></td>
                            <td><?php echo $v['metodo_pago']; ?></td>
                            <td>
                                <span class="badge <?php echo $v['tipo_entrega'] == 'Domicilio' ? 'bg-info text-dark' : 'bg-light text-dark'; ?>">
                                    <?php echo $v['tipo_entrega']; ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold">$<?php echo number_format($v['total_venta'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>