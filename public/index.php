<?php
session_start();
require_once __DIR__ . '/../config/connection.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unideportes - Iniciar sesión</title>
    <link rel="stylesheet" href="/unideportes-system/assets/CSS/style.css?v=1">
</head>
<body>

<div class="login-container">
    <div class="hero-card">
        <h1>UNI<span style="color: var(--primary);">DEPORTES</span></h1>
        <p class="subtitulo">Sistema de gestión de inventarios</p>
    </div>

    <?php if (!isset($_SESSION['username'])): ?>
        <div class="login-wrapper">
            <div class="login-box">
                <h3>Ingreso al Sistema</h3>
                <form action="/unideportes-system/controllers/auth.php" method="POST">
                    <label>Usuario:</label>
                    <input type="text" name="username" required>

                    <label>Contraseña:</label>
                    <input type="password" name="password" required>

                    <button type="submit" name="accion" value="login" class="btn-login">ENTRAR</button>
                </form>

                <?php if (isset($_GET['error'])): ?>
                    <p class="error-msg">⚠️ Datos incorrectos</p>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="login-wrapper">
            <div class="welcome-box">
                <h2>¡Hola de nuevo!</h2>
                <p>Sesión activa: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
                <a href="<?= ($_SESSION['role'] == 'admin') ? '/unideportes-system/views/panel_admin.php' : '/unideportes-system/views/panel_vendedor.php'; ?>" class="btn-panel">IR AL PANEL</a>
                <p style="margin-top:16px;"><a href="/unideportes-system/controllers/logout.php" class="cerrar">Cerrar sesión</a></p>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>


