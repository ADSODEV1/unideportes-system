<?php
// Ya que el header se incluye en todos lados, aprovechamos para verificar el nombre del usuario
$usuario_nombre = $_SESSION['username'] ?? 'Usuario';
$rol_usuario = $_SESSION['role'] ?? 'vendedor';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unideportes - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f4f7f6; }
        .navbar { background-color: #1A2B4C !important; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .nav-link { color: rgba(255,255,255,0.8) !important; font-weight: 500; transition: 0.3s; }
        .nav-link:hover { color: #E61E2A !important; transform: translateY(-2px); }
        .btn-logout { background-color: #E61E2A; color: white; border-radius: 8px; font-weight: bold; }
        .btn-logout:hover { background-color: #c41924; color: white; }
        .brand-text { font-family: 'Montserrat', sans-serif; letter-spacing: 1px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= ($rol_usuario == 'admin') ? 'panel_admin.php' : 'panel_vendedor.php' ?>">
            <span class="fs-4 fw-bold brand-text">UNI<span style="color: #E61E2A;">DEPORTES</span></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                
                <li class="nav-item">
                    <a class="nav-link px-3" href="inventario.php">📦 Inventario</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="pedidos.php">🧵 Producción</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="clientes.php">🏆 Clientes</a>
                </li>

                <?php if ($rol_usuario == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link px-3" href="reportes_ventas.php">📊 Reportes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="admin_user.php">👥 Personal</a>
                </li>
                <?php endif; ?>

                <li class="nav-item ms-lg-4 border-start ps-lg-4">
                    <span class="text-white-50 small d-block">Bienvenido,</span>
                    <strong class="text-white d-block"><?= ucfirst($usuario_nombre) ?></strong>
                </li>
                <li class="nav-item ms-lg-3">
                    <a class="btn btn-sm btn-logout px-3" href="logout.php">Salir</a>
                </li>
            </ul>
        </div>
    </div>
</nav>