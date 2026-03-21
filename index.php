<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UNI DEPORTES - Inventarios</title>
  <link rel="stylesheet" href="CSS/style.css" />
  <script src="https://kit.fontawesome.com/a772d1a60a.js" crossorigin="anonymous"></script>
</head>
<body>
  <?php include("header.php"); ?>

  <div class="site-wrapper">
    <div class="login-container">
      <h1>UNI<span style="color: var(--color-accion);">DEPORTES</span></h1>
      <p>Sistema de Inventarios</p>

      <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="users-form">
          <h3><i class="fa-solid fa-lock"></i> Ingreso al Sistema</h3>
          <form action="auth.php" method="POST">
            <label><i class="fa-solid fa-user"></i> Usuario:</label>
            <input type="text" name="username" required />
            <label><i class="fa-solid fa-key"></i> Contraseña:</label>
            <input type="password" name="password" required />
            <button type="submit" class="btn-guardar"><i class="fa-solid fa-right-to-bracket"></i> ENTRAR</button>
          </form>
          <?php if (isset($_GET['error'])): ?>
            <div class="alert">⚠️ Datos incorrectos.</div>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="welcome-box">
          <h2><i class="fa-solid fa-hand-wave"></i> ¡Hola de nuevo!</h2>
          <p>Sesión activa: <strong><?= htmlspecialchars($_SESSION['nombre'] ?? $_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></p>
          <a href="<?= ($_SESSION['rol'] ?? $_SESSION['role'] ?? '') === 'admin' ? 'admin_user.php' : 'panel_vendedor.php' ?>" class="btn-panel">
            <i class="fa-solid fa-gauge-high"></i> IR AL PANEL
          </a>
          <br><br>
          <a href="logout.php" style="color: var(--text-sec)">Cerrar sesión</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
 <?php include("footer.php"); ?>
</body>
</html>