<?php
include("connection.php");

// 1. VERIFICACIÓN: Comprobamos que existan los datos enviados
if (!empty($_POST['name']) && !empty($_POST['username']) && !empty($_POST['password'])) {
    
    // 2. RECEPCIÓN: Guardamos los datos en variables
    $name     = $_POST['name'];
    $lastname = $_POST['lastname'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email    = $_POST['email'];
    $role     = $_POST['role']; // ¡Importante recibir el rol!

    // 3. SQL: Insertamos en la tabla 'usuarios' con los campos exactos
    $sql = "INSERT INTO usuarios (name, lastname, username, password, email, role) 
            VALUES ('$name', '$lastname', '$username', '$password', '$email', '$role')";
    
    $query = mysqli_query($conn, $sql);

    // 4. REDIRECCIÓN: Si funciona, volvemos al panel con éxito
    if($query){
        header("Location: admin_user.php?msj=ok");
    } else {
        echo "Error en la BD: " . mysqli_error($conn);
    }

} else {
    // Si faltan campos obligatorios
    header("Location: admin_user.php?msj=vacio");
}
?>