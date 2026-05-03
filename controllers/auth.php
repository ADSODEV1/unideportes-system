<?php
require_once __DIR__ . '/../config/connection.php';
session_start();

//MOTOR DE SALIDA (Logout)
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../public/index.php");
    exit();
}

//MOTOR DE ENTRADA (Login)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userForm = $_POST['username'];
    $passForm = $_POST['password'];

    // Buscar usuario por username
    $sql = "SELECT id, username, password, role FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userForm);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Verificar contraseña hasheada
        if (password_verify($passForm, $row['password'])) {
            //Creamos la "Credencial" (Sesión)
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role'];

            //¿A dónde va? (Admin o Vendedor)
            $destino = ($_SESSION['role'] == 'admin') ? '/unideportes-system/views/panel_admin.php' : '/unideportes-system/views/panel_vendedor.php';
            header("Location: $destino");
            exit();
        } else {
            header("Location: ../public/index.php?error=1");
            exit();
        }
    } else {
        header("Location: ../public/index.php?error=1");
        exit();
    }
}
?>