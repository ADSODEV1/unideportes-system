<?php
include("connection.php");

if (!empty($_POST['username']) && !empty($_POST['password'])) {

    $name     = trim($_POST['name'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $username = trim($_POST['username']);
    $email    = trim($_POST['email'] ?? '');
    
    // Rol por defecto
    $role = $_POST['role'] ?? 'colaborador';

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Email inválido";
        exit();
    }

    // Verificar que el username no exista
    $check = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $checkResult = $check->get_result();
    
    if ($checkResult->num_rows > 0) {
        header("Location: ../views/admin_user.php?msj=existe");
        exit();
    }

    // Hash de contraseña
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insertar con todos los campos
    $sql = "INSERT INTO usuarios (name, lastname, username, password, email, role) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $name, $lastname, $username, $password, $email, $role);

    if($stmt->execute()){
        header("Location: ../views/admin_user.php?msj=ok");
        exit();
    } else {
        echo "Error en la BD: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

} else {
    header("Location: ../views/admin_user.php?msj=vacio");
    exit();
}
?>