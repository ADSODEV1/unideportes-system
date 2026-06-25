<?php
// controllers/auth.php
require_once __DIR__ . '/../config/connection.php';

// Si no hay sesión iniciada, la arrancamos de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DEFINICIÓN DE LA RUTA BASE CORRECTA CON LA CARPETA PUBLIC
$base_url = "/unideportes-system/public";

// 1. MOTOR DE SALIDA (Logout) 
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $base_url . "/index.php");
    exit();
}

// 2. MOTOR DE ENTRADA (Login) - Se activa al enviar el formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userForm = $_POST['username'] ?? '';
    $passForm = $_POST['password'] ?? '';

    // Validamos que no vengan vacíos
    if (empty($userForm) || empty($passForm)) {
        header("Location: " . $base_url . "/index.php?msj=vacio");
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

        if ($row) {
            $storedPassword = $row['password'];
            $loginOk = false;

            if (password_verify($passForm, $storedPassword)) {
                $loginOk = true;
            } elseif ($storedPassword === $passForm) {
                // Caso de contraseña antigua almacenada en texto plano.
                $loginOk = true;
                $newHash = password_hash($passForm, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
                $updateStmt->execute([$newHash, $row['id']]);
            }

            if ($loginOk) {
                // Variables de sesión estándar para todo el sistema
                $_SESSION['user_id']     = $row['id'];
                $_SESSION['vendedor_id'] = $row['id']; // Para el módulo de ventas
                $_SESSION['username']    = $row['username'];
                $_SESSION['role']        = $row['role'];

                // Determinar destino con rutas web absolutas y limpias (Salen de la carpeta public hacia views)
                if ($row['role'] === 'admin') {
                    $destino = "/unideportes-system/views/panel_admin.php";
                } else {
                    $destino = "/unideportes-system/views/panel_vendedor.php";
                }

                header("Location: " . $destino);
                exit();
            }
        }

        // Credenciales incorrectas - Redirecciona al index dentro de public
        header("Location: " . $base_url . "/index.php?error=datos_incorrectos");
        exit();

    } catch (PDOException $e) {
        die("Error en la consulta: " . $e->getMessage());
    }
} else {
    // Si alguien intenta entrar a este archivo por URL sin POST ni GET[logout]
    header("Location: " . $base_url . "/index.php");
    exit();
}