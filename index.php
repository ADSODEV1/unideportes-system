<?php 
session_start();
include("header.php"); 
?>

<div class="login-container">
    <h1>UNI<span style="color: red;">DEPORTES</span></h1>
    <p>Sistema de Inventarios</p>

    <?php if(!isset($_SESSION['user_id'])): ?>
        
        <div class="users-form">
            <h3><i class="fa-solid fa-lock"></i> Ingreso al Sistema</h3>
            
            <form action="auth.php" method="POST">
                <label><i class="fa-solid fa-user"></i> Usuario:</label>
                <input type="text" name="username" required>

                <label><i class="fa-solid fa-key"></i> Contraseña:</label>
                <input type="password" name="password" required>

                <button type="submit" class="btn-guardar">
                    <i class="fa-solid fa-right-to-bracket"></i> ENTRAR
                </button>
            </form>

            <?php if(isset($_GET['error'])): ?>
                <p style="color: red;">⚠️ Datos incorrectos.</p>
            <?php endif; ?>
        </div>

    <?php else: ?>
        
        <div class="welcome-box">
            <h2><i class="fa-solid fa-hand-wave"></i> ¡Hola de nuevo!</h2>
            <p>Sesión activa: <strong><?= $_SESSION['nombre'] ?></strong></p>
            
            <a href="<?= ($_SESSION['rol'] == 'admin') ? 'admin_user.php' : 'panel_vendedor.php'; ?>" class="btn-panel">
                <i class="fa-solid fa-gauge-high"></i> IR AL PANEL
            </a>
            <br><br>
            <a href="logout.php" style="color: gray;">Cerrar sesión</a>
        </div>

    <?php endif; ?>
</div>

<?php include("footer.php"); ?>