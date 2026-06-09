<?php
session_start();
require_once __DIR__ . '/../config/connection.php';
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

        <label>Categoría:</label>
        <input type="text" name="categoria" placeholder="Camisetas, Pantalonetas, Accesorios...">

        <label>Color:</label>
        <input type="text" name="color" placeholder="Ej: Azul, Rojo, Verde">

        <label>Material:</label>
        <input type="text" name="material" placeholder="Ej: Algodón, Poliéster">

        <label>Género:</label>
        <select name="genero">
            <option value="Unisex">Unisex</option>
            <option value="Hombre">Hombre</option>
            <option value="Mujer">Mujer</option>
        </select>

        <label>Estado:</label>
        <select name="estado">
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
        </select>

        <label>Descripción:</label>
        <textarea name="descripcion" placeholder="Descripción para vendedor y catálogo..." rows="3"></textarea>

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