<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario_nombre = $_SESSION['username'] ?? 'Invitado';
$rol_usuario = $_SESSION['role'] ?? 'vendedor'; 
?>

<!-- CSS -->
<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">

<!-- FAVICON -->
<link rel="icon" href="images/favicon.ico" type="image/x-icon">

<header class="main-header">
    <div class="nav-container">
        <a class="logo" href="<?= ($rol_usuario == 'admin') ? 'panel_admin.php' : 'panel_vendedor.php' ?>">
            <img src="Logo y imagenes/Logo unideportes.png" alt="Logo Unideportes" class="logo-img">
            UNI<span style="color: red;">DEPORTES</span>
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
            <a href="logout.php" class="btn-salir">Salir</a>
        </div>
    </div>
</header>