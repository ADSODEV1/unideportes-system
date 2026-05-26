<?php
require_once __DIR__ . '/../config/bootstrap.php';

$token = request('token');
$error = request('error');
$success = request('success');
$validToken = false;
$email = '';

if ($token && isset($_SESSION['password_reset_tokens'][$token])) {
    $resetData = $_SESSION['password_reset_tokens'][$token];
    if ($resetData['expires'] >= time()) {
        $validToken = true;
        $email = $resetData['email'];
    } else {
        unset($_SESSION['password_reset_tokens'][$token]);
        $error = 'token_expirado';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña | Unideportes</title>
    <link rel="stylesheet" href="/unideportes-system/assets/CSS/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="login-container" style="min-height: 100vh; align-items: center;">
        <div class="login-box">
            <h3>Restablecer contraseña</h3>
            <?php if ($success === '1'): ?>
                <div class="alert alert-success">Contraseña actualizada correctamente. <a href="/unideportes-system/public/index.php">Inicia sesión</a>.</div>
            <?php elseif (!$validToken): ?>
                <div class="alert alert-error">
                    <?php if ($error === 'token_expirado'): ?>
                        El enlace de recuperación ha expirado.
                    <?php else: ?>
                        El enlace no es válido. <a href="/unideportes-system/views/recuperar_password.php">Solicitar otro</a>.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <p>Restablece la contraseña de <strong><?= htmlspecialchars($email) ?></strong>.</p>
                <?php if ($error === 'contrasena_no_coincide'): ?>
                    <div class="alert alert-error">Las contraseñas no coinciden.</div>
                <?php elseif ($error === 'contrasena_corta'): ?>
                    <div class="alert alert-error">La contraseña debe tener al menos 6 caracteres.</div>
                <?php endif; ?>

                <form action="/unideportes-system/controllers/reset_password.php" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <label for="password">Nueva contraseña</label>
                    <input id="password" type="password" name="password" required>
                    <label for="password_confirm">Confirmar contraseña</label>
                    <input id="password_confirm" type="password" name="password_confirm" required>
                    <button type="submit" class="btn-primary">Actualizar contraseña</button>
                </form>
            <?php endif; ?>

            <p style="margin-top: 20px; text-align: center;">
                <a href="/unideportes-system/public/index.php">Volver al inicio de sesión</a>
            </p>
        </div>
    </div>
</body>
</html>
