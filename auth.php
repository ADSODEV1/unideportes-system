<?php
include("connection.php");
session_start();

// 1. LÓGICA PARA CERRAR SESIÓN (Logout)
// Si el archivo recibe una instrucción de "logout", limpia la sesión y vuelve al index
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// 2. LÓGICA PARA INICIAR SESIÓN (Login)
if (isset($_POST['username']) && isset($_POST['password'])) {
    $userForm = trim($_POST['username']);
    $passForm = trim($_POST['password']);

    // Consulta buscando coincidencia exacta de usuario y clave
    $sql = "SELECT * FROM usuarios WHERE username = '$userForm' AND password = '$passForm'";
    $query = mysqli_query($conn, $sql);

    if ($result = mysqli_fetch_array($query)) {
        // Guardamos los datos clave en la sesión
        $_SESSION['username'] = $result['username'];
        $_SESSION['role']     = $result['role'];
        $_SESSION['nombre']   = $result['name'];

        // Redirección según nivel de acceso
        $folder = ($_SESSION['role'] == 'admin') ? 'admin_user.php' : 'panel_vendedor.php';
        header("Location: $folder");
        exit();
    } else {
        // Si fallan los datos, regresa al index con el aviso de error
        header("Location: index.php?error=1");
        exit();
    }
}
?>