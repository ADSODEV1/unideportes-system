<?php
// ZONA 1: INICIALIZACIÓN Y SEGURIDAD
require_once __DIR__ . '/../config/bootstrap.php';

// Validamos la sesión y los roles permitidos
require_login(['vendedor', 'colaborador', 'admin']);

// Cargamos la conexión PDO
$pdo = app();
$conn = connection(); 

// Variables de sesión para la cabecera
$rol_usuario = $_SESSION['role'] ?? '';
$usuario_nombre = $_SESSION['username'] ?? 'Usuario';

// Variable para saber qué página estamos viendo (para marcar el activo)
$pagina_actual = basename($_SERVER['PHP_SELF']);
$base = "/unideportes-system"; // Asegúrate que esto coincida con tu ruta raíz
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unideportes - Gestión</title>
    <link rel="stylesheet" href="/unideportes-system/assets/CSS/style.css?v=<?php echo time(); ?>">
</head>
<body>

<header class="main-header">
    <div class="nav-container">
        <a class="logo" href="<?= ($rol_usuario == 'admin') ? 'panel_admin.php' : 'panel_vendedor.php' ?>">
            <img src="/unideportes-system/assets/logo-imagenes/logo-unideportes.png" alt="Logo Unideportes" class="logo-img">
            UNI<span style="color: var(--primary);">DEPORTES</span>
        </a>

        <nav class="main-nav">
            <ul class="nav-list">
                <li><a href="inventario.php">Inventario</a></li>
                <li><a href="pedidos.php">Producción</a></li>
                <li><a href="clientes.php">Clientes</a></li>
                <?php if ($rol_usuario == 'admin'): ?>
                    <li><a href="reportes_ventas.php">Reportes</a></li>
                    <li><a href="admin_user.php">Personal</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="user-info">
            <span>Hola, <strong><?= ucfirst($usuario_nombre) ?></strong></span>
            <a href="/unideportes-system/controllers/auth.php?logout=1" class="btn-salir">Salir</a>
        </div>
    </div>
</header>