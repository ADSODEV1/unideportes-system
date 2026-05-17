<?php
// 1. INCLUSIONES
require_once __DIR__ . '/../config/connection.php';
$conn = connection();
include(__DIR__ . "/../views/header.php");

if (!isset($_SESSION['username'])) {
    header("Location: ../public/index.php");
    exit();
}

// 2. CONSULTAS
$total_prod = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as t FROM productos"))['t'] ?? 0;
$stock_bajo = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as b FROM productos WHERE stock > 0 AND stock <= 5"))['b'] ?? 0;
$agotados   = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as a FROM productos WHERE stock = 0"))['a'] ?? 0;

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $like = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT * FROM productos WHERE nombre LIKE ? OR referencia LIKE ? ORDER BY nombre ASC");
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $query = $stmt->get_result();
} else {
    $query = mysqli_query($conn, "SELECT * FROM productos ORDER BY nombre ASC");
}

$sidebarExtra = '<div class="sidebar-section"><h3>🔍 Buscar producto</h3><form method="GET" action="inventario.php"><input type="search" name="q" value="' . htmlspecialchars($search) . '" placeholder="Nombre o referencia" class="sidebar-input" autocomplete="off"><button type="submit" class="btn-secondary" style="margin-top:10px; width:100%;">Buscar</button></form></div><div class="sidebar-section"><h3>Resumen Stock</h3><div class="stat-box">Total: <strong>'. $total_prod .'</strong></div><div class="stat-box warning">Bajo Stock: <strong>'. $stock_bajo .'</strong></div><div class="stat-box danger">Agotados: <strong>'. $agotados .'</strong></div></div>';
?>

<div class="container admin-layout">
    
    <?php include(__DIR__ . "/../views/sidebar_control.php"); ?>

    <main class="main-content-panel">
        <div class="header-carapteristicas">
            <h1>Inventario Unideportes</h1>
        </div>

        <table class="tabla-maestra">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Talla</th>
                    <th>Estado</th>
                    <th>Precio</th>
                    <th>Características</th>
                </tr>
            </thead>
            <tbody id="tablaProductos">
                <?php while($row = mysqli_fetch_array($query)): ?>
                <tr>
                    <td style="text-align: left;">
                        <strong><?= $row['nombre'] ?></strong><br>
                        <small style="color: #666;">Ref: <?= $row['referencia'] ?></small>
                    </td>
                    <td><span class="talla-badge"><?= $row['talla'] ?></span></td>
                    <td>
                        <?php 
                            $s = $row['stock'];
                            if($s == 0) echo "<span class='badge rojo'>AGOTADO</span>";
                            elseif($s <= 5) echo "<span class='badge naranja'>BAJO ($s)</span>";
                            else echo "<span class='badge verde'>STOCK ($s)</span>";
                        ?>
                    </td>
                    <td>$<?= number_format($row['precio'], 0, ',', '.') ?></td>
                    <td>
                        <a href="detalle_prod.php?id=<?= $row['id'] ?>" class="btn-action view" title="Ver detalle">🔎</a>
                        <?php if($_SESSION['role'] == 'admin'): ?>
                           
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

<footer class="main-footer">
    <div class="container">
        <p>&copy; <?php echo date("Y"); ?> Unideportes - Sistema de Gestión Interno</p>
    </div>
</footer>
