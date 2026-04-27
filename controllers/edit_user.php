<?php
include("connection.php");

// 1. Recibimos el ID oculto y los datos nuevos
$id       = $_POST['id'];
$name     = $_POST['name'];
$lastname = $_POST['lastname'];
$username = $_POST['username'];
$email    = $_POST['email'];

// 2. Preparamos la sentencia de actualización
// Lógica: "Actualiza la tabla usuarios, pon estos valores DONDE el id sea este"
$sql = "UPDATE usuarios SET name=?, lastname=?, username=?, email=? WHERE id=?";

$stmt = $conn->prepare($sql);

// 3. Vinculamos: 4 textos (s) y 1 número entero (i) para el ID
$stmt->bind_param("ssssi", $name, $lastname, $username, $email, $id);

// 4. Ejecutamos el cambio
if ($stmt->execute()) {
    // Si todo salió bien, volvemos a la lista de administración
    header("Location: admin_user.php?msj=actualizado");
    exit();
} else {
    echo "Error al actualizar: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>