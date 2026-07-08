<?php
// views/header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        
        <!-- BOTÓN HAMBURGUESA (Solo móvil/tablet) -->
        <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Abrir menú">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- LOGO -->
        <a class="logo" href="<?= ($rol_usuario == 'admin') ? 'panel_admin.php' : 'panel_vendedor.php' ?>">
            <img src="/unideportes-system/assets/imagenes/logo-unideportes.png" alt="Logo Unideportes" class="logo-img">
            UNI<span>DEPORTES</span>
        </a>

        <!-- NAVEGACIÓN CENTRAL -->
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

        <!-- INFO DE USUARIO -->
        <div class="user-info">
            <span>Hola, <strong><?= ucfirst($usuario_nombre) ?></strong></span>
            <a href="/unideportes-system/controllers/auth.php?logout=1" class="btn-salir">Salir</a>
        </div>
    </div>
</header>

<!-- OVERLAY OSCURO PARA MÓVIL -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<script>
// Toggle Sidebar (Móvil/Tablet)
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar-panel');
    const overlay = document.querySelector('.sidebar-overlay');
    const hamburger = document.querySelector('.hamburger-btn');
    
    if (sidebar) sidebar.classList.toggle('active');
    if (overlay) overlay.classList.toggle('active');
    if (hamburger) hamburger.classList.toggle('active');
}

// Cerrar sidebar al hacer clic en un enlace (móvil)
document.addEventListener('DOMContentLoaded', function() {
    const sidebarLinks = document.querySelectorAll('.sidebar-panel .nav-link');
    const sidebar = document.querySelector('.sidebar-panel');
    const overlay = document.querySelector('.sidebar-overlay');
    const hamburger = document.querySelector('.hamburger-btn');
    
    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 1024) {
                if (sidebar) sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
                if (hamburger) hamburger.classList.remove('active');
            }
        });
    });
    
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) {
            if (sidebar) sidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            if (hamburger) hamburger.classList.remove('active');
        }
    });
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && window.innerWidth <= 1024) {
            if (sidebar) sidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            if (hamburger) hamburger.classList.remove('active');
        }
    });
});
</script>