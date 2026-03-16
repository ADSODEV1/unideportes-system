<?php 
session_start();
include("header.php"); // Carga CSS y Bootstrap
?>

<div class="container d-flex align-items-center" style="min-height: 85vh;">
    <div class="row w-100 align-items-center">
        
        <div class="col-md-7 text-center text-md-start mb-5 mb-md-0">
            <h1 class="display-3 fw-bold" style="color: #1A2B4C;">
                UNI<span style="color: #E61E2A;">DEPORTES</span>
            </h1>
            <p class="lead fs-3" style="color: #333;">Sistema de Inventarios.</p>
        </div>

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
                            <p class="text-muted">Sesión activa como: <strong><?= $_SESSION['username'] ?></strong></p>
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
        </div>

    </div>
</div>

<?php include("footer.php"); ?>