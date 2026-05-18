<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ProductoModel.php';

require_login();
$conn = app();
include(__DIR__ . "/../views/header.php");

$search = trim($_GET['q'] ?? '');
$productos = obtenerProductos($conn, $search);

$total_prod = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as t FROM productos"))['t'] ?? 0;
$stock_bajo = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as b FROM productos WHERE stock > 0 AND stock <= 5"))['b'] ?? 0;
$agotados   = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as a FROM productos WHERE stock = 0"))['a'] ?? 0;

$success = '';
if (!empty($_GET['success']) && $_GET['success'] === 'producto_registrado') {
    $success = 'Producto registrado exitosamente.';
} elseif (!empty($_GET['success']) && $_GET['success'] === 'producto_actualizado') {
    $success = 'Producto actualizado correctamente.';
}

$sidebarExtra = '<div class="sidebar-section"><h3>🔍 Buscar producto</h3><form method="GET" action="inventario.php"><input type="search" name="q" value="' . htmlspecialchars($search) . '" placeholder="Nombre o referencia" class="sidebar-input" autocomplete="off"><button type="submit" class="btn-secondary" style="margin-top:10px; width:100%;">Buscar</button></form></div><div class="sidebar-section"><h3>Resumen Stock</h3><div class="stat-box">Total: <strong>'. $total_prod .'</strong></div><div class="stat-box warning">Bajo Stock: <strong>'. $stock_bajo .'</strong></div><div class="stat-box danger">Agotados: <strong>'. $agotados .'</strong></div></div>';
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/../views/sidebar_control.php"); ?>

    <main class="main-content-panel">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

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
                <?php if (count($productos) > 0): ?>
                    <?php foreach ($productos as $row): ?>
                        <tr>
                            <td style="text-align: left;">
                                <strong><?= htmlspecialchars($row['nombre']) ?></strong><br>
                                <small style="color: #666;">Ref: <?= htmlspecialchars($row['referencia']) ?></small>
                            </td>
                            <td><span class="talla-badge"><?= htmlspecialchars($row['talla']) ?></span></td>
                            <td>
                                <?php 
                                    $s = $row['stock'];
                                    if ($s == 0) echo "<span class='badge rojo'>AGOTADO</span>";
                                    elseif ($s <= 5) echo "<span class='badge naranja'>BAJO ($s)</span>";
                                    else echo "<span class='badge verde'>STOCK ($s)</span>";
                                ?>
                            </td>
                            <td>$<?= number_format($row['precio'], 0, ',', '.') ?></td>
                            <td>
                                <a href="detalle_prod.php?id=<?= $row['id'] ?>" class="btn-action view" title="Ver detalle">🔎</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color: #888; padding: 30px;">No hay productos para mostrar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

<footer class="main-footer">
    <div class="container">
        <p>&copy; <?php echo date("Y"); ?> Unideportes - Sistema de Gestión Interno</p>
    </div>
</footer>
