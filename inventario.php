<?php
session_start();
include("connection.php");

// 1. SEGURIDAD
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// 2. CONSULTAS DE RESUMEN (KPIs)
$total_prod = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as t FROM productos"))['t'];
$stock_bajo = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as b FROM productos WHERE stock > 0 AND stock <= 5"))['b'];
$agotados   = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as a FROM productos WHERE stock = 0"))['a'];

// 3. OBTENER LISTADO
$query = mysqli_query($conn, "SELECT * FROM productos");

include("header.php");
?>

<div class="inventario-page">
    <div class="header-acciones">
        <h1>Inventario Unideportes</h1>
        <?php if($_SESSION['role'] == 'admin'): ?>
            <a href="productos_nuevo.php" class="btn-principal">+ Nueva Prenda</a>
        <?php endif; ?>
    </div>

    <div class="resumen-grid">
        <div class="caja">
            <small>REFERENCIAS</small>
            <h3><?= $total_prod ?></h3>
        </div>
        <div class="caja alerta">
            <small>STOCK BAJO</small>
            <h3><?= $stock_bajo ?></h3>
        </div>
        <div class="caja critico">
            <small>AGOTADOS</small>
            <h3><?= $agotados ?></h3>
        </div>
    </div>

    <div class="buscador-bar">
        <input type="text" id="inputBusqueda" placeholder="🔍 Buscar producto, talla o referencia...">
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
                <td>
                    <strong><?= $row['nombre'] ?></strong><br>
                    <small>Ref: <?= $row['referencia'] ?></small>
                </td>
                <td><?= $row['talla'] ?></td>
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
    <a href="detalle_prod.php?id=<?= $row['id'] ?>" class="btn-icono ver">🔎</a>

    <?php if($_SESSION['role'] == 'admin'): ?>
        <a href="editar_prod.php?id=<?= $row['id'] ?>" class="btn-icono editar">✎</a>
    <?php endif; ?>
</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
// El buscador 
document.getElementById('inputBusqueda').addEventListener('keyup', function() {
    let filtro = this.value.toLowerCase();
    let filas = document.querySelectorAll('#tablaProductos tr');

    filas.forEach(fila => {
        let texto = fila.innerText.toLowerCase();
        fila.style.display = texto.includes(filtro) ? "" : "none";
    });
});
</script>

<?php include("footer.php"); 
?>