<?php
include("connection.php");

if (!empty($_POST['name']) && !empty($_POST['username']) && !empty($_POST['password'])) {

    // LIMPIEZA
    $name     = trim($_POST['name']);
    $lastname = trim($_POST['lastname']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $role     = $_POST['role'];

    // VALIDAR EMAIL
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Email inválido";
        exit();
    }

    // ENCRIPTAR CONTRASEÑA 🔐
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // SQL SEGURO
    $sql = "INSERT INTO usuarios (name, lastname, username, password, email, role) 
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("ssssss", $name, $lastname, $username, $password, $email, $role);

    if($stmt->execute()){
        header("Location: admin_user.php?msj=ok");
        exit();
    } else {
        echo "Error en la BD: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

} else {
    header("Location: admin_user.php?msj=vacio");
    exit();
}
?>