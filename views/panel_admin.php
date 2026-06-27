<?php
// views/panel_admin.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = app();
require_login(['admin']);

// Consultas dinámicas optimizadas
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE role IN ('admin', 'vendedor', 'colaborador')");
    $total_colab = $stmt->fetchColumn() ?: 0;

    $stmtProd = $pdo->query("SELECT COUNT(*) FROM productos WHERE estado = 'activo'");
    $total_productos = $stmtProd->fetchColumn() ?: 0;

    $stmtVentas = $pdo->query("SELECT COUNT(*) FROM ventas WHERE DATE(fecha_venta) = CURRENT_DATE");
    $ventas_hoy = $stmtVentas->fetchColumn() ?: 0;

    $stmtBajoStock = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock <= 5 AND stock > 0");
    $productos_bajo_stock = $stmtBajoStock->fetchColumn() ?: 0;

    $stmtPedidos = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado != 'Entregado'");
    $ordenes_taller = $stmtPedidos->fetchColumn() ?: 0;

} catch (Exception $e) {
    $total_colab = $total_productos = $ventas_hoy = $productos_bajo_stock = $ordenes_taller = 0;
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Panel de Administración</h1>
                <p>Centro de control global de Unideportes.</p>
            </div>
            <div class="header-stats">
                <span class="stat-item"><?= htmlspecialchars($total_colab) ?> Personal</span>
                <span class="stat-item"><?= htmlspecialchars($total_productos) ?> Productos</span>
            </div>
        </div>

        <!-- ALERTAS -->
        <div class="alert-grid">
            <div class="alert-card alert-success">
                <div class="alert-icon">📈</div>
                <div class="alert-content">
                    <strong>Actividad Comercial Hoy</strong>
                    <p>Se procesaron <strong><?= htmlspecialchars($ventas_hoy) ?></strong> ventas. Hay <strong><?= htmlspecialchars($ordenes_taller) ?></strong> prendas en taller.</p>
                </div>
            </div>

            <div class="alert-card <?= $productos_bajo_stock > 0 ? 'alert-danger' : 'alert-neutral' ?>">
                <div class="alert-icon">⚠️</div>
                <div class="alert-content">
                    <strong>Alertas de Stock</strong>
                    <p>
                        <?= $productos_bajo_stock > 0 
                            ? "Hay <strong>".htmlspecialchars($productos_bajo_stock)."</strong> producto(s) con existencias bajas." 
                            : "Todos los productos cuentan con stock estable." ?>
                    </p>
                </div>
                <?php if ($productos_bajo_stock > 0): ?>
                    <a href="/unideportes-system/views/inventario.php" class="btn-alert">Ver Inventario</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- MENÚ DE ACCIONES -->
        <h2 class="section-title">Acciones de Administración</h2>

        <div class="menu-grid">
            
            <a href="/unideportes-system/views/panel_produccion.php" class="card">
                <div class="card-icon">🏭</div>
                <div class="card-body">
                    <h3>Línea de Confección</h3>
                    <p>Monitorea y cambia las fases de fabricación en taller.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/mis_pedidos.php" class="card">
                <div class="card-icon">📦</div>
                <div class="card-body">
                    <h3>Despacho / Entregas</h3>
                    <p>Controla saldos y gestiona entregas de pedidos.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/nueva_venta.php" class="card">
                <div class="card-icon">🛒</div>
                <div class="card-body">
                    <h3>Realizar Venta</h3>
                    <p>Abre el POS para facturar productos de stock.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/inventario.php" class="card">
                <div class="card-icon">🎽</div>
                <div class="card-body">
                    <h3>Control de Productos</h3>
                    <p>Edita precios, añade referencias y gestiona stock.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/clientes.php" class="card">
                <div class="card-icon">👥</div>
                <div class="card-body">
                    <h3>Base de Clientes</h3>
                    <p>Administra historiales y datos comerciales.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/admin_usuarios.php" class="card">
                <div class="card-icon">👤</div>
                <div class="card-body">
                    <h3>Gestionar Personal</h3>
                    <p>Crea cuentas, asigna roles y resetea claves.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/reportes_ventas.php" class="card">
                <div class="card-icon">📜</div>
                <div class="card-body">
                    <h3>Reportes Financieros</h3>
                    <p>Inspecciona ingresos y rendimiento comercial.</p>
                </div>
            </a>

        </div>

    </main>
</div>

<style>
/* ============================================
   PANEL ADMIN - ESTILOS SIMPLIFICADOS
   ============================================ */

/* Encabezado */
.page-header {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.page-header h1 {
    color: #1e293b;
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0;
}

.page-header p {
    color: #64748b;
    margin: 5px 0 0 0;
    font-size: 0.95rem;
}

.header-stats {
    display: flex;
    gap: 15px;
}

.stat-item {
    background: white;
    padding: 8px 16px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    font-weight: 600;
    color: #475569;
    font-size: 0.9rem;
}

/* Grid de alertas */
.alert-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.alert-card {
    padding: 18px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
}

.alert-icon {
    font-size: 1.8rem;
    flex-shrink: 0;
}

.alert-content {
    flex: 1;
}

.alert-content strong {
    display: block;
    margin-bottom: 4px;
    font-size: 0.95rem;
}

.alert-content p {
    font-size: 0.85rem;
    margin: 0;
    line-height: 1.4;
}

.alert-success {
    background: #f0fdf4;
    border-left: 4px solid #10b981;
    color: #065f46;
}

.alert-danger {
    background: #fef2f2;
    border-left: 4px solid #ef4444;
    color: #991b1b;
}

.alert-neutral {
    background: #f8fafc;
    border-left: 4px solid #94a3b8;
    color: #475569;
}

.btn-alert {
    background: #ef4444;
    color: white;
    text-decoration: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    white-space: nowrap;
    transition: background 0.2s;
}

.btn-alert:hover {
    background: #dc2626;
}

/* Título de sección */
.section-title {
    color: #475569;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
}

/* Grid de tarjetas */
.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
}

/* Tarjetas */
.card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.card:hover {
    border-color: #2563eb;
    box-shadow: 0 4px 6px rgba(37, 99, 235, 0.1);
    transform: translateY(-2px);
}

.card-icon {
    font-size: 2rem;
    line-height: 1;
    flex-shrink: 0;
}

.card-body h3 {
    margin: 0;
    font-size: 1.05rem;
    color: #1e293b;
    font-weight: 600;
}

.card-body p {
    margin: 6px 0 0 0;
    font-size: 0.85rem;
    color: #64748b;
    line-height: 1.4;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        text-align: center;
    }
    
    .header-stats {
        justify-content: center;
    }
    
    .alert-grid {
        grid-template-columns: 1fr;
    }
    
    .menu-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>