<?php
// Redirección automática (opcional, si quieres que pase solo)
// header("Location: ./unideportes-system/public/index.php");
// exit();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Redirigiendo...</title>
    <meta http-equiv="refresh" content="3;url=http://localhost/unideportes-system/public/index.php">
    <style>
        body { font-family: sans-serif; text-align: center; padding-top: 50px; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <h2>Redirigiendo al sistema Unideportes...</h2>
    <div class="loader"></div>
    <p>Si no eres redirigido automáticamente, <a href="http://localhost/unideportes-system/public/index.php">haz clic aquí</a>.</p>
    
    <script>
        // Redirección automática también con JavaScript por si acaso
        setTimeout(function() {
            window.location.href = "http://localhost/unideportes-system/public/index.php";
        }, 3000); // 3 segundos
    </script>
</body>
</html>