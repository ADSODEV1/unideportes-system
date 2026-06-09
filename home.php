<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniDeportes - System Home</title>
    <style>
        /* Estilos Globales Estilo Corporativo */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Barra de Navegación */
        header {
            background-color: #ffffff;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .logo {
            font-size: 1.6rem;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .logo span {
            color: #E8310E; /* Rojo corporativo de UniDeportes */
        }
        
        /* Botones de Acceso */
        .btn-ingresar {
            background-color: #2563eb; /* Azul corporativo */
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        .btn-ingresar:hover {
            background-color: #1d4ed8;
            transform: translateY(-1px);
        }

        /* Sección de Presentación (Hero) */
        .hero-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 5%;
            max-width: 1200px;
            margin: 0 auto;
            gap: 50px;
        }
        .hero-text {
            flex: 1;
            min-width: 300px;
        }
        .hero-text h1 {
            font-size: 2.5rem;
            margin-top: 0;
            margin-bottom: 20px;
            line-height: 1.2;
            color: #0f172a;
        }
        .hero-text p {
            font-size: 1.05rem;
            color: #475569;
            margin-bottom: 35px;
            line-height: 1.6;
            text-align: justify;
        }
        .hero-img {
            flex: 1;
            min-width: 300px;
            text-align: center;
        }
        .hero-img img {
            max-width: 100%;
            height: auto;
            max-height: 320px;
            filter: drop-shadow(0 10px 15px rgba(0,0,0,0.08));
        }

        /* Ajustes para Celulares */
        @media (max-width: 768px) {
            header {
                padding: 15px;
            }
            .hero-container {
                flex-direction: column-reverse; /* El logo/imagen pasa abajo en celular */
                text-align: center;
                padding: 30px 20px;
                gap: 30px;
            }
            .hero-text h1 {
                font-size: 1.8rem;
            }
            .hero-text p {
                text-align: center;
                font-size: 0.95rem;
            }
            .hero-img img {
                max-height: 200px;
            }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">UNI<span>DEPORTES</span></div>
        <a href="/unideportes-system/public/index.php" class="btn-ingresar">Ingresar al Sistema</a>
    </header>

    <div class="hero-container">
        
        <div class="hero-text">
            <h1>Gestión Interna de</span> <br>UNI<span style="color: #E8310E;">DEPORTES</span></h1>
            <p>
                Bienvenidos a <strong>Unideportes</strong>. 
                Este sistema ha sido diseñado exclusivamente para la gestión eficiente de Unideportes. 
                Inicie sesión para acceder al stock, reportes detallados y una interfaz intuitiva que facilitará 
                la administración de nuestros recursos.
            </p>
            
            <a href="/unideportes-system/public/index.php" class="btn-ingresar" style="padding: 15px 35px; font-size: 1.1rem; display: inline-block;">
                Autenticarse en el Sistema
            </a>
        </div>
        
        <div class="hero-img">
            <img src="/unideportes-system/public/imagenes/logo-unideportes.png" alt="Logo Corporativo UniDeportes">
        </div>

    </div>

</body>
</html>