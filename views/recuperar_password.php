<?php
require_once __DIR__ . '/../config/bootstrap.php';

$success = request('success');
$error = request('error');
$token = $_SESSION['last_password_reset_token'] ?? null;
$showLink = $success && $token && isset($_SESSION['password_reset_tokens'][$token]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña | Unideportes</title>
    <link rel="stylesheet" href="/unideportes-system/assets/CSS/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="login-container" style="min-height: 100vh; align-items: center;">
        <div class="login-box">
            <h3>Recuperar contraseña</h3>
            <p>Ingresa tu correo electrónico para recibir un enlace de recuperación.</p>

            <?php if ($error === 'email_invalido'): ?>
                <div class="alert alert-error">Por favor ingresa un email válido.</div>
            <?php elseif ($error === 'no_encontrado'): ?>
                <div class="alert alert-error">No se encontró una cuenta con ese email.</div>
            <?php elseif ($error === 'token_expirado'): ?>
                <div class="alert alert-error">El enlace de recuperación expiró. Solicítalo de nuevo.</div>
            <?php endif; ?>

            <?php if ($showLink): ?>
                <div class="alert alert-success" style="word-break: break-all;">
                    Enlace de recuperación generado. Copia o haz clic en el siguiente enlace para restablecer tu contraseña:
                    <br>
                    <a href="/unideportes-system/views/reset_password.php?token=<?= urlencode($token) ?>">
                        /unideportes-system/views/reset_password.php?token=<?= htmlspecialchars($token) ?>
                    </a>
                </div>
            <?php endif; ?>

            <form action="/unideportes-system/controllers/password_reset_request.php" method="POST">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" placeholder="tu@email.com" required>
                <button type="submit" class="btn-primary">Enviar enlace</button>
            </form>

            <p style="margin-top: 20px; text-align: center;">
                <a href="/unideportes-system/public/index.php">Volver al inicio de sesión</a>
            </p>
        </div>
    </div>
</body>
</html>
