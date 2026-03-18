<?php
session_start();
include("connection.php");
$conn = connection();

// SEGURIDAD: Solo Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php?error=acceso_denegado");
    exit();
}

include("header.php");
?>

<div class="form-container">
    <h2>🆕 Registrar Nueva Prenda</h2>
    
    <?php if(isset($_GET['error'])) echo "<p style='color:red;'>⚠️ Error al registrar. Revisa los datos.</p>"; ?>

    <form action="registrar_productos.php" method="POST">
        <label>Nombre del Producto:</label>
        <input type="text" name="nombre" required placeholder="Ej: Uniforme Shorin-ryu">

        <label>Referencia:</label>
        <input type="text" name="referencia" required placeholder="REF-001">

        <label>Talla:</label>
        <select name="talla">
            <option value="S">S</option>
            <option value="M">M</option>
            <option value="L">L</option>
            <option value="XL">XL</option>
        </select>

        <label>Stock Inicial:</label>
        <input type="number" name="stock" min="0" value="0">

        <label>Precio ($):</label>
        <input type="number" name="precio" step="0.01" required>

        <button type="submit" class="btn-guardar">Guardar Producto</button>
        <a href="inventario.php">Cancelar</a>
    </form>
</div>

<?php include("footer.php"); ?>