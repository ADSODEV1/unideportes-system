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

<<<<<<< Updated upstream
    <?php else: ?>
        
        <div class="welcome-box">
            <h2><i class="fa-solid fa-hand-wave"></i> ¡Hola de nuevo!</h2>
            <p>Sesión activa: <strong><?= $_SESSION['nombre'] ?></strong></p>
            
            <a href="<?= ($_SESSION['rol'] == 'admin') ? 'admin_user.php' : 'panel_vendedor.php'; ?>" class="btn-panel">
                <i class="fa-solid fa-gauge-high"></i> IR AL PANEL
            </a>
            <br><br>
            <a href="logout.php" style="color: gray;">Cerrar sesión</a>
=======
        <div class="col-md-5">
            <div class="card shadow-lg border-0" style="border-radius: 20px; border-top: 6px solid #E61E2A;">
                <div class="card-body p-5">
                    
                    <?php if(!isset($_SESSION['username'])): ?>
                        <h3 class="fw-bold mb-4" style="color: #1A2B4C;">Ingreso</h3>
                        
                        <form action="auth.php" method="POST">
                            <div class="mb-3">
                                <label class="small fw-bold">Usuario</label>
                                <input type="text" name="username" class="form-control bg-light" required>
                            </div>
                            <div class="mb-4">
                                <label class="small fw-bold">Contraseña</label>
                                <input type="password" name="password" class="form-control bg-light" required>
                            </div>
                            <button type="submit" class="btn btn-lg w-100 text-white fw-bold" style="background-color: #E61E2A; border-radius: 12px;">
                                ENTRAR
                            </button>
                        </form>

                        <?php if(isset($_GET['error'])): ?>
                            <div class="alert alert-danger mt-3 small text-center">Datos incorrectos.</div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center">
                            <div class="fs-1 mb-2">👋</div>
                            <h4 class="fw-bold">¡Hola de nuevo!</h4>
                            <p class="text-muted">Sesión activa como: <strong><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                            <a href="auth.php" class="btn btn-outline-danger btn-sm mb-3">Cerrar sesión e ingresar con otro</a>
                            <hr>
                            <a href="<?= ($_SESSION['role'] == 'admin') ? 'admin_user.php' : 'panel_vendedor.php'; ?>" 
                               class="btn btn-lg w-100 text-white fw-bold" style="background-color: #1A2B4C; border-radius: 12px;">
                                IR AL PANEL
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
>>>>>>> Stashed changes
        </div>

    <?php endif; ?>
</div>

<?php include("footer.php"); ?>