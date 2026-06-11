<?php
// views/sidebar_control.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = htmlspecialchars($_SESSION['username'] ?? 'Invitado');
$role = $_SESSION['role'] ?? 'vendedor'; 
$current = basename($_SERVER['PHP_SELF']);

// Configuración de menús 100% sincronizada con las vistas reales del proyecto
$menuConfig = [
    'vendedor' => [
        'titulo_area' => '📍 Punto de Venta',
        'principales' => [
            'panel_vendedor.php' => '📊 Mi Panel Principal',
            'nueva_venta.php' => '🛒 Nueva Venta Directa'
        ],
        'secundarios' => [
            'mis_pedidos.php' => '🎁 Entregar Pedidos',
            'clientes.php' => '👥 Base de Clientes',
            'inventario.php' => '📦 Stock en Tiempo Real'
        ]
    ],
    'colaborador' => [
        'titulo_area' => '⚙️ Área de Producción',
        'principales' => [
            'panel_vendedor.php' => '📊 Panel General',
            'linea_confeccion.php' => '🏭 Línea de Confección'
        ],
        'secundarios' => [
            'inventario.php' => '📦 Stock de Unideportes'
        ]
    ],
    'admin' => [
        'titulo_area' => '⚡ Administrador Global',
        'principales' => [
            'panel_admin.php' => '📊 Dashboard de Control',
            'nueva_venta.php' => '🛒 Realizar Venta'
        ],
        'secundarios' => [
            'linea_confeccion.php' => '🏭 Línea de Confección',
            'registrar_productos.php' => ' 🆕 Registrar Productos',
            'panel_produccion.php' => '👷‍♂️ Gestión de Taller',
            'mis_pedidos.php' => '📦 Despacho / Entregas',
            'inventario.php' => '🎽 Control de Productos',
            'clientes.php' => '👥 Base de Clientes',
            'admin_usuarios.php' => '👤 Gestionar Personal',
            'reportes_ventas.php' => '📜 Reportes Globales'
        ]
    ]
];

$configActual = $menuConfig[$role] ?? $menuConfig['vendedor'];

function renderNavLink($file, $label, $isPriority = false) {
    global $current;
    $url = "/unideportes-system/views/{$file}";
    $isActive = ($current === $file) ? 'active' : '';
    $priorityClass = $isPriority ? 'nav-priority' : '';
    return "<a href=\"{$url}\" class=\"nav-link {$isActive} {$priorityClass}\">{$label}</a>";
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
            <span class="group-title">Operaciones Clave</span>
            <?php foreach ($configActual['principales'] as $archivo => $texto): ?>
                <?= renderNavLink($archivo, $texto, true) ?>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($configActual['secundarios'])): ?>
            <div class="nav-group nav-group-secondary">
                <span class="group-title">Módulos de Consulta</span>
                <?php foreach ($configActual['secundarios'] as $archivo => $texto): ?>
                    <?= renderNavLink($archivo, $texto, false) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($sidebarExtra)): ?>
            <div class="sidebar-dynamic-widgets">
                <?= $sidebarExtra ?>
            </div>
        <?php endif; ?>
    </nav>
</aside>

<style>
/* Estilos Semánticos Suplementarios para el Sidebar */
.nav-group-secondary {
    margin-top: 22px;
}
.sidebar-dynamic-widgets {
    margin-top: 25px; 
    padding-top: 15px; 
    border-top: 1px dashed rgba(255, 255, 255, 0.1);
}
</style>