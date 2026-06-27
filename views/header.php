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
    
    <style>
        /* ============================================
           HEADER CON BOTÓN HAMBURGUESA
           ============================================ */
        
        /* Header principal */
        .main-header {
            background: white;
            padding: 15px 5%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Logo */
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-size: 1.4rem;
            font-weight: bold;
            color: #1e293b;
            letter-spacing: 0.5px;
        }

        .logo-img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .logo span {
            color: #E8310E;
        }

        /* Navegación central */
        .main-nav {
            display: flex;
            align-items: center;
        }

        .nav-list {
            display: flex;
            list-style: none;
            gap: 25px;
            margin: 0;
            padding: 0;
        }

        .nav-list li a {
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .nav-list li a:hover {
            background: #f1f5f9;
            color: #2563eb;
        }

        .nav-list li a.active {
            background: #dbeafe;
            color: #1e40af;
            font-weight: 600;
        }

        /* Info de usuario */
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info span {
            color: #64748b;
            font-size: 0.9rem;
        }

        .user-info strong {
            color: #1e293b;
        }

        .btn-salir {
            padding: 8px 16px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn-salir:hover {
            background: #fecaca;
        }

        /* ============================================
           BOTÓN HAMBURGUESA (Solo móvil/tablet)
           ============================================ */
        
        .hamburger-btn {
            display: none;
            flex-direction: column;
            justify-content: space-around;
            align-items: center;
            width: 30px;
            height: 30px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            z-index: 1001;
            margin-right: 15px;
        }

        .hamburger-btn span {
            width: 25px;
            height: 3px;
            background: #1e293b;
            border-radius: 3px;
            transition: all 0.3s linear;
            position: relative;
            transform-origin: 1px;
        }

        /* Animación de X cuando está activo */
        .hamburger-btn.active span:nth-child(1) {
            transform: rotate(45deg);
        }

        .hamburger-btn.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger-btn.active span:nth-child(3) {
            transform: rotate(-45deg);
        }

        /* ============================================
           OVERLAY OSCURO (Móvil)
           ============================================ */
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            transition: opacity 0.3s;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* ============================================
           RESPONSIVE - MÓVIL Y TABLET (≤768px)
           ============================================ */
        
        @media (max-width: 768px) {
            /* Mostrar botón hamburguesa */
            .hamburger-btn {
                display: flex;
            }

            /* Ocultar navegación central en móvil */
            .main-nav {
                display: none;
            }

            /* Ajustar espaciado */
            .main-header {
                padding: 12px 15px;
            }

            .logo {
                font-size: 1.2rem;
            }

            .logo-img {
                width: 35px;
                height: 35px;
            }

            .user-info span {
                display: none; /* Ocultar "Hola, ..." en móvil */
            }

            /* Sidebar se convierte en menú deslizante */
            .sidebar-panel {
                position: fixed !important;
                left: -100% !important;
                top: 0 !important;
                width: 280px !important;
                max-width: 85% !important;
                height: 100vh !important;
                transition: left 0.3s ease !important;
                z-index: 1000 !important;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2) !important;
                overflow-y: auto !important;
            }

            .sidebar-panel.active {
                left: 0 !important;
            }

            /* Ajustar contenido principal */
            .main-content-panel {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 15px !important;
            }

            .admin-layout {
                flex-direction: column !important;
            }
        }

        /* Tablet (769px - 1024px) */
        @media (min-width: 769px) and (max-width: 1024px) {
            .hamburger-btn {
                display: flex;
            }

            .main-nav {
                display: none;
            }

            .sidebar-panel {
                position: fixed !important;
                left: -100% !important;
                top: 0 !important;
                width: 280px !important;
                height: 100vh !important;
                transition: left 0.3s ease !important;
                z-index: 1000 !important;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2) !important;
                overflow-y: auto !important;
            }

            .sidebar-panel.active {
                left: 0 !important;
            }

            .main-content-panel {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
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

        <!-- NAVEGACIÓN CENTRAL (Solo desktop) -->
        <nav class="main-nav">
            <ul class="nav-list">
                <li><a href="inventario.php" class="<?= ($pagina_actual == 'inventario.php') ? 'active' : '' ?>">Inventario</a></li>
                <li><a href="clientes.php" class="<?= ($pagina_actual == 'clientes.php') ? 'active' : '' ?>">Clientes</a></li>
                <li><a href="reportes_ventas.php" class="<?= ($pagina_actual == 'reportes_ventas.php') ? 'active' : '' ?>">Reportes</a></li>
                <?php if ($rol_usuario == 'admin'): ?>
                    <li><a href="admin_usuarios.php" class="<?= ($pagina_actual == 'admin_usuarios.php') ? 'active' : '' ?>">Personal</a></li>
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
// ============================================
// TOGGLE SIDEBAR (Móvil/Tablet)
// ============================================
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar-panel');
    const overlay = document.querySelector('.sidebar-overlay');
    const hamburger = document.querySelector('.hamburger-btn');
    
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
    
    if (overlay) {
        overlay.classList.toggle('active');
    }
    
    if (hamburger) {
        hamburger.classList.toggle('active');
    }
}

// Cerrar sidebar al hacer clic en un enlace (móvil)
document.addEventListener('DOMContentLoaded', function() {
    const sidebarLinks = document.querySelectorAll('.sidebar-panel .nav-link');
    const sidebar = document.querySelector('.sidebar-panel');
    const overlay = document.querySelector('.sidebar-overlay');
    const hamburger = document.querySelector('.hamburger-btn');
    
    // Cerrar al hacer clic en un enlace del sidebar
    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 1024) {
                if (sidebar) sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
                if (hamburger) hamburger.classList.remove('active');
            }
        });
    });
    
    // Cerrar al redimensionar a desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) {
            if (sidebar) sidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            if (hamburger) hamburger.classList.remove('active');
        }
    });
    
    // Cerrar con tecla ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && window.innerWidth <= 1024) {
            if (sidebar) sidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            if (hamburger) hamburger.classList.remove('active');
        }
    });
});
</script>