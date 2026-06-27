<?php
// views/reset_password.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = app();
$token = $_GET['token'] ?? '';
$error = null;
$success = null;
$tokenData = null;

// Validar token consultando la base de datos
if (empty($token)) {
    $error = 'token_invalido';
} else {
    // Buscar el token en la BD
    $stmt = $pdo->prepare("
        SELECT pr.*, u.id as user_id, u.username 
        FROM password_resets pr
        INNER JOIN usuarios u ON pr.email = u.email
        WHERE pr.token = ?
    ");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        $error = 'token_invalido';
    } elseif (strtotime($tokenData['expires_at']) < time()) {
        // Token expirado - eliminarlo de la BD
        $stmtDelete = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmtDelete->execute([$token]);
        $error = 'token_expirado';
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $tokenData) {
    $nuevaPassword = $_POST['password'] ?? '';
    $confirmarPassword = $_POST['confirm_password'] ?? '';

    if (empty($nuevaPassword) || strlen($nuevaPassword) < 6) {
        $error = 'password_corta';
    } elseif ($nuevaPassword !== $confirmarPassword) {
        $error = 'passwords_no_coinciden';
    } else {
        try {
            // Actualizar contraseña del usuario
            $passwordHash = password_hash($nuevaPassword, PASSWORD_BCRYPT);
            $stmtUpdate = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmtUpdate->execute([$passwordHash, $tokenData['user_id']]);

            // Eliminar el token usado de la BD
            $stmtDelete = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmtDelete->execute([$token]);

            $success = true;
        } catch (Exception $e) {
            $error = 'error_db';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña | Unideportes</title>
    <style>
        body {
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .recovery-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            max-width: 500px;
            width: 100%;
            border: 1px solid #e2e8f0;
        }

        .recovery-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .recovery-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .recovery-header h1 {
            color: #1e293b;
            font-size: 1.6rem;
            margin: 0 0 10px 0;
            font-weight: 700;
        }

        .recovery-header p {
            color: #64748b;
            margin: 0;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: #334155;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
            text-align: center;
        }

        .alert-success a {
            color: #047857;
            font-weight: 700;
            text-decoration: underline;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .back-link a {
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link a:hover {
            color: #2563eb;
        }

        .user-info {
            background: #eff6ff;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9rem;
            color: #1e40af;
        }
    </style>
</head>
<body>

    <div class="recovery-container">
        
        <?php if ($success): ?>
            <!-- ÉXITO -->
            <div class="recovery-header">
                <div class="recovery-icon">✅</div>
                <h1>¡Contraseña Cambiada!</h1>
                <p>Tu contraseña ha sido actualizada correctamente.</p>
            </div>

            <div class="alert alert-success">
                ✅ Contraseña actualizada con éxito.<br>
                <a href="/unideportes-system/public/index.php">Ir al inicio de sesión</a>
            </div>

        <?php elseif ($error): ?>
            <!-- ERROR -->
            <div class="recovery-header">
                <div class="recovery-icon">❌</div>
                <h1>Error</h1>
            </div>

            <?php if ($error === 'token_invalido'): ?>
                <div class="alert alert-error">⚠️ El enlace de recuperación no es válido o ya fue utilizado.</div>
            <?php elseif ($error === 'token_expirado'): ?>
                <div class="alert alert-error">⚠️ El enlace de recuperación expiró. Solicítalo de nuevo.</div>
            <?php elseif ($error === 'password_corta'): ?>
                <div class="alert alert-error">⚠️ La contraseña debe tener al menos 6 caracteres.</div>
            <?php elseif ($error === 'passwords_no_coinciden'): ?>
                <div class="alert alert-error">⚠️ Las contraseñas no coinciden.</div>
            <?php elseif ($error === 'error_db'): ?>
                <div class="alert alert-error">⚠️ Error al actualizar la contraseña. Intenta de nuevo.</div>
            <?php endif; ?>

            <div class="back-link">
                <a href="/unideportes-system/views/recuperar_password.php">← Solicitar nuevo enlace</a>
            </div>

        <?php else: ?>
            <!-- FORMULARIO -->
            <div class="recovery-header">
                <div class="recovery-icon">🔐</div>
                <h1>Cambiar Contraseña</h1>
                <p>Ingresa tu nueva contraseña</p>
            </div>

            <div class="user-info">
                <strong>Usuario:</strong> <?= htmlspecialchars($tokenData['username']) ?><br>
                <strong>Email:</strong> <?= htmlspecialchars($tokenData['email']) ?>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="password">Nueva Contraseña *</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Mínimo 6 caracteres" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                           placeholder="Repite la contraseña" required minlength="6">
                </div>

                <button type="submit" class="btn-primary">🔒 Cambiar Contraseña</button>
            </form>

            <div class="back-link">
                <a href="/unideportes-system/public/index.php">← Cancelar y volver al inicio</a>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>