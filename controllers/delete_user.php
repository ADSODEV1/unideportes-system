<?php
include("connection.php");

//Atrapamos el ID que viene en la URL: delete_user.php?id=5
$id = $_GET['id'];

//Preparamos la orden de eliminación
$sql = "DELETE FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);

//Le pasamos el ID (es un número entero, por eso usamos "i")
$stmt->bind_param("i", $id);

//Ejecutamos
if ($stmt->execute()) {
    // Si funcionó, regresamos a la tabla
    header("Location: admin_user.php?msj=eliminado");
} else {
    echo "No se pudo eliminar: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>