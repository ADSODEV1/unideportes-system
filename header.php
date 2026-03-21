<?php
// header
$usuario_nombre = $_SESSION['username'] ?? 'Invitado';
$rol_usuario = $_SESSION['role'] ?? 'vendedor';
?>
<header class="main-header">
  <div class="nav-container">
    <a class="logo" href="<?= ($rol_usuario === 'admin') ? 'panel_admin.php' : 'panel_vendedor.php'; ?>">
      UNI<span style="color: #E61E2A">DEPORTES</span>
    </a>
    <img src="img/logounideportes.png" alt="logo" width="104" height="142" />
    <nav class="main-nav">
      <ul>
        <li><a href="inventario.php">Inventario</a></li>
        <li><a href="pedidos.php">Producción</a></li>
        <li><a href="clientes.php">Clientes</a></li>
        <?php if ($rol_usuario === 'admin'): ?>
          <li><a href="reportes_ventas.php">Reportes</a></li>
          <li><a href="admin_user.php">Personal</a></li>
        <?php endif; ?>
      </ul>
    </nav>

    <div class="user-info">
      <span>Hola, <strong><?= htmlspecialchars(ucfirst($usuario_nombre), ENT_QUOTES, 'UTF-8'); ?></strong></span>
      <a href="logout.php" class="btn-salir">Salir</a>
    </div>
  </div>
</header>