<?php
session_start();
include("connection.php");
$conn = connection();

// 1. SEGURIDAD: Acceso exclusivo para directivos (Admin)
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php?error=acceso_denegado");
    exit();
}

include("header.php");

// 2. CONSULTA AVANZADA
// He cambiado v.fecha por v.fecha_venta para solucionar el error
$sql = "SELECT v.id, v.total_venta, v.fecha_venta, c.nombre_completo AS cliente, u.username AS vendedor 
        FROM ventas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        INNER JOIN usuarios u ON v.vendedor_id = u.id
        ORDER BY v.fecha_venta DESC";

$query = mysqli_query($conn, $sql);

// Si sigue fallando, esto nos dirá si el problema es el nombre de otra columna
if (!$query) {
    die("Error en la base de datos: " . mysqli_error($conn));
}

$query = mysqli_query($conn, $sql);

// 3. KPI: Cálculo del Ingreso Total Acumulado
$sql_total = "SELECT SUM(total_venta) as gran_total FROM ventas";
$res_total = mysqli_query($conn, $sql_total);
$dato_total = mysqli_fetch_array($res_total);
$gran_total = $dato_total['gran_total'] ?? 0;
?>

<div class="container py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold" style="color: #1A2B4C;">📊 Reporte Global de Ventas</h2>
            <p class="text-muted">Balance económico y rendimiento de la fuerza de ventas.</p>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 text-center" style="background-color: #E61E2A; border-radius: 15px;">
                <small class="text-white opacity-75 fw-bold text-uppercase">Ingresos Totales</small>
                <h3 class="text-white fw-bold mb-0">$ <?= number_format($gran_total, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>

    <div class="card shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background-color: #1A2B4C; color: white;">
                    <tr class="text-uppercase small">
                        <th class="p-4">ID Transacción</th>
                        <th class="p-4">Fecha / Hora</th>
                        <th class="p-4">Cliente</th>
                        <th class="p-4 text-center">Vendedor</th>
                        <th class="p-4 text-end">Monto Total</th>
                    </tr>
                </thead>
                <tbody style="color: #333333;">
                    <?php while($row = mysqli_fetch_array($query)): ?>
                    <tr>
                        <td class="p-4 fw-bold text-muted">#INV-<?= str_pad($row['id'], 4, "0", STR_PAD_LEFT) ?></td>
                        <td class="p-4"><?= date("d/m/Y H:i", strtotime($row['fecha_venta'])) ?></td>
                        <td class="p-4"><?= $row['cliente'] ?></td>
                        <td class="p-4 text-center">
                             <span class="badge bg-light text-dark border px-3">👤 <?= ucfirst($row['vendedor']) ?></span>
                        </td>
                        <td class="p-4 text-end fw-bold text-success" style="font-size: 1.1rem;">
                            $ <?= number_format($row['total_venta'], 0, ',', '.') ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center mt-4">
        <button onclick="window.print()" class="btn btn-dark px-5 py-2 fw-bold shadow-sm" style="border-radius: 12px;">
            🖨️ GENERAR INFORME IMPRESO (PDF)
        </button>
    </div>
</div>

<?php include("footer.php"); ?>