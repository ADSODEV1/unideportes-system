<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Datos base de la sesión
$user = htmlspecialchars($_SESSION['username'] ?? 'Invitado');
$role = $_SESSION['role'] ?? 'vendedor'; // 'administrador' o 'vendedor'
$current = basename($_SERVER['PHP_SELF']);

// 2. Configuración Inteligente de Roles (Títulos y Accesos Directos Clave)
$menuConfig = [
    'vendedor' => [
        'titulo_area' => 'Área Comercial',
        'principales' => [
            'nueva_venta.php' => '🛒 Nueva Venta',
            'panel_vendedor.php' => '📊 Mi Panel'
        ],
        'secundarios' => []
    ],
    'administrador' => [
        'titulo_area' => 'Panel Corporativo',
        'principales' => [
            'panel_admin.php' => '📊 Dashboard General',
            'productos.php' => '🏷️ Gestión Productos'
        ],
        'secundarios' => [
            'clientes.php' => '👥 Clientes',
            'pedidos.php' => '📦 Pedidos Fábrica',
            'nueva_venta.php' => '🛒 Módulo Venta'
        ]
    ]
];

// Obtener la configuración del rol actual (o usar vendedor por defecto si no existe)
$configActual = $menuConfig[$role] ?? $menuConfig['vendedor'];

/**
 * Renderiza un enlace evaluando si es el archivo actual en el DOM
 */
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
            <span class="group-title">Acciones Clave</span>
            <?php foreach ($configActual['principales'] as $archivo => $texto): ?>
                <?= renderNavLink($archivo, $texto, true) ?>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($configActual['secundarios'])): ?>
            <div class="nav-group" style="margin-top: 20px;">
                <span class="group-title">Administración</span>
                <?php foreach ($configActual['secundarios'] as $archivo => $texto): ?>
                    <?= renderNavLink($archivo, $texto, false) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        <a href="/unideportes-system/controllers/logout.php" class="btn-logout">
            <span>❌</span> Salir del Sistema
        </a>
    </div>
</aside>