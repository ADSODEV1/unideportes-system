<?php
include("connection.php");
session_start();

//MOTOR DE SALIDA (Logout)
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

//MOTOR DE ENTRADA (Login)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userForm = $_POST['username'];
    $passForm = $_POST['password'];

    //Usamos Sentencias Preparadas (El "Escudo")
    $sql = "SELECT * FROM usuarios WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $userForm, $passForm);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        //Creamos la "Credencial" (Sesión)
        $_SESSION['username'] = $row['username'];
        $_SESSION['role']     = $row['role'];
        $_SESSION['nombre']   = $row['name'];

        //¿A dónde va? (Admin o Vendedor)
        $destino = ($_SESSION['role'] == 'admin') ? 'admin_user.php' : 'panel_vendedor.php';
        header("Location: $destino");
        exit();
    } else {
        //Credenciales falsas
        header("Location: index.php?error=1");
        exit();
    }
}
?>