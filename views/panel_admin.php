<?php
// views/panel_admin.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = app();

// Seguridad: Solo el rol admin puede ingresar
require_login(['admin']);

// Consultas dinámicas optimizadas
try {
    // 1. Total de usuarios registrados (FILTRADO POR ROLES - de V2)
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE role IN ('admin', 'vendedor', 'colaborador')");
    $total_colab = $stmt->fetchColumn() ?: 0;

    // 2. Total de productos activos en el catálogo
    $stmtProd = $pdo->query("SELECT COUNT(*) FROM productos WHERE estado = 'activo'");
    $total_productos = $stmtProd->fetchColumn() ?: 0;

    // 3. Cantidad de ventas realizadas el día de HOY
    $stmtVentas = $pdo->query("SELECT COUNT(*) FROM ventas WHERE DATE(fecha_venta) = CURRENT_DATE");
    $ventas_hoy = $stmtVentas->fetchColumn() ?: 0;

    // 4. Productos con stock bajo (≤ 5) - CAMBIADO de V2 para ser más útil
    $stmtBajoStock = $pdo->query("SELECT COUNT(*) FROM productos WHERE estado = 'activo' AND stock <= 5 AND stock > 0");
    $productos_bajo_stock = $stmtBajoStock->fetchColumn() ?: 0;

    // 5. Productos agotados (stock = 0) - NUEVO de V1
    $stmtAgotados = $pdo->query("SELECT COUNT(*) FROM productos WHERE estado = 'activo' AND stock <= 0");
    $productos_agotados = $stmtAgotados->fetchColumn() ?: 0;

    // 6. Órdenes activas en taller que requieren seguimiento
    $stmtPedidos = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado != 'Entregado'");
    $ordenes_taller = $stmtPedidos->fetchColumn() ?: 0;

} catch (Exception $e) {
    $total_colab = $total_productos = $ventas_hoy = $productos_bajo_stock = $productos_agotados = $ordenes_taller = 0;
}

// Header del sistema
include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">

        <div class="page-header header-dashboard">
            <div>
                <h1>📊 Panel de Administración</h1>
                <p>
                    Centro de control global de Unideportes.
                    <span class="badge azul">👥 <?= htmlspecialchars($total_colab); ?> Personal</span>
                    <span class="badge azul">🏷️ <?= htmlspecialchars($total_productos); ?> Items Catálogo</span>
                </p>
            </div>
        </div>

        <div class="alert-grid">
            
            <div class="alert-card success">
                <div class="alert-icon">📈</div>
                <div class="alert-text">
                    <strong>Actividad Comercial Hoy</strong>
                    <p>Se procesaron <strong><?= htmlspecialchars($ventas_hoy) ?></strong> ventas. Hay <strong><?= htmlspecialchars($ordenes_taller) ?></strong> prendas en taller.</p>
                </div>
            </div>

            <div class="alert-card <?= ($productos_bajo_stock + $productos_agotados) > 0 ? 'danger' : 'neutral' ?>">
                <div class="alert-icon">⚠️</div>
                <div class="alert-text">
                    <strong>Alertas de Inventario</strong>
                    <p>
                        <?php if ($productos_agotados > 0): ?>
                            ¡Atención! Hay <strong><?= htmlspecialchars($productos_agotados) ?></strong> producto(s) agotado(s).
                        <?php endif; ?>
                        <?php if ($productos_bajo_stock > 0): ?>
                            <br>Hay <strong><?= htmlspecialchars($productos_bajo_stock) ?></strong> producto(s) con stock bajo (≤ 5 unidades).
                        <?php endif; ?>
                        <?php if ($productos_bajo_stock + $productos_agotados === 0): ?>
                            Todos los productos cuentan con stock adecuado.
                        <?php endif; ?>
                    </p>
                </div>
                <?php if (($productos_bajo_stock + $productos_agotados) > 0): ?>
                    <a href="/unideportes-system/views/inventario.php" class="btn-alert-action">Ver</a>
                <?php endif; ?>
            </div>

        </div>

        <h3 class="section-title">Acciones de Administración</h3>

        <div class="menu-maestro">
            
            <div class="dashboard-card border-green">
                <a href="/unideportes-system/views/linea_confeccion.php" class="card-link">
                    <div class="card-icon">🏭</div>
                    <div class="card-body">
                        <h3>Línea de Confección</h3>
                        <p>Monitorea y cambia las fases de fabricación en taller (Corte, Costura, Terminado).</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card border-emerald">
                <a href="/unideportes-system/views/mis_pedidos.php" class="card-link">
                    <div class="card-icon">📦</div>
                    <div class="card-body">
                        <h3>Despacho / Entregas</h3>
                        <p>Busca pedidos por cliente, controla saldos monetarios y gestiona entregas.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card border-red">
                <a href="/unideportes-system/views/nueva_venta.php" class="card-link">
                    <div class="card-icon">🛒</div>
                    <div class="card-body">
                        <h3>Realizar Venta</h3>
                        <p>Abre el POS de mostrador para facturar uniformes listos de stock común.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card border-amber">
                <a href="/unideportes-system/views/venta_mayorista.php" class="card-link">
                    <div class="card-icon">🧵</div>
                    <div class="card-body">
                        <h3>Nueva Venta Mayorista</h3>
                        <p>Genera un pedido de confección por volumen y administra abonos desde la línea de producción.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card border-slate">
                <a href="/unideportes-system/views/inventario.php" class="card-link">
                    <div class="card-icon">🎽</div>
                    <div class="card-body">
                        <h3>Control de Productos</h3>
                        <p>Edita precios bases, añade nuevas referencias y gestiona tallajes de fábrica.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card border-blue">
                <a href="/unideportes-system/views/clientes.php" class="card-link">
                    <div class="card-icon">👥</div>
                    <div class="card-body">
                        <h3>Base de Clientes</h3>
                        <p>Administración de historiales, NITs, bases de datos comerciales y estados.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card border-indigo">
                <a href="/unideportes-system/views/admin_usuarios.php" class="card-link">
                    <div class="card-icon">👤</div>
                    <div class="card-body">
                        <h3>Gestionar Personal</h3>
                        <p>Alta de cuentas de trabajadores, asignación de roles y reseteo de claves.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card border-blue">
                <a href="/unideportes-system/views/soporte_tecnico.php" class="card-link">
                    <div class="card-icon">🛠️</div>
                    <div class="card-body">
                        <h3>Soporte Técnico</h3>
                        <p>Gestiona tickets/incidencias, prioridades y respuestas de solución.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card border-amber full-width-card">
                <a href="/unideportes-system/views/reportes_ventas.php" class="card-link link-wide">
                    <div class="card-icon icon-small">📜</div>
                    <div class="card-body">
                        <h3>Reportes Financieros y Auditoría Global</h3>
                        <p>Inspecciona ingresos brutos diarios, históricos y rendimiento comercial individual por vendedor.</p>
                    </div>
                </a>
            </div>

        </div>

    </main>
</div>

<style>
/* Estilos Semánticos y Limpios para el Dashboard */
.header-dashboard {
    background: var(--card);
    padding: 20px;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    margin-bottom: 30px;
}
.alert-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 35px;
}
.alert-card {
    border: 1px solid var(--border);
    padding: 15px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.alert-card.success { background: #f0fdf4; border-left: 4px solid var(--success); color: #166534; }
.alert-card.danger { background: #fef2f2; border-left: 4px solid var(--danger); color: #991b1b; }
.alert-card.neutral { background: #f8fafc; border-left: 4px solid var(--text-light); color: var(--text); }
.alert-icon { font-size: 1.6rem; }
.alert-text p { font-size: 0.85rem; margin: 2px 0 0 0; opacity: 0.9; }

.btn-alert-action {
    background: var(--danger);
    color: white;
    text-decoration: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-left: auto;
}
.section-title { color: var(--text-light); font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; }

.menu-maestro {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
}
.dashboard-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    transition: transform 0.2s, box-shadow 0.2s;
}
.dashboard-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}
.border-green  { border-top: 4px solid #16a34a; }
.border-emerald{ border-top: 4px solid var(--success); }
.border-red    { border-top: 4px solid var(--primary); }
.border-slate  { border-top: 4px solid var(--text-light); }
.border-blue   { border-top: 4px solid #0284c7; }
.border-indigo { border-top: 4px solid #4f46e5; }
.border-amber  { border-top: 4px solid var(--warning); }

.card-link { display: flex; align-items: flex-start; padding: 24px 20px; text-decoration: none; gap: 15px; }
.card-icon { font-size: 2rem; line-height: 1; }
.card-body h3 { margin: 0; font-size: 1.05rem; color: var(--navy); font-weight: 600; }
.card-body p { margin: 6px 0 0 0; font-size: 0.85rem; color: var(--text-light); line-height: 1.4; }

.full-width-card { grid-column: 1 / -1; }
.link-wide { padding: 15px 22px; }
.icon-small { font-size: 1.5rem; }

/* Responsive - AGREGADO de V2 */
@media (max-width: 768px) {
    .header-dashboard {
        text-align: center;
    }
    
    .alert-grid {
        grid-template-columns: 1fr;
    }
    
    .menu-maestro {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>