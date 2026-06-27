<?php
// views/sidebar_control.php
// Sidebar inteligente con permisos por rol - Proyecto SENA
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = htmlspecialchars($_SESSION['username'] ?? 'Invitado');
$role = $_SESSION['role'] ?? 'vendedor';
$current = basename($_SERVER['PHP_SELF']);
$pdo = app();

// ============================================
// CONFIGURACIÓN DE MENÚS POR ROL (REESTRUCTURADA)
// ============================================
$menuConfig = [
    'vendedor' => [
        'titulo_area' => 'Punto de Venta',
        'principales' => [
            'panel_vendedor.php' => 'Mi Panel',
            'nueva_venta.php' => 'Nueva Venta',
            'venta_mayorista.php' => 'Venta Mayorista',
            'nuevo_pedido.php' => 'Pedido de Confección'  // ✅ AGREGADO
        ],
        'secundarios' => [
            'mis_ventas.php' => 'Mis Ventas',
            'mis_pedidos.php' => 'Mis Pedidos',
            'ventas_mayoristas_pendientes.php' => 'Cobros Mayoristas',
            'clientes.php' => 'Clientes',
            'inventario.php' => 'Inventario',
            'reportes_ventas.php' => 'Mis Reportes'
        ]
    ],
    'colaborador' => [
        'titulo_area' => 'Área de Producción',
        'principales' => [
            'panel_vendedor.php' => 'Panel General',
            'nuevo_pedido.php' => 'Nuevo Pedido',  // ✅ AGREGADO
            'pedidos_admin.php' => 'Línea de Confección',
            'panel_produccion.php' => 'Gestión de Taller'
        ],
        'secundarios' => [
            'mis_pedidos.php' => 'Despacho / Entregas',
            'inventario.php' => 'Inventario'
        ]
    ],
    'admin' => [
        'titulo_area' => 'Administración',
        'principales' => [
            'panel_admin.php' => 'Dashboard',
            'nueva_venta.php' => 'Realizar Venta',
            'venta_mayorista.php' => 'Venta Mayorista',
            'nuevo_pedido.php' => 'Nuevo Pedido'  // ✅ AGREGADO
        ],
        'secundarios' => [
            'pedidos_admin.php' => 'Línea de Confección',
            'panel_produccion.php' => 'Gestión de Taller',
            'gestion_precios_confeccion.php' => 'Precios de Confección',
            'mis_pedidos.php' => 'Despacho / Entregas',
            'mis_ventas.php' => 'Historial de Ventas',
            'ventas_mayoristas_pendientes.php' => 'Cobros Mayoristas',
            'registrar_productos.php' => 'Registrar Productos',
            'inventario.php' => 'Control de Productos',
            'clientes.php' => 'Clientes',
            'admin_usuarios.php' => 'Gestionar Personal',
            'reportes_ventas.php' => 'Reportes Globales'
        ]
    ]
];

$configActual = $menuConfig[$role] ?? $menuConfig['vendedor'];

// FUNCIÓN AUXILIAR PARA RENDERIZAR ENLACES
if (!function_exists('renderNavLink')) {
    function renderNavLink($file, $label) {
        global $current;
        $url = "/unideportes-system/views/{$file}";
        $isActive = ($current === $file) ? 'active' : '';
        return "<a href=\"{$url}\" class=\"nav-link {$isActive}\">{$label}</a>";
    }
}

// ============================================
// ALERTAS IMPORTANTES (máximo 5, según rol)
// ============================================
$alertas = [];

try {
    // 1. PEDIDOS VENCIDOS (todos los roles)
    $stmt = $pdo->query("
        SELECT COUNT(*) as total
        FROM pedidos
        WHERE estado != 'Entregado'
        AND fecha_entrega < CURDATE()
    ");
    $vencidos = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($vencidos['total'] > 0) {
        $alertas[] = [
            'tipo' => 'danger',
            'icono' => '🚨',
            'texto' => "{$vencidos['total']} pedido(s) vencido(s)",
            'url' => '/unideportes-system/views/mis_pedidos.php'
        ];
    }

    // 2. PEDIDOS LISTOS PARA ENTREGAR (todos)
    $stmt = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'Terminado'");
    $pedidosListos = $stmt->fetchColumn();
    if ($pedidosListos > 0) {
        $alertas[] = [
            'tipo' => 'success',
            'icono' => '📦',
            'texto' => "{$pedidosListos} pedido(s) listo(s)",
            'url' => '/unideportes-system/views/mis_pedidos.php'
        ];
    }

    // 3. VENTAS MAYORISTAS CON SALDO (admin y vendedor)
    if ($role === 'admin' || $role === 'vendedor') {
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM ventas
            WHERE tipo_venta = 'mayorista'
            AND estado = 'Pendiente'
            AND saldo_pendiente > 0
        ");
        $ventasPendientes = $stmt->fetchColumn();
        if ($ventasPendientes > 0) {
            $alertas[] = [
                'tipo' => 'warning',
                'icono' => '💰',
                'texto' => "{$ventasPendientes} venta(s) con saldo",
                'url' => '/unideportes-system/views/ventas_mayoristas_pendientes.php'
            ];
        }
    }

    // 4. STOCK BAJO (solo admin)
    if ($role === 'admin') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock <= 5 AND stock > 0");
        $stockBajo = $stmt->fetchColumn();
        if ($stockBajo > 0) {
            $alertas[] = [
                'tipo' => 'warning',
                'icono' => '⚠️',
                'texto' => "{$stockBajo} producto(s) stock bajo",
                'url' => '/unideportes-system/views/inventario.php'
            ];
        }
    }

    // 5. VENTAS HOY (solo vendedor)
    if ($role === 'vendedor') {
        $vendedorId = $_SESSION['user_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE vendedor_id = ? AND DATE(fecha_venta) = CURRENT_DATE");
        $stmt->execute([$vendedorId]);
        $ventasHoy = $stmt->fetchColumn();
        if ($ventasHoy > 0) {
            $alertas[] = [
                'tipo' => 'info',
                'icono' => '📈',
                'texto' => "{$ventasHoy} venta(s) hoy",
                'url' => '/unideportes-system/views/mis_ventas.php'
            ];
        }
    }

    // Limitar a máximo 5 alertas
    $alertas = array_slice($alertas, 0, 5);

} catch (Exception $e) {
    // Si hay error, no mostramos alertas
}

// ============================================
// MÓDULOS PARA BUSCADOR INTELIGENTE (según rol)
// ============================================
$modulosBusqueda = [
    // Módulos comunes
    ['nombre' => 'Mi Panel', 'url' => 'panel_vendedor.php', 'icono' => '📊', 'desc' => 'Dashboard principal', 'roles' => ['vendedor', 'colaborador']],
    ['nombre' => 'Panel Admin', 'url' => 'panel_admin.php', 'icono' => '📊', 'desc' => 'Dashboard de administración', 'roles' => ['admin']],
    ['nombre' => 'Nueva Venta', 'url' => 'nueva_venta.php', 'icono' => '🛒', 'desc' => 'Registrar venta directa', 'roles' => ['vendedor', 'admin']],
    ['nombre' => 'Venta Mayorista', 'url' => 'venta_mayorista.php', 'icono' => '📦', 'desc' => 'Venta al por mayor', 'roles' => ['vendedor', 'admin']],
    ['nombre' => 'Cobros Mayoristas', 'url' => 'ventas_mayoristas_pendientes.php', 'icono' => '💰', 'desc' => 'Ventas con saldo pendiente', 'roles' => ['admin', 'vendedor']],
    ['nombre' => 'Pedido de Confección', 'url' => 'nuevo_pedido.php', 'icono' => '🏭', 'desc' => 'Crear orden de fabricación', 'roles' => ['vendedor', 'colaborador', 'admin']],  // ✅ AGREGADO
    
    // Ventas
    ['nombre' => 'Mis Ventas', 'url' => 'mis_ventas.php', 'icono' => '📈', 'desc' => 'Historial de ventas', 'roles' => ['vendedor', 'admin']],
    ['nombre' => 'Historial de Ventas', 'url' => 'mis_ventas.php', 'icono' => '📈', 'desc' => 'Todas las ventas del sistema', 'roles' => ['admin']],
    ['nombre' => 'Reportes de Ventas', 'url' => 'reportes_ventas.php', 'icono' => '📜', 'desc' => 'Reportes financieros', 'roles' => ['vendedor', 'admin']],
    
    // Pedidos y Producción
    ['nombre' => 'Mis Pedidos', 'url' => 'mis_pedidos.php', 'icono' => '📋', 'desc' => 'Despacho y entregas', 'roles' => ['vendedor', 'colaborador', 'admin']],
    ['nombre' => 'Línea de Confección', 'url' => 'pedidos_admin.php', 'icono' => '🏭', 'desc' => 'Gestión de pedidos en fábrica', 'roles' => ['colaborador', 'admin']],
    ['nombre' => 'Gestión de Taller', 'url' => 'panel_produccion.php', 'icono' => '👷', 'desc' => 'Control de producción', 'roles' => ['colaborador', 'admin']],
    ['nombre' => 'Precios de Confección', 'url' => 'gestion_precios_confeccion.php', 'icono' => '💵', 'desc' => 'Configurar precios base', 'roles' => ['admin']],
    
    // Productos e Inventario
    ['nombre' => 'Inventario', 'url' => 'inventario.php', 'icono' => '🎽', 'desc' => 'Control de productos', 'roles' => ['vendedor', 'colaborador', 'admin']],
    ['nombre' => 'Registrar Productos', 'url' => 'registrar_productos.php', 'icono' => '➕', 'desc' => 'Agregar nueva mercancía', 'roles' => ['admin']],
    
    // Clientes
    ['nombre' => 'Clientes', 'url' => 'clientes.php', 'icono' => '👥', 'desc' => 'Base de clientes', 'roles' => ['vendedor', 'admin']],
    ['nombre' => 'Nuevo Cliente', 'url' => 'nuevo_cliente.php', 'icono' => '👤', 'desc' => 'Registrar nuevo cliente', 'roles' => ['vendedor', 'admin']],
    
    // Administración
    ['nombre' => 'Gestionar Personal', 'url' => 'admin_usuarios.php', 'icono' => '🔐', 'desc' => 'Administración de usuarios', 'roles' => ['admin']],
];

// Filtrar módulos según el rol del usuario
$modulosFiltrados = array_filter($modulosBusqueda, function($mod) use ($role) {
    return in_array($role, $mod['roles']);
});
$modulosJSON = json_encode(array_values($modulosFiltrados));
?>

<aside class="sidebar-panel">
    
    <!-- 🔍 BUSCADOR INTELIGENTE -->
    <div class="sidebar-search">
        <input type="text" id="smartSearch" class="search-input" 
               placeholder="🔍 Buscar módulo, cliente o producto..." 
               autocomplete="off">
        <div id="searchResults" class="search-results"></div>
    </div>

    <!-- PERFIL DE USUARIO -->
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
        <!-- ALERTAS IMPORTANTES -->
        <?php if (!empty($alertas)): ?>
            <div class="nav-group nav-group-alertas">
                <span class="group-title collapsible-header" onclick="toggleSection('alertas')">
                    🔔 Alertas Activas <span class="toggle-icon" id="icon-alertas">▼</span>
                </span>
                <div id="section-alertas" class="collapsible-content">
                    <?php foreach ($alertas as $alerta): ?>
                        <a href="<?= $alerta['url'] ?>" class="nav-alerta nav-alerta-<?= $alerta['tipo'] ?>">
                            <span class="alerta-icono"><?= $alerta['icono'] ?></span>
                            <span class="alerta-texto"><?= $alerta['texto'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- MENÚ PRINCIPAL -->
        <div class="nav-group">
            <span class="group-title collapsible-header" onclick="toggleSection('principal')">
                Principal <span class="toggle-icon" id="icon-principal">▼</span>
            </span>
            <div id="section-principal" class="collapsible-content">
                <?php foreach ($configActual['principales'] as $archivo => $texto): ?>
                    <?= renderNavLink($archivo, $texto) ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- MENÚ SECUNDARIO (COLAPSABLE) -->
        <?php if (!empty($configActual['secundarios'])): ?>
            <div class="nav-group nav-group-secondary">
                <span class="group-title collapsible-header" onclick="toggleSection('secundarios')">
                    Consultas <span class="toggle-icon" id="icon-secundarios">▶</span>
                </span>
                <div id="section-secundarios" class="collapsible-content" style="display: none;">
                    <?php foreach ($configActual['secundarios'] as $archivo => $texto): ?>
                        <?= renderNavLink($archivo, $texto) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </nav>
</aside>

<style>
/* ============================================
   SIDEBAR - ESTILOS LIMPIOS
   ============================================ */

.sidebar-panel {
    width: 240px;
    background: #ffffff;
    color: #1e293b;
    padding: 20px 0;
    min-height: calc(100vh - 60px);
    border-right: 1px solid #e2e8f0;
    overflow-y: auto;
    transition: all 0.3s;
}

/* BUSCADOR INTELIGENTE */
.sidebar-search {
    position: relative;
    padding: 0 20px 15px;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 15px;
}

.search-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: all 0.2s;
    box-sizing: border-box;
}

.search-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Resultados del buscador */
.search-results {
    position: absolute;
    top: 100%;
    left: 20px;
    right: 20px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
    margin-top: 5px;
    display: none;
}

.search-results.active {
    display: block;
}

.search-result-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    cursor: pointer;
    transition: background 0.2s;
    border-bottom: 1px solid #f1f5f9;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-item:hover {
    background: #f8fafc;
}

.result-icon {
    font-size: 1.2rem;
    flex-shrink: 0;
}

.result-info {
    flex: 1;
    min-width: 0;
}

.result-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.result-desc {
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.search-no-results {
    padding: 15px;
    text-align: center;
    color: #64748b;
    font-size: 0.9rem;
}

.search-loading {
    padding: 15px;
    text-align: center;
    color: #64748b;
    font-size: 0.9rem;
}

/* Colores por tipo de resultado */
.result-modulo { border-left: 3px solid #2563eb; }
.result-cliente { border-left: 3px solid #10b981; }
.result-producto { border-left: 3px solid #f59e0b; }

/* Badge de tipo */
.result-type-badge {
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 600;
    text-transform: uppercase;
    flex-shrink: 0;
}

.badge-modulo { background: #dbeafe; color: #1e40af; }
.badge-cliente { background: #d1fae5; color: #065f46; }
.badge-producto { background: #fef3c7; color: #92400e; }

/* Perfil de usuario */
.sidebar-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 20px 20px;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 20px;
}

.profile-avatar {
    width: 40px;
    height: 40px;
    background: #2563eb;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.profile-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.profile-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-role {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 2px;
}

/* Grupos de navegación */
.nav-group {
    margin-bottom: 20px;
}

.group-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 20px;
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 8px;
    letter-spacing: 0.5px;
    cursor: pointer;
    user-select: none;
    transition: color 0.2s;
}

.group-title:hover {
    color: #2563eb;
}

.toggle-icon {
    font-size: 0.7rem;
    transition: transform 0.3s;
}

.collapsible-content {
    transition: all 0.3s ease;
    overflow: hidden;
}

/* Enlaces de navegación */
.nav-link {
    display: block;
    padding: 10px 20px;
    color: #475569;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s;
    border-left: 3px solid transparent;
    border-radius: 0 6px 6px 0;
    margin: 2px 0;
}

.nav-link:hover {
    background: #e0f2fe;
    color: #0284c7;
    border-left-color: #0284c7;
}

.nav-link.active {
    background: #dbeafe;
    color: #1e40af;
    border-left-color: #2563eb;
    font-weight: 600;
}

/* Alertas simplificadas */
.nav-alerta {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    margin: 4px 0;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.2s;
    font-size: 0.85rem;
    background: white;
    border: 1px solid #e2e8f0;
}

.nav-alerta:hover {
    transform: translateX(3px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.nav-alerta-danger {
    background: #fee2e2;
    color: #991b1b;
    border-left: 3px solid #ef4444;
}

.nav-alerta-warning {
    background: #fef3c7;
    color: #92400e;
    border-left: 3px solid #f59e0b;
}

.nav-alerta-success {
    background: #d1fae5;
    color: #065f46;
    border-left: 3px solid #10b981;
}

.nav-alerta-info {
    background: #dbeafe;
    color: #1e40af;
    border-left: 3px solid #3b82f6;
}

.alerta-icono {
    font-size: 1rem;
}

.alerta-texto {
    font-weight: 500;
}

/* Responsive para móvil */
@media (max-width: 768px) {
    .sidebar-panel {
        width: 100%;
        max-width: 280px;
    }
    
    .search-results {
        position: fixed;
        top: 120px;
        left: 10px;
        right: 10px;
        width: auto;
    }
}
</style>

<script>
// ============================================
// FUNCIONES DE TOGGLE (Secciones colapsables)
// ============================================
function toggleSection(name) {
    const section = document.getElementById('section-' + name);
    const icon = document.getElementById('icon-' + name);
    
    if (section.style.display === 'none' || !section.style.display) {
        section.style.display = 'block';
        icon.textContent = '▼';
    } else {
        section.style.display = 'none';
        icon.textContent = '▶';
    }
}

// ============================================
// BUSCADOR INTELIGENTE (Smart Search)
// ============================================
(function() {
    // Módulos disponibles según el rol del usuario
    const modulos = <?= $modulosJSON ?>;
    
    const smartSearch = document.getElementById('smartSearch');
    const searchResults = document.getElementById('searchResults');
    
    if (!smartSearch || !searchResults) return;
    
    // Variable para debounce (evitar muchas peticiones)
    let debounceTimer = null;
    
    smartSearch.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        
        // Limpiar timer anterior
        clearTimeout(debounceTimer);
        
        // Si la consulta es muy corta, limpiar resultados
        if (query.length < 2) {
            searchResults.innerHTML = '';
            searchResults.classList.remove('active');
            return;
        }
        
        // Aplicar debounce (esperar 300ms antes de buscar)
        debounceTimer = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    async function performSearch(query) {
        const results = [];
        
        // 1. Buscar en módulos del sistema (instantáneo)
        modulos.forEach(mod => {
            const nombreMatch = mod.nombre.toLowerCase().includes(query);
            const descMatch = mod.desc.toLowerCase().includes(query);
            if (nombreMatch || descMatch) {
                results.push({
                    tipo: 'modulo',
                    nombre: mod.nombre,
                    url: '/unideportes-system/views/' + mod.url,
                    icono: mod.icono,
                    desc: mod.desc,
                    prioridad: nombreMatch ? 1 : 2
                });
            }
        });
        
        // 2. Buscar clientes (AJAX)
        try {
            const resClientes = await fetch(`../controllers/buscar_clientes_ajax.php?q=${encodeURIComponent(query)}`);
            if (resClientes.ok) {
                const clientes = await resClientes.json();
                if (Array.isArray(clientes)) {
                    clientes.forEach(cli => {
                        results.push({
                            tipo: 'cliente',
                            nombre: cli.nombre_completo,
                            url: `/unideportes-system/views/clientes.php?search=${encodeURIComponent(cli.nombre_completo)}`,
                            icono: '👤',
                            desc: `NIT: ${cli.nit_cedula}`,
                            prioridad: 3
                        });
                    });
                }
            }
        } catch (e) {
            console.warn('No se pudieron cargar clientes:', e.message);
        }
        
        // 3. Buscar productos (AJAX)
        try {
            const resProductos = await fetch(`../controllers/buscar_productos_ajax.php?q=${encodeURIComponent(query)}`);
            if (resProductos.ok) {
                const productos = await resProductos.json();
                if (Array.isArray(productos)) {
                    productos.forEach(prod => {
                        results.push({
                            tipo: 'producto',
                            nombre: prod.nombre,
                            url: `/unideportes-system/views/inventario.php?q=${encodeURIComponent(prod.nombre)}`,
                            icono: '📦',
                            desc: `Ref: ${prod.referencia} • Stock: ${prod.stock}`,
                            prioridad: 3
                        });
                    });
                }
            }
        } catch (e) {
            console.warn('No se pudieron cargar productos:', e.message);
        }
        
        // Ordenar por prioridad (módulos primero)
        results.sort((a, b) => a.prioridad - b.prioridad);
        
        // Renderizar resultados (máximo 10)
        renderResults(results.slice(0, 10));
    }
    
    function renderResults(results) {
        searchResults.innerHTML = '';
        
        if (results.length === 0) {
            searchResults.innerHTML = '<div class="search-no-results">No se encontraron resultados</div>';
            searchResults.classList.add('active');
            return;
        }
        
        results.forEach(result => {
            const div = document.createElement('div');
            div.className = `search-result-item result-${result.tipo}`;
            
            const badgeClass = `badge-${result.tipo}`;
            const badgeText = result.tipo === 'modulo' ? 'Módulo' : 
                             result.tipo === 'cliente' ? 'Cliente' : 'Producto';
            
            div.innerHTML = `
                <span class="result-icon">${result.icono}</span>
                <div class="result-info">
                    <div class="result-name">${escapeHtml(result.nombre)}</div>
                    <div class="result-desc">${escapeHtml(result.desc)}</div>
                </div>
                <span class="result-type-badge ${badgeClass}">${badgeText}</span>
            `;
            
            div.onclick = () => {
                window.location.href = result.url;
            };
            
            searchResults.appendChild(div);
        });
        
        searchResults.classList.add('active');
    }
    
    // Función para escapar HTML y prevenir XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.sidebar-search')) {
            searchResults.classList.remove('active');
        }
    });
    
    // Cerrar con tecla Escape
    smartSearch.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchResults.classList.remove('active');
            smartSearch.blur();
        }
        // Navegar con flechas (bonus)
        if (e.key === 'Enter') {
            const firstResult = searchResults.querySelector('.search-result-item');
            if (firstResult) firstResult.click();
        }
    });
    
    // Atajo de teclado: Ctrl+K o Cmd+K para enfocar el buscador
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            smartSearch.focus();
            smartSearch.select();
        }
    });
})();
</script>