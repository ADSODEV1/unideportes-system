<?php
session_start();
include("connection.php");

// 1. SEGURIDAD: Solo usuarios logueados pueden vender
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// 2. CONSULTA DE PRODUCTOS: Para llenar el selector (Select)
$res_productos = mysqli_query($conn, "SELECT id, nombre_prod, precio, stock FROM productos WHERE stock > 0");

include("header.php");
?>

<div class="venta-container">
    <h1>🛒 Nueva Venta</h1>
    <p>Registrar salida de mercancía - Unideportes</p>

    <form action="procesar_venta.php" method="POST" class="users-form">
        
        <label>Cliente:</label>
        <input type="text" name="cliente" placeholder="Nombre del cliente" required>

        <label>Producto en Stock:</label>
        <select name="id_producto" required>
            <option value="">-- Seleccione un artículo --</option>
            <?php while($prod = mysqli_fetch_array($res_productos)): ?>
                <option value="<?= $prod['id'] ?>">
                    <?= $prod['nombre_prod'] ?> - $<?= $prod['precio'] ?> (Disponibles: <?= $prod['stock'] ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <label>Cantidad a vender:</label>
        <input type="number" name="cantidad" min="1" value="1" required>

        <br>
        <button type="submit" class="users-table--edit">
            ✅ PROCESAR VENTA
        </button>
    </form>

    <br>
    <p><strong>Nota:</strong> Al procesar, el sistema restará automáticamente las unidades del inventario.</p>
    
    <a href="panel_vendedor.php">Volver al Panel</a>
</div>

<?php include("footer.php"); ?>