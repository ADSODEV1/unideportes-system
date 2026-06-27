<?php
// views/recuperar_password.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success = isset($_GET['success']);
$error = $_GET['error'] ?? null;
$token = $_SESSION['last_password_reset_token'] ?? null;

// Verificar si el token existe en la BD (opcional, para mayor seguridad)
$showLink = false;
if ($success && $token) {
    try {
        $pdo = app();
        $stmt = $pdo->prepare("SELECT id FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        if ($stmt->fetch()) {
            $showLink = true;
        }
    } catch (Exception $e) {
        $showLink = false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña | Unideportes</title>
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
            line-height: 1.5;
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

        .btn-copy {
            width: 100%;
            padding: 12px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem;
            transition: background 0.2s;
            margin-top: 10px;
        }

        .btn-copy:hover {
            background: #059669;
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

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
            padding: 15px;
        }

        .recovery-link-box {
            background: #f8fafc;
            border: 2px solid #2563eb;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            word-break: break-all;
        }

        .recovery-link-box a {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .recovery-link-box a:hover {
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
            transition: color 0.2s;
        }

        .back-link a:hover {
            color: #2563eb;
        }

        .note-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px;
            margin: 15px 0;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #92400e;
        }
    </style>
</head>
<body>

    <div class="recovery-container">
        
        <div class="recovery-header">
            <div class="recovery-icon">🔐</div>
            <h1>Recuperar Contraseña</h1>
            <p>Ingresa tu correo electrónico para recibir un enlace de recuperación de contraseña.</p>
        </div>

        <?php if ($error === 'email_invalido'): ?>
            <div class="alert alert-error">⚠️ Por favor ingresa un email válido.</div>
        <?php elseif ($error === 'no_encontrado'): ?>
            <div class="alert alert-error">⚠️ No se encontró una cuenta con ese email.</div>
        <?php elseif ($error === 'token_expirado'): ?>
            <div class="alert alert-error">⚠️ El enlace de recuperación expiró. Solicítalo de nuevo.</div>
        <?php elseif ($error === 'error_db'): ?>
            <div class="alert alert-error">⚠️ Error al generar el enlace. Intenta de nuevo.</div>
        <?php endif; ?>

        <?php if ($showLink): ?>
            <div class="alert alert-info">
                <strong>✅ Enlace de recuperación generado</strong>
                <p style="margin: 10px 0 0 0; font-size: 0.85rem;">
                    Como el sistema está en modo de desarrollo (localhost), el enlace se muestra aquí. 
                    En producción, se enviaría por correo electrónico.
                </p>
            </div>

            <div class="recovery-link-box">
                <strong>Haz clic en el siguiente enlace para cambiar tu contraseña:</strong><br><br>
                <a href="/unideportes-system/views/reset_password.php?token=<?= urlencode($token) ?>" target="_blank">
                    /unideportes-system/views/reset_password.php?token=<?= htmlspecialchars($token) ?>
                </a>
            </div>

            <button type="button" class="btn-copy" onclick="copiarEnlace()">
                📋 Copiar Enlace al Portapapeles
            </button>

            <div class="note-box">
                <strong>⚠️ Importante:</strong> Este enlace es válido por 1 hora y solo puede usarse una vez.
            </div>
        <?php endif; ?>

        <?php if (!$showLink && !$error): ?>
            <form action="/unideportes-system/controllers/password_reset_request.php" method="POST">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input id="email" type="email" name="email" class="form-input" 
                           placeholder="tu@email.com" required>
                </div>

                <button type="submit" class="btn-primary">📧 Enviar Enlace de Recuperación</button>
            </form>
        <?php elseif (!$showLink && $error): ?>
            <form action="/unideportes-system/controllers/password_reset_request.php" method="POST">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input id="email" type="email" name="email" class="form-input" 
                           placeholder="tu@email.com" required>
                </div>

                <button type="submit" class="btn-primary">📧 Intentar de Nuevo</button>
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="/unideportes-system/public/index.php">← Volver al inicio de sesión</a>
        </div>

    </div>

    <script>
        function copiarEnlace() {
            const linkBox = document.querySelector('.recovery-link-box a');
            const enlace = linkBox.href;
            
            navigator.clipboard.writeText(enlace).then(() => {
                const btn = document.querySelector('.btn-copy');
                const textoOriginal = btn.innerHTML;
                btn.innerHTML = '✅ ¡Copiado!';
                btn.style.background = '#059669';
                
                setTimeout(() => {
                    btn.innerHTML = textoOriginal;
                    btn.style.background = '#10b981';
                }, 2000);
            }).catch(err => {
                alert('Error al copiar: ' + err);
            });
        }
    </script>

</body>
</html>