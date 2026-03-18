<?php
session_start();
include("connection.php");
$conn = connection();

// 1. SEGURIDAD: Solo Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php?error=acceso_denegado");
    exit();
}

include("header.php");

// 2. CONSULTA AVANZADA (Conexión de 3 tablas)
$sql = "SELECT v.id, v.total_venta, v.fecha_venta, c.nombre_completo AS cliente, u.username AS vendedor 
        FROM ventas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        INNER JOIN usuarios u ON v.vendedor_id = u.id
        ORDER BY v.fecha_venta DESC";

$query = mysqli_query($conn, $sql);

if (!$query) {
    die("Error en la base de datos: " . mysqli_error($conn));
}

// 3. KPI: El dinero total
$sql_total = "SELECT SUM(total_venta) as gran_total FROM ventas";
$res_total = mysqli_query($conn, $sql_total);
$dato_total = mysqli_fetch_array($res_total);
$gran_total = $dato_total['gran_total'] ?? 0;
?>

<div class="reporte-page">
    <div class="reporte-header">
        <div>
            <h1>📊 Reporte Global de Ventas</h1>
            <p>Balance económico de Unideportes.</p>
        </div>
        
        <div class="kpi-card-total">
            <small>INGRESOS TOTALES</small>
            <h2>$ <?= number_format($gran_total, 0, ',', '.') ?></h2>
        </div>
    </div>

    <section class="users-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha / Hora</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_array($query)): ?>
                <tr>
                    <td>#<?= str_pad($row['id'], 4, "0", STR_PAD_LEFT) ?></td>
                    <td><?= date("d/m/Y H:i", strtotime($row['fecha_venta'])) ?></td>
                    <td><?= $row['cliente'] ?></td>
                    <td><span class="user-tag"><?= ucfirst($row['vendedor']) ?></span></td>
                    <td class="text-right total-bold">$ <?= number_format($row['total_venta'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>

    <div class="imprimir-area">
        <button onclick="window.print()" class="btn-print">🖨️ Generar PDF</button>
    </div>
</div>

<?php include("footer.php"); 
?>