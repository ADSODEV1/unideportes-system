<?php
include_once __DIR__ . '/../db/conexion.php';
session_start();

//MOTOR DE SALIDA (Logout)
if (isset($_GET['logout'])) {
    session_destroy();
  header("Location: ../public/index.php");
    exit();
}

//MOTOR DE ENTRADA (Login)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userForm = trim($_POST['username'] ?? '');
    $passForm = $_POST['password'];

    // Buscamos por usuario y verificamos el hash con password_verify()
    // LOWER() para permitir login aunque escriban "pablo" en vez de "Pablo"
    $sql = "SELECT * FROM usuarios WHERE LOWER(username) = LOWER(?) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userForm);
    $stmt->execute();
    $result = $stmt->get_result();

    if (($row = $result->fetch_assoc()) && password_verify($passForm, $row['password'])) {
        //Creamos la "Credencial" (Sesión)
        $_SESSION['username'] = $row['username'];
        $_SESSION['role']     = $row['role'];
        $_SESSION['nombre']   = $row['name'];
        $_SESSION['id_usuario'] = $row['id'];

        //¿A dónde va? (Admin o Vendedor)
        $destino = ($_SESSION['role'] == 'admin') ? '../views/admin_user.php' : '../views/panel_vendedor.php';
        header("Location: $destino");
        exit();
    } else {
        //Credenciales falsas
        header("Location: ../public/index.php?error=1");
        exit();
    }
}
?>