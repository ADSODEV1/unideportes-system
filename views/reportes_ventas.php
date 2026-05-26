<?php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();

require_login(['admin', 'vendedor', 'colaborador']);

// 2. PROCESAR FILTROS
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';
$filtro_vendedor = $_GET['vendedor'] ?? '';
$filtro_cliente = $_GET['cliente'] ?? '';

$where_parts = [];
$params = [];
if ($filtro_fecha_desde) {
    $where_parts[] = "DATE(v.fecha_venta) >= ?";
    $params[] = $filtro_fecha_desde;
}
if ($filtro_fecha_hasta) {
    $where_parts[] = "DATE(v.fecha_venta) <= ?";
    $params[] = $filtro_fecha_hasta;
}
if ($filtro_vendedor) {
    $where_parts[] = "u.id = ?";
    $params[] = intval($filtro_vendedor);
}
if ($filtro_cliente) {
    $where_parts[] = "c.id = ?";
    $params[] = intval($filtro_cliente);
}

$where_clause = !empty($where_parts) ? "WHERE " . implode(" AND ", $where_parts) : "";

$sql = "SELECT v.id, v.total_venta, v.fecha_venta, c.id AS cliente_id, c.nombre_completo AS cliente, u.id AS vendedor_id, u.username AS vendedor
        FROM ventas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        INNER JOIN usuarios u ON v.vendedor_id = u.id
        $where_clause
        ORDER BY v.fecha_venta DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$datos_ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_ventas = 0;
$cantidad_transacciones = count($datos_ventas);
foreach ($datos_ventas as $row) {
    $total_ventas += $row['total_venta'];
}

$res_vendedores = $pdo->query("SELECT DISTINCT u.id, u.username FROM usuarios u 
                                        INNER JOIN ventas v ON u.id = v.vendedor_id 
                                        ORDER BY u.username")->fetchAll(PDO::FETCH_ASSOC);
$res_clientes = $pdo->query("SELECT DISTINCT c.id, c.nombre_completo FROM clientes c 
                                      INNER JOIN ventas v ON c.id = v.cliente_id 
                                      ORDER BY c.nombre_completo")->fetchAll(PDO::FETCH_ASSOC);

$stmtDetalles = $pdo->prepare("SELECT dv.cantidad, dv.precio_unitario, dv.subtotal, p.nombre, p.referencia
                                        FROM detalle_venta dv
                                        INNER JOIN productos p ON dv.producto_id = p.id
                                        WHERE dv.venta_id = ?");

include(__DIR__ . "/header.php");
?>

<div class="reporte-page">
    <div class="reporte-header">
        <div>
            <h1> Reporte de Ventas</h1>
            <p>Balance económico y detalle de transacciones.</p>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="filtros-section">
        <h3> Filtros</h3>
        <form method="GET" class="filtros-form">
            <div class="filtro-grupo">
                <label>Desde:</label>
                <input type="date" name="fecha_desde" value="<?= htmlspecialchars($filtro_fecha_desde) ?>">
            </div>

            <div class="filtro-grupo">
                <label>Hasta:</label>
                <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($filtro_fecha_hasta) ?>">
            </div>

            <div class="filtro-grupo">
                <label>Vendedor:</label>
                <select name="vendedor">
                    <option value="">Todos</option>
                    <?php foreach ($res_vendedores as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= ($filtro_vendedor == $v['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filtro-grupo">
                <label>Cliente:</label>
                <select name="cliente">
                    <option value="">Todos</option>
                    <?php foreach ($res_clientes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($filtro_cliente == $c['id']) ? 'selected' : '' ?>
                            <?= htmlspecialchars($c['nombre_completo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-filtrar"> Filtrar</button>
            <a href="reportes_ventas.php" class="btn-limpiar"> Limpiar</a>
        </form>
    </div>

    <!-- RESUMEN -->
    <div class="resumen-totales">
        <div class="total-card">
            <p>Transacciones</p>
            <h2><?= $cantidad_transacciones ?></h2>
        </div>
        <div class="total-card">
            <p>Venta Total</p>
            <h2>$<?= number_format($total_ventas, 2, '.', ',') ?></h2>
        </div>
        <div class="total-card">
            <p>Promedio por Venta</p>
            <h2>$<?= number_format($cantidad_transacciones > 0 ? $total_ventas / $cantidad_transacciones : 0, 2, '.', ',') ?></h2>
        </div>
    </div>

    <!-- TABLA PRINCIPAL -->
    <section class="users-table">
        <table id="tablasVentas" class="tabla-reportes">
            <thead>
                <tr>
                    <th>ID Venta</th>
                    <th>Fecha / Hora</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th class="text-right">Total</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datos_ventas as $venta): ?>
                <tr>
                    <td><strong>#<?= str_pad($venta['id'], 5, "0", STR_PAD_LEFT) ?></strong></td>
                    <td><?= date("d/m/Y H:i", strtotime($venta['fecha_venta'])) ?></td>
                    <td><?= htmlspecialchars($venta['cliente']) ?></td>
                    <td><span class="user-tag"><?= htmlspecialchars(ucfirst($venta['vendedor'])) ?></span></td>
                    <td class="text-right total-bold">$<?= number_format($venta['total_venta'], 2, '.', ',') ?></td>
                    <td>
                        <button type="button" class="toggle-detalle-venta" data-venta-id="<?= $venta['id'] ?>"> Ver</button>
                    </td>
                </tr>
                <!-- Fila expandible de detalles -->
                <tr id="detalle-venta-<?= $venta['id'] ?>" class="detalle-venta-row">
                    <td colspan="6">
                        <div class="detalle-venta-content">
                            <h4>Productos vendidos:</h4>
                            <table class="tabla-detalles-interna">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Referencia</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unit.</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmtDetalles->execute([$venta['id']]);
                                    $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($detalles as $detalle):
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($detalle['nombre']) ?></td>
                                            <td><?= htmlspecialchars($detalle['referencia']) ?></td>
                                            <td><?= $detalle['cantidad'] ?></td>
                                            <td>$<?= number_format($detalle['precio_unitario'], 2, '.', ',') ?></td>
                                            <td>$<?= number_format($detalle['subtotal'], 2, '.', ',') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($cantidad_transacciones == 0): ?>
            <div class="sin-resultados">
                <p>No hay ventas registradas con los filtros seleccionados.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- ACCIONES -->
    <div class="acciones-reporte">
        <button onclick="window.print()" class="btn-imprimir"> Imprimir / PDF</button>
        <button onclick="exportarCSV()" class="btn-exportar"> Descargar CSV</button>
    </div>
</div>

<style>
.reporte-page {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 15px;
}

.filtros-section {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.filtros-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.filtro-grupo {
    display: flex;
    flex-direction: column;
}

.filtro-grupo label {
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 0.95rem;
}

.filtro-grupo input,
.filtro-grupo select {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
}

.btn-filtrar,
.btn-limpiar {
    align-self: flex-end;
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.btn-filtrar {
    background: var(--primary);
    color: white;
}

.btn-limpiar {
    background: #9ca3af;
    color: white;
}

.resumen-totales {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.total-card {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid var(--primary);
}

.total-card p {
    margin: 0 0 10px 0;
    color: #6B7280;
    font-weight: 600;
}

.total-card h2 {
    margin: 0;
    color: var(--primary);
    font-size: 1.8rem;
}

.tabla-reportes {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.tabla-reportes th {
    background: #1A2B4C;
    color: #fff;
    padding: 15px;
    text-align: left;
}

.tabla-reportes td {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.detalle-venta-row {
    display: none;
    background: #f8fafc;
}

.detalle-venta-row.abierto {
    display: table-row;
}

.detalle-venta-content {
    padding: 15px;
    background: #eef2f7;
    border-radius: 10px;
}

.tabla-detalles-interna {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.tabla-detalles-interna th {
    background: #1A2B4C;
    color: #fff;
    padding: 10px;
    font-size: 0.9rem;
}

.tabla-detalles-interna td {
    padding: 10px;
    border-bottom: 1px solid #d1d5db;
}

.toggle-detalle-venta {
    padding: 6px 12px;
    background: #0ea5e9;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.toggle-detalle-venta:hover {
    background: #0284c7;
}

.sin-resultados {
    text-align: center;
    padding: 30px;
    color: #6B7280;
}

.acciones-reporte {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.btn-imprimir,
.btn-exportar {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.btn-imprimir {
    background: var(--primary);
    color: white;
}

.btn-exportar {
    background: #10b981;
    color: white;
}

@media print {
    .filtros-section,
    .acciones-reporte {
        display: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-detalle-venta').forEach(btn => {
        btn.addEventListener('click', function() {
            const ventaId = this.dataset.ventaId;
            const detalleRow = document.getElementById('detalle-venta-' + ventaId);
            if (detalleRow) {
                detalleRow.classList.toggle('abierto');
                this.textContent = detalleRow.classList.contains('abierto') ? '✓ Cerrar' : '👁️ Ver';
            }
        });
    });
});

function exportarCSV() {
    const tabla = document.getElementById('tablasVentas');
    let csv = 'ID Venta,Fecha,Cliente,Vendedor,Total\n';
    
    tabla.querySelectorAll('tbody > tr:not(.detalle-venta-row)').forEach(row => {
        const celdas = row.querySelectorAll('td');
        csv += `${celdas[0].textContent},${celdas[1].textContent},${celdas[2].textContent},${celdas[3].textContent},${celdas[4].textContent}\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'reporte_ventas.csv';
    a.click();
}
</script>

<?php include(__DIR__ . "/footer.php"); ?>
