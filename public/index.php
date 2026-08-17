<?php
// public/index.php - Página de inicio y login unificados
session_start();
require_once __DIR__ . '/../config/connection.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unideportes - Iniciar sesión</title>
    <style>
        /* ============================================
           PÁGINA DE LOGIN - ESTILOS UNIFICADOS
        ============================================ */
        body {
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        /* Contenedor principal */
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            max-width: 950px;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            margin: auto;
            border: 1px solid #e2e8f0;
        }

        /* Columna izquierda: Logo y Bienvenida */
        .hero-card {
            flex: 1;
            min-width: 300px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo-img {
            max-width: 150px;
            height: auto;
            margin-bottom: 15px;
            display: block;
        }

        .hero-card h1 {
            margin: 10px 0 5px;
            font-size: 1.8rem;
            color: #1e293b;
        }

        .hero-card h1 span {
            color: #E8310E;
        }

        .subtitulo {
            color: #64748b;
            font-size: 0.9rem;
            margin: 0 0 20px 0;
        }

        /* Texto de bienvenida (traído de home.php) */
        .hero-welcome-text {
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.6;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #E8310E;
        }

        .hero-welcome-text strong {
            color: #1e293b;
            display: block;
            margin-bottom: 8px;
            font-size: 1.05rem;
        }

        /* Columna derecha: Formulario */
        .login-wrapper {
            flex: 1;
            min-width: 300px;
        }

        .login-box, .welcome-box {
            background: #f8fafc;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .login-box h3, .welcome-box h3 {
            margin: 0 0 20px 0;
            color: #1e293b;
            font-size: 1.3rem;
            text-align: center;
        }

        /* Formulario */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #334155;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Botones */
        .btn-login, .btn-panel {
            background: #2563eb;
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: block;
            width: 100%;
            text-align: center;
            font-weight: 600;
            font-size: 1rem;
            box-sizing: border-box;
            transition: background 0.2s;
        }

        .btn-login:hover, .btn-panel:hover {
            background: #1d4ed8;
        }

        /* Mensajes */
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            text-align: center;
            margin-top: 15px;
            border-left: 4px solid #ef4444;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            text-align: center;
            margin-top: 15px;
            border-left: 4px solid #10b981;
        }

        /* Enlaces */
        .link-recovery {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #64748b;
            font-size: 0.9rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .link-recovery:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        .link-logout {
            display: block;
            margin-top: 15px;
            text-align: center;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        .link-logout:hover {
            color: #dc2626;
        }

        /* Welcome box info */
        .welcome-info {
            margin: 10px 0 20px 0;
            color: #475569;
            text-align: center;
        }

        .welcome-info strong {
            color: #1e293b;
        }

        /* Footer */
        .main-footer {
            margin-top: auto;
            padding: 20px 0;
            text-align: center;
            color: #64748b;
            font-size: 0.85rem;
            width: 100%;
            background-color: white;
            border-top: 1px solid #e2e8f0;
        }

        .main-footer p { margin: 0; }
        .main-footer .version { font-size: 0.75rem; color: #94a3b8; margin-top: 5px; }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                padding: 25px;
                gap: 25px;
                margin: 20px 15px;
                width: 90%;
            }
            .hero-card, .login-wrapper {
                width: 100%;
                min-width: 100%;
            }
            .hero-welcome-text {
                text-align: center;
                border-left: none;
                border-top: 4px solid #E8310E;
            }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <!-- COLUMNA IZQUIERDA: LOGO Y MENSAJE DE BIENVENIDA (Unificado de home.php) -->
        <div class="hero-card">
            <img src="/unideportes-system/public/imagenes/logo-unideportes.png" alt="Logo Unideportes" class="logo-img">
            <h1>UNI<span>DEPORTES</span></h1>
            <p class="subtitulo">Sistema de gestión interna</p>
            
            <div class="hero-welcome-text">
                <strong>Bienvenidos a Unideportes</strong>
                Este sistema ha sido diseñado exclusivamente para la gestión eficiente de nuestro stock. 
                Inicie sesión para acceder a funciones avanzadas, reportes detallados y una interfaz intuitiva que facilitará 
                la gestión de nuestros recursos.
            </div>
        </div>

        <!-- COLUMNA DERECHA: FORMULARIO DE LOGIN O PANEL DE USUARIO -->
        <?php if (!isset($_SESSION['username'])): ?>
            <!-- USUARIO NO LOGUEADO: Mostrar formulario -->
            <div class="login-wrapper">
                <div class="login-box">
                    <h3>Ingresar al Sistema</h3>
                    <form action="/unideportes-system/controllers/auth.php" method="POST">
                        <div class="form-group">
                            <label for="username">Usuario:</label>
                            <input type="text" name="username" id="username" required class="form-input" placeholder="Ej: admin">
                        </div>
                        <div class="form-group">
                            <label for="password">Contraseña:</label>
                            <input type="password" name="password" id="password" required class="form-input" placeholder="••••••••">
                        </div>
                        <button type="submit" name="accion" value="login" class="btn-login">ENTRAR</button>
                    </form>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert-error">⚠️ Datos incorrectos. Intente de nuevo.</div>
                    <?php endif; ?>

                    <?php if (isset($_GET['success']) && $_GET['success'] === 'reset_completado'): ?>
                        <div class="alert-success">
                            ✅ Contraseña restablecida correctamente. Ingresa con tu nueva contraseña.
                        </div>
                    <?php endif; ?>

                    <a href="/unideportes-system/views/recuperar_password.php" class="link-recovery">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- USUARIO LOGUEADO: Mostrar bienvenida y acceso al panel -->
            <div class="login-wrapper">
                <div class="welcome-box">
                    <h3>¡Hola de nuevo!</h3>
                    <p class="welcome-info">
                        Sesión activa como: <br>
                        <strong style="font-size: 1.2rem;"><?= htmlspecialchars($_SESSION['username']) ?></strong>
                    </p>
                    <a href="<?= ($_SESSION['role'] == 'admin') ? '/unideportes-system/views/panel_admin.php' : '/unideportes-system/views/panel_vendedor.php'; ?>" class="btn-panel">
                        IR AL PANEL DE CONTROL
                    </a>
                    <a href="/unideportes-system/controllers/auth.php?logout=1" class="link-logout">
                        Cerrar sesión
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> <strong>Unideportes</strong>. Todos los derechos reservados.</p>
        <p class="version">Sistema de Control de Inventario y Ventas v1.2 | Proyecto ADSO - SENA</p>
    </footer>

</body>
</html>
