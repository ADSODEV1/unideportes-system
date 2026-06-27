<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniDeportes - Sistema de Gestión</title>
    <style>
        /* ============================================
           PÁGINA DE INICIO - ESTILOS SIMPLIFICADOS
           ============================================ */
        
        /* Reset y base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            background-color: #ffffff;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .logo {
            font-size: 1.6rem;
            font-weight: bold;
            letter-spacing: 0.5px;
            color: #1e293b;
        }

        .logo span {
            color: #E8310E;
        }

        /* Hero */
        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 5%;
            max-width: 1200px;
            margin: 0 auto;
            gap: 50px;
        }

        .hero-content {
            flex: 1;
            min-width: 300px;
        }

        .hero-content h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            line-height: 1.2;
            color: #0f172a;
        }

        .hero-content h1 span {
            color: #E8310E;
        }

        .hero-content p {
            font-size: 1.05rem;
            color: #475569;
            margin-bottom: 35px;
            line-height: 1.6;
            text-align: justify;
        }

        .hero-image {
            flex: 1;
            min-width: 300px;
            text-align: center;
        }

        .hero-image img {
            max-width: 100%;
            height: auto;
            max-height: 320px;
            filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.08));
        }

        /* Botones */
        .btn-primary {
            background-color: #2563eb;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-block;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-large {
            padding: 15px 35px;
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 15px;
            }
            
            .hero {
                flex-direction: column-reverse;
                text-align: center;
                padding: 30px 20px;
                gap: 30px;
            }
            
            .hero-content h1 {
                font-size: 1.8rem;
            }
            
            .hero-content p {
                text-align: center;
                font-size: 0.95rem;
            }
            
            .hero-image img {
                max-height: 200px;
            }
            
            .btn-large {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="header">
        <div class="logo">UNI<span>DEPORTES</span></div>
        <a href="/unideportes-system/public/index.php" class="btn-primary">
            Ingresar al Sistema
        </a>
    </header>

    <!-- HERO -->
    <main class="hero">
        
        <div class="hero-content">
            <h1>Gestión Interna de <br><span>UNIDEPORTES</span></h1>
            <p>
                Bienvenidos a <strong>Unideportes</strong>. 
                Este sistema ha sido diseñado exclusivamente para la gestión eficiente de la tienda. 
                Inicie sesión para acceder al stock, reportes detallados y una interfaz intuitiva que facilitará 
                la administración de nuestros recursos.
            </p>
            
            <a href="/unideportes-system/public/index.php" class="btn-primary btn-large">
                Autenticarse en el Sistema
            </a>
        </div>
        
        <div class="hero-image">
            <img src="/unideportes-system/public/imagenes/logo-unideportes.png" alt="Logo Corporativo UniDeportes">
        </div>

    </main>

</body>
</html>