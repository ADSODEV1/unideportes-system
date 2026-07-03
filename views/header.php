<?php
// views/header.php

// Si por alguna razón no se ha iniciado sesión en la página padre, la iniciamos aquí
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Variables listas para usar (ya validadas y creadas en la página que incluye este header)
$rol_usuario = $_SESSION['role'] ?? '';
$usuario_nombre = $_SESSION['username'] ?? 'Usuario';
$pagina_actual = basename($_SERVER['PHP_SELF']);
$base = "/unideportes-system"; 
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
            <img src="/unideportes-system/assets/imagenes/logo-unideportes.png" alt="Logo Unideportes" class="logo-img">
            UNI<span style="color: var(--primary);">DEPORTES</span>
        </a>

        <nav class="main-nav">
            <ul class="nav-list">
                <li><a href="inventario.php" class="<?= ($pagina_actual == 'inventario.php') ? 'active' : '' ?>">Inventario</a></li>
                <li><a href="pedidos_admin.php" class="<?= ($pagina_actual == 'pedidos_admin.php') ? 'active' : '' ?>">Producción</a></li>
                <li><a href="clientes.php" class="<?= ($pagina_actual == 'clientes.php') ? 'active' : '' ?>">Clientes</a></li>
                <li><a href="reportes_ventas.php" class="<?= ($pagina_actual == 'reportes_ventas.php') ? 'active' : '' ?>">Reportes</a></li>
                <?php if (in_array($rol_usuario, ['vendedor', 'colaborador'], true)): ?>
                    <li><a href="/unideportes-system/views/soporte_tecnico_vendedor.php" class="<?= ($pagina_actual == 'soporte_tecnico_vendedor.php') ? 'active' : '' ?>">Soporte Técnico</a></li>
                <?php endif; ?>
                <?php if ($rol_usuario == 'admin'): ?>
                    <li><a href="admin_usuarios.php" class="<?= ($pagina_actual == 'admin_usuarios.php') ? 'active' : '' ?>">Personal</a></li>
                    <li><a href="soporte_tecnico.php" class="<?= ($pagina_actual == 'soporte_tecnico.php') ? 'active' : '' ?>">Soporte Técnico</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="user-info">
            <span>Hola, <strong><?= ucfirst($usuario_nombre) ?></strong></span>
            <a href="/unideportes-system/controllers/auth.php?logout=1" class="btn-salir">Salir</a>
        </div>
    </div>
</header>