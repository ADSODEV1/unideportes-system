<?php
// controllers/auth.php
require_once __DIR__ . '/../config/connection.php';

// Si no hay sesión iniciada, la arrancamos de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url = "/unideportes-system";

// 1. MOTOR DE SALIDA (Logout) 
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $base_url . "/public/index.php");
    exit();
}

// 2. MOTOR DE ENTRADA (Login) - Se activa al enviar el formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userForm = $_POST['username'] ?? '';
    $passForm = $_POST['password'] ?? '';

    // Validamos que no vengan vacíos
    if (empty($userForm) || empty($passForm)) {
        header("Location: " . $base_url . "/public/index.php?msj=vacio");
        exit();
    }

    // Obtener conexión PDO 
    $pdo = connection();

    try {
        // Consulta SQL con sentencia preparada
        $sql = "SELECT id, username, password, role FROM usuarios WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userForm]);
        $row = $stmt->fetch();

        if ($row && password_verify($passForm, $row['password'])) {
            // Variables de sesión estándar para todo el sistema
            $_SESSION['user_id']  = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role'];

            // Determinar destino con rutas web absolutas y limpias
            if ($row['role'] === 'admin') {
                $destino = $base_url . "/views/panel_admin.php";
            } else {
                $destino = $base_url . "/views/panel_vendedor.php";
            }

            header("Location: " . $destino);
            exit();
        } else {
            // Credenciales incorrectas
            header("Location: " . $base_url . "/public/index.php?error=datos_incorrectos");
            exit();
        }

    } catch (PDOException $e) {
        die("Error en la consulta: " . $e->getMessage());
    }
} else {
    // Si alguien intenta entrar a este archivo por URL sin POST ni GET[logout]
    header("Location: " . $base_url . "/public/index.php");
    exit();
}