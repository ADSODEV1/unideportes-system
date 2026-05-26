<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Redirigiendo...</title>
    <meta http-equiv="refresh" content="3;url=/unideportes-system/public/index.php">
    <style>
        body { font-family: sans-serif; text-align: center; padding-top: 50px; background-color: #f8fafc; color: #1e293b; }
        .loader { border: 4px solid #e2e8f0; border-top: 4px solid #c91a25; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        a { color: #c91a25; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h2>Redirigiendo al sistema Unideportes...</h2>
    <div class="loader"></div>
    <p>Si no eres redirigido automáticamente, <a href="/unideportes-system/public/index.php">haz clic aquí</a>.</p>
    
    <script>
        // También limpiamos el enlace en JavaScript
        setTimeout(function() {
            window.location.href = "/unideportes-system/public/index.php";
        }, 3000);
    </script>
</body>
</html>