<?php
session_start();
require_once __DIR__ . '/../config/connection.php';

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = "Por favor ingresa tu correo.";
    } else {
        // 1. Verificar si el correo existe en la BD
        $stmt = $conn->prepare("SELECT id, username FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // AQUÍ IRÍA LA LÓGICA PARA ENVIAR EL CORREO
            // Por ahora, simulamos que se envió correctamente
            
            // Generar un token (en un caso real, guárdalo en BD)
            $token = bin2hex(random_bytes(16));
            
            // En un entorno real, aquí usarías PHPMailer o mail() para enviar el link:
            // $link = "http://tusitio.com/unideportes-system/views/nueva_password.php?token=" . $token;
            // mail($email, "Recuperar Contraseña", "Haz clic aquí: $link");
            
            $exito = "Se han enviado las instrucciones a tu correo (Simulación: Token generado: " . substr($token, 0, 8) . "...).";
        } else {
            // Para seguridad, no decimos "el correo no existe", solo que se enviaron instrucciones
            $exito = "Si el correo está registrado, recibirás las instrucciones.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="/unideportes-system/assets/CSS/style.css">
    <style>
        body { background: #f0f2f5; font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .recover-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-width: 400px; width: 100%; }
        h2 { text-align: center; color: #333; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background: #1d4ed8; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; }
        .success { background: #d1fae5; color: #065f46; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .error-msg { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="recover-box">
        <h2>Recuperar Contraseña</h2>
        <p style="text-align: center; color: #666; font-size: 0.9rem;">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
        
        <?php if ($exito): ?>
            <div class="success"><?= $exito ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="email" style="font-weight: bold; font-size: 0.9rem;">Correo Electrónico:</label>
            <input type="email" name="email" id="email" required placeholder="ejemplo@correo.com">
            <button type="submit">Enviar Instrucciones</button>
        </form>
        
        <a href="http://localhost/unideportes-system/public/index.php" class="back-link">← Volver al inicio</a>
    </div>
</body>
</html>