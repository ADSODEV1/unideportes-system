<?php
include("connection.php");

// 1. RECEPCIÓN: Capturamos los datos enviados por POST desde update.php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id       = $_POST['id'];
    $name     = $_POST['name'];
    $lastname = $_POST['lastname'];
    $email    = $_POST['email'];
    $role     = $_POST['role'];

    // 2. SQL: Actualizamos la tabla 'usuarios' filtrando por el ID
    // Nota: Usamos comillas simples para los valores de texto
    $sql = "UPDATE usuarios SET 
            name = '$name', 
            lastname = '$lastname', 
            email = '$email', 
            role = '$role' 
            WHERE id = '$id'";
    
    $query = mysqli_query($conn, $sql);

    // 3. REDIRECCIÓN: Si fue exitoso, volvemos al panel
    if($query) {
        header("Location: admin_user.php?msj=editado");
    } else {
        echo "Error al actualizar: " . mysqli_error($conn);
    }
} else {
    // Si intentan entrar al archivo sin enviar el formulario
    header("Location: admin_user.php");
}
?>