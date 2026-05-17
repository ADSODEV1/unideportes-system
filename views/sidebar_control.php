<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = htmlspecialchars($_SESSION['username'] ?? 'Invitado');
$role = htmlspecialchars(ucfirst($_SESSION['role'] ?? 'colaborador'));
$current = basename($_SERVER['PHP_SELF']);
$sidebarExtra = $sidebarExtra ?? '';

function navLink($file, $label) {
    global $current;
    $class = $current === $file ? 'nav-link active' : 'nav-link';
    return "<a href=\"{$file}\" class=\"{$class}\">{$label}</a>";
}
?>

<aside class="sidebar-panel">
    <div class="sidebar-section">
        <h3>Panel de Control</h3>
        <p>Bienvenido:<br><strong><?= $user ?></strong></p>
        <p style="font-size: 0.85rem; color: rgba(255,255,255,0.8);">Rol: <?= $role ?></p>
    </div>
    <?= $sidebarExtra ?>
    <nav class="sidebar-nav">
        <?= navLink('panel_vendedor.php', '📊 Panel Principal') ?>
        <?= navLink('nueva_venta.php', '🛒 Nueva Venta') ?>
        <?= navLink('clientes.php', '👥 Clientes') ?>
        <?= navLink('pedidos.php', '📦 Pedidos') ?>
        <?= navLink('productos.php', '🏷️ Productos') ?>
        <a href="/unideportes-system/controllers/logout.php" class="nav-link logout">🚪 Salir</a>
    </nav>
</aside>
