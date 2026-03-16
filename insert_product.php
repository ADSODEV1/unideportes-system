<?php
session_start();
include("connection.php");
$conn = connection();

// 1. SEGURIDAD: Solo el admin puede ejecutar este proceso
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php?error=acceso_denegado");
    exit();
}

// 2. VERIFICAR QUE LOS DATOS LLEGUEN POR POST
if ($_POST) {
    // Limpiamos los datos para evitar errores de caracteres
    $nombre     = mysqli_real_escape_string($conn, $_POST['nombre']);
    $referencia = mysqli_real_escape_string($conn, $_POST['referencia']);
    $talla      = $_POST['talla'];
    $stock      = intval($_POST['stock']);
    $precio     = floatval($_POST['precio']);

    // 3. SENTENCIA SQL PARA INSERTAR
    // Asegúrate de que los nombres de las columnas coincidan con tu base de datos
    $sql = "INSERT INTO productos (nombre, referencia, talla, stock, precio) 
            VALUES ('$nombre', '$referencia', '$talla', '$stock', '$precio')";

    // 4. EJECUTAR Y REDIRECCIONAR
    if (mysqli_query($conn, $sql)) {
        // Si sale bien, volvemos al inventario con mensaje de éxito
        header("Location: inventario.php?success=producto_registrado");
    } else {
        // Si hay error (ej: referencia duplicada), volvemos al formulario
        header("Location: productos.php?error=fallo_en_registro");
    }
} else {
    // Si alguien intenta entrar al archivo sin usar el formulario
    header("Location: productos.php");
}

mysqli_close($conn);
?>