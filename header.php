<?php
// Ya que el header se incluye en todos lados, aprovechamos para verificar quién es el usuario
$usuario_nombre = $_SESSION['username'] ?? 'Invitado';
$rol_usuario = $_SESSION['role'] ?? 'vendedor';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unideportes - Gestión</title>
    
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="main-header">
    <div class="nav-container">
        <a class="logo" href="<?= ($rol_usuario == 'admin') ? 'panel_admin.php' : 'panel_vendedor.php' ?>">
            UNI<span style="color: red;">DEPORTES</span>
        </a>

        <nav class="main-nav">
            <ul>
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
            <a href="logout.php" class="btn-salir">Salir</a>
        </div>
    </div>
</header>