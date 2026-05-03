<?php
require_once __DIR__ . '/../config/connection.php';

// 1. Recibimos el ID oculto y los datos nuevos
$id       = $_POST['id'];
$name     = $_POST['name'];
$lastname = $_POST['lastname'];
$username = $_POST['username'];
$email    = $_POST['email'];
$password = $_POST['password'];

// 2. Preparamos la sentencia de actualización
if (!empty($password)) {
    // Si se proporcionó contraseña, la actualizamos con hash
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE usuarios SET name=?, lastname=?, username=?, email=?, password=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $name, $lastname, $username, $email, $hashed, $id);
} else {
    // Si no se proporcionó contraseña, mantenemos la actual
    $sql = "UPDATE usuarios SET name=?, lastname=?, username=?, email=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $name, $lastname, $username, $email, $id);
}

// 3. Ejecutamos el cambio
if ($stmt->execute()) {
    header("Location: ../views/admin_user.php?msj=actualizado");
    exit();
} else {
    echo "Error al actualizar: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>