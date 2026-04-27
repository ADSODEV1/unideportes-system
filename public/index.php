<?php 
session_start();
include("header.php"); 
?>

<div class="login-container">

    <h1>UNI<span style="color: red;">DEPORTES</span></h1>
    <p class="subtitulo">Sistema de gestion de inventarios</p>

    <?php if(!isset($_SESSION['username'])): ?>

        <!-- LOGIN -->
        <div class="login-wrapper">
            <div class="login-box">
                <h3> Ingreso al Sistema</h3>

                <form action="auth.php" method="POST">
                    
                    <label>Usuario:</label>
                    <input type="text" name="username" required>

                    <label>Contraseña:</label>
                    <input type="password" name="password" required>

                    <button type="submit" name="accion" value="login" class="btn-login">
    ENTRAR
</button>


                </form>

                <?php if(isset($_GET['error'])): ?>
                    <p class="error-msg">⚠️ Datos incorrectos</p>
                <?php endif; ?>

            </div>
        </div>

    <?php else: ?>

        <!-- BIENVENIDA -->
        <div class="login-wrapper">
            <div class="welcome-box">
                <h2> ¡Hola de nuevo!</h2>
                <p>Sesión activa: <strong><?= $_SESSION['username'] ?></strong></p>

                <a href="<?= ($_SESSION['role'] == 'admin') ? 'panel_admin.php' : 'panel_vendedor.php'; ?>" class="btn-panel">
                    IR AL PANEL
                </a>

                <br><br>

                <a href="logout.php" class="cerrar">Cerrar sesión</a>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php include("footer.php"); ?>