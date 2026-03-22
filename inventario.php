<?php
// 1. INCLUSIONES
include("header.php");
include("connection.php"); 

if (!isset($_SESSION['username'])) {
    echo "<script>window.location='index.php';</script>";
    exit();
}

// 2. CONSULTAS
$total_prod = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as t FROM productos"))['t'] ?? 0;
$stock_bajo = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as b FROM productos WHERE stock > 0 AND stock <= 5"))['b'] ?? 0;
$agotados   = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as a FROM productos WHERE stock = 0"))['a'] ?? 0;
$query = mysqli_query($conn, "SELECT * FROM productos");
?>

<div class="container admin-layout">
    
    <aside class="sidebar-panel">
        <div class="sidebar-section">
            <h3>🔍 Buscador</h3>
            <input type="text" id="inputBusqueda" placeholder="Buscar producto..." class="sidebar-input">
        </div>

        <div class="sidebar-section">
            <h3>📊 Resumen Stock</h3>
            <div class="stat-box">Total: <strong><?= $total_prod ?></strong></div>
            <div class="stat-box warning">Bajo Stock: <strong><?= $stock_bajo ?></strong></div>
            <div class="stat-box danger">Agotados: <strong><?= $agotados ?></strong></div>
        </div>

        <div class="sidebar-section">
            <h3>⚙️ Acciones</h3>
            <?php if($_SESSION['role'] == 'admin'): ?>
                <a href="productos_nuevo.php" class="btn-sidebar-action">+ Nueva Prenda</a>
            <?php endif; ?>
        </div>
    </aside>

    <main class="main-content-panel">
        <div class="header-acciones">
            <h1>Inventario Unideportes</h1>
        </div>

        <table class="tabla-maestra">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Talla</th>
                    <th>Estado</th>
                    <th>Precio</th>
                    <th>Acciones</th>
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
                            <a href="editar_prod.php?id=<?= $row['id'] ?>" class="btn-action edit" title="Editar">✎</a>
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

<script>
// El buscador funcional que apunta al nuevo ID
document.getElementById('inputBusqueda').addEventListener('keyup', function() {
    let filtro = this.value.toLowerCase();
    let filas = document.querySelectorAll('#tablaProductos tr');
    filas.forEach(fila => {
        let texto = fila.innerText.toLowerCase();
        fila.style.display = texto.includes(filtro) ? "" : "none";
    });
});
</script>