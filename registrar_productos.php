<?php
session_start();
include("connection.php");
$conn = connection();

// 1. SEGURIDAD
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php?error=acceso_denegado");
    exit();
}

// 2. RECIBIR DATOS
if ($_POST) {
    $nombre     = mysqli_real_escape_string($conn, $_POST['nombre']);
    $referencia = mysqli_real_escape_string($conn, $_POST['referencia']);
    $talla      = $_POST['talla'];
    $stock      = intval($_POST['stock']);
    $precio     = floatval($_POST['precio']);

    // 3. SQL
    $sql = "INSERT INTO productos (nombre, referencia, talla, stock, precio) 
            VALUES ('$nombre', '$referencia', '$talla', '$stock', '$precio')";

    // 4. EJECUTAR
    if (mysqli_query($conn, $sql)) {
        header("Location: inventario.php?success=1");
    } else {
        // Si falla, regresa al formulario (no a este mismo archivo)
        header("Location: productos_nuevo.php?error=fallo");
    }
} else {
    header("Location: productos_nuevo.php");
}
mysqli_close($conn);
?>