<?php
// public/reset_password_view.php
require_once __DIR__ . '/../config/bootstrap.php';

// Aseguramos que la sesión esté activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = app();

// Usamos $_GET nativo para evitar fallos si request() se comporta raro
$token = $_GET['token'] ?? '';
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
$validToken = false;
$email = '';

if (!empty($token)) {
    try {
        // Buscamos el token en la BD
        $stmt = $pdo->prepare('SELECT email, expires_at FROM password_resets WHERE token = ? LIMIT 1');
        $stmt->execute([$token]);
        $resetData = $stmt->fetch();

        if ($resetData) {
            $current_time = date('Y-m-d H:i:s');
            // Validamos cronológicamente si no ha vencido
            if ($resetData['expires_at'] >= $current_time) {
                $validToken = true;
                $email = $resetData['email'];
            } else {
                // Si ya expiró, lo borramos para limpiar la BD
                $deleteStmt = $pdo->prepare('DELETE FROM password_resets WHERE token = ?');
                $deleteStmt->execute([$token]);
                $error = 'token_expirado';
            }
        } else {
            $error = 'token_invalido';
        }
    } catch (PDOException $e) {
        // Si hay un error de base de datos lo atrapamos para que no quede en blanco
        die("Error en la base de datos: " . $e->getMessage());
    }
} else {
    $error = 'token_invalido';
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
    <div class="login-container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
        <div class="login-box">
            <h3>Restablecer contraseña</h3>
            
            <?php if ($success === '1'): ?>
                <div class="alert alert-success" style="background-color: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                    Contraseña actualizada correctamente. <a href="/unideportes-system/public/index.php" style="font-weight: bold; color: #0f5132;">Inicia sesión aquí</a>.
                </div>
            
            <?php elseif (!$validToken): ?>
                <div class="alert alert-error" style="background-color: #f8d7da; color: #842029; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                    <?php if ($error === 'token_expirado'): ?>
                        El enlace de recuperación ha expirado (límite de 15 minutos). <a href="/unideportes-system/views/recuperar_password.php" style="color: #842029; font-weight: bold;">Solicitar otro</a>.
                    <?php else: ?>
                        El enlace de recuperación no es válido o ya fue utilizado. <a href="/unideportes-system/views/recuperar_password.php" style="color: #842029; font-weight: bold;">Solicitar uno nuevo</a>.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <p>Restablece la contraseña para la cuenta: <br><strong><?= htmlspecialchars($email) ?></strong>.</p>
                
                <?php if ($error === 'contrasena_no_coincide'): ?>
                    <div class="alert alert-error" style="background-color: #f8d7da; color: #842029; padding: 10px; border-radius: 4px; margin-bottom: 10px;">Las contraseñas no coinciden.</div>
                <?php elseif ($error === 'contrasena_corta'): ?>
                    <div class="alert alert-error" style="background-color: #f8d7da; color: #842029; padding: 10px; border-radius: 4px; margin-bottom: 10px;">La contraseña debe tener al menos 6 caracteres.</div>
                <?php endif; ?>

                <form action="/unideportes-system/controllers/password_update_process.php" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div style="margin-bottom: 12px; text-align: left;">
                        <label for="password" style="display: block; margin-bottom: 5px; font-weight: bold;">Nueva contraseña</label>
                        <input id="password" type="password" name="password" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                    </div>
                    
                    <div style="margin-bottom: 18px; text-align: left;">
                        <label for="password_confirm" style="display: block; margin-bottom: 5px; font-weight: bold;">Confirmar contraseña</label>
                        <input id="password_confirm" type="password" name="password_confirm" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                    </div>
                    
                    <button type="submit" class="btn-primary" style="width: 100%; padding: 10px; font-weight: bold; cursor: pointer;">Actualizar contraseña</button>
                </form>
            <?php endif; ?>

            <p style="margin-top: 25px; text-align: center;">
                <a href="/unideportes-system/public/index.php" style="color: #2563eb; text-decoration: none;">Volver al inicio de sesión</a>
            </p>
        </div>
    </div>
</body>
</html>