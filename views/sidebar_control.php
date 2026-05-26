<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = htmlspecialchars($_SESSION['username'] ?? 'Invitado');
$role = $_SESSION['role'] ?? 'vendedor'; 
$current = basename($_SERVER['PHP_SELF']);

// Configuración de menús por rol
$menuConfig = [
    'vendedor' => [
        'titulo_area' => 'Punto de Venta',
        'principales' => [
            'nueva_venta.php' => '🛒 Nueva Venta',
            'panel_vendedor.php' => '📊 Mi Panel'
        ],
        'secundarios' => [
            'mis_ventas.php' => '📜 Mis Ventas',
            'clientes.php' => '👥 Clientes'
        ]
    ],
    'colaborador' => [
        'titulo_area' => 'Producción',
        'principales' => [
            'pedidos_admin.php' => '📦 Mis Pedidos',
            'panel_vendedor.php' => '📊 Panel General'
        ],
        'secundarios' => [
            'inventario.php' => '📦 Inventario'
        ]
    ],
    'admin' => [
        'titulo_area' => 'Administración',
        'principales' => [
            'panel_admin.php' => '📊 Dashboard',
            'productos.php' => '🏷️ Productos'
        ],
        'secundarios' => [
            'usuarios.php' => '👤 Usuarios',
            'clientes.php' => '👥 Clientes',
            'pedidos.php' => '📦 Pedidos',
            'reportes_ventas.php' => '📜 Reportes Globales'
        ]
    ]
];

$configActual = $menuConfig[$role] ?? $menuConfig['vendedor'];

function renderNavLink($file, $label, $isPriority = false) {
    global $current;
    $isActive = ($current === $file) ? 'active' : '';
    $priorityClass = $isPriority ? 'nav-priority' : '';
    return "<a href=\"{$file}\" class=\"nav-link {$isActive} {$priorityClass}\">{$label}</a>";
}
?>

<aside class="sidebar-panel">
    <div class="sidebar-profile">
        <div class="profile-avatar">
            <?= strtoupper(substr($user, 0, 2)) ?>
        </div>
        <div class="profile-info">
            <span class="profile-name"><?= $user ?></span>
            <span class="profile-role"><?= $configActual['titulo_area'] ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group">
            <span class="group-title">Acciones Principales</span>
            <?php foreach ($configActual['principales'] as $archivo => $texto): ?>
                <?= renderNavLink($archivo, $texto, true) ?>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($configActual['secundarios'])): ?>
            <div class="nav-group" style="margin-top: 20px;">
                <span class="group-title">Consultas</span>
                <?php foreach ($configActual['secundarios'] as $archivo => $texto): ?>
                    <?= renderNavLink($archivo, $texto, false) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($sidebarExtra)): ?>
            <div class="sidebar-dynamic-widgets" style="margin-top: 25px; padding-top: 15px; border-top: 1px dashed rgba(255,255,255,0.1);">
                <?= $sidebarExtra ?>
            </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="/unideportes-system/controllers/auth.php?logout=1" class="btn-logout">
            <span>🚪</span> Salir
        </a>
    </div>
</aside>