<?php
session_start();
include("connection.php");
$conn = connection();

// 1. SEGURIDAD: Solo Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: inventario.php?error=no_autorizado");
    exit();
}

// 2. RECIBIR EL ID (Por la URL)
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // 3. SQL DE ELIMINACIÓN
    $sql = "DELETE FROM productos WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        header("Location: inventario.php?success=producto_eliminado");
    } else {
        header("Location: inventario.php?error=fallo_eliminacion");
    }
} else {
    header("Location: inventario.php");
}

mysqli_close($conn);
?>