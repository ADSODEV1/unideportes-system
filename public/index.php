<?php
session_start();
require_once __DIR__ . '/../config/connection.php';
// Footer común para todas las vistas

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unideportes - Iniciar sesión</title>
    <!-- Tu CSS -->
    <link rel="stylesheet" href="/unideportes-system/assets/CSS/style.css?v=1">
    
    
    <!-- Un pequeño estilo extra para el logo si no está definido en tu CSS principal -->
    <style>
        .logo-img {
            max-width: 150px; /* Tamaño máximo del logo */
            height: auto;
            margin-bottom: 15px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Si quieres que el fondo sea un color sólido o gradiente simple */
        body {
            background: #f0f2f5; /* Gris muy suave o puedes usar un gradiente simple */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif; /* Fuente segura */
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            max-width: 900px;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin: 20px;
        }

        .hero-card {
            flex: 1;
            min-width: 250px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .login-wrapper {
            flex: 1;
            min-width: 250px;
        }

        .login-box, .welcome-box {
            background: #f9fafb;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        h1 { margin: 10px 0 5px; font-size: 1.8rem; }
        .subtitulo { color: #666; font-size: 0.9rem; margin-top: 0; }
        
        button.btn-login, a.btn-panel {
            background: #2563eb; /* Azul estándar */
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: block;
            width: 100%;
            text-align: center;
            font-weight: bold;
        }
        
        button.btn-login:hover, a.btn-panel:hover {
            background: #1d4ed8;
        }

        .error-msg {
            background: #fee2e2;
            color: #b91c1c;
            padding: 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            text-align: center;
        }

        footer.main-footer {
            margin-top: auto;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 0.85rem;
            width: 100%;
        }

        @media (max-width: 700px) {
            .login-container { flex-direction: column; }
            .hero-card, .login-wrapper { width: 100%; }
        }
    </style>
</head>

<body>

<div class="login-container">
    <div class="hero-card">
        <!-- LOGO -->
        <img src="/unideportes-system/assets/logo-imagenes/logo-unideportes.png" alt="Logo Unideportes" class="logo-img">
        
        <h1>UNI<span style="color: #E8310E;">DEPORTES</span></h1>
        <p class="subtitulo">Sistema de gestión de inventarios</p>
    </div>

    <?php if (!isset($_SESSION['username'])): ?>
        <div class="login-wrapper">
            <div class="login-box">
                <h3 style="margin-top: 0; color: #333;">Ingreso al Sistema</h3>
                <form action="/unideportes-system/controllers/auth.php" method="POST">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9rem;">Usuario:</label>
                        <input type="text" name="username" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9rem;">Contraseña:</label>
                        <input type="password" name="password" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                    </div>

                    <button type="submit" name="accion" value="login" class="btn-login">ENTRAR</button>
                </form>

                <?php if (isset($_GET['error'])): ?>
    <div class="error-msg">⚠️ Datos incorrectos.</div>
<?php endif; ?>

<!-- NUEVO ENLACE DE RECUPERACIÓN -->
<div style="margin-top: 15px; text-align: center;">
    <a href="/unideportes-system/views/recuperar_password.php" style="color: #666; font-size: 0.9rem; text-decoration: none;">
        ¿Olvidaste tu contraseña?
    </a>
</div>
        </div>
    <?php else: ?>
        <div class="login-wrapper">
            <div class="welcome-box">
                <h3 style="margin-top: 0; color: #333;">¡Hola de nuevo!</h3>
                <p style="margin: 10px 0;">Sesión activa: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
                <a href="<?= ($_SESSION['role'] == 'admin') ? '/unideportes-system/views/panel_admin.php' : '/unideportes-system/views/panel_vendedor.php'; ?>" class="btn-panel">IR AL PANEL</a>
                <p style="margin-top: 15px; text-align: center;"><a href="/unideportes-system/controllers/logout.php" style="color: #666; text-decoration: none; font-size: 0.9rem;">Cerrar sesión</a></p>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>


