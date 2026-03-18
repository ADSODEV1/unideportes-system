<?php

include("connection.php");

if (!empty($_POST['name']) && !empty($_POST
['username']) && !empty($_POST['password'])) {

    //RECEPCIÓN
    $name       = $_POST['name'];
    $lastname   = $_POST['lastname'];
    $username   = $_POST['username'];
    $password   = $_POST['password'];
    $email      = $_POST['email'];
    $rol        = $_POST['rol'];
 
    //CONTRATO SEGURO
    //Usamos'?' para que SQL se que va un dato y no una orden

    $sql = "INSERT INTO usuarios (name, lastname, username, password, email, rol) 
    VALUES (?, ?, ?, ?, ?, ?)";

    //PREPARACIÓN CON OBJETO ($conn)
    $stmt = $conn->prepare($sql);

    //VINCULACIÓN ESCUDO
    //"ssssss" significa que los 6 campos son Strings (texto)

    $stmt->bind_param("ssssss", $name, $lastname, $username, $password, $email, $rol);

    //EJECUCIÓN
    if($stmt->execute()){
        header("Location: admin_user.php?msj=ok");
        exit();
    } else {
        echo "Error en la BD: " . $stmt->error;
    }

    //CIERRE
    $stmt->close();
    $conn->close();

    } else {
    header("Location: admin_user.php?msj=vacio");
    exit();
}
?>}
