<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = app();

// Seguridad: Solo admin puede entrar
require_login(['admin']);

// Consultas dinámicas usando PDO para los indicadores del Administrador
try {
    // 1. Total Colaboradores activos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE role = 'colaborador'");
    $total_colab = $stmt->fetchColumn() ?: 0;

    // 2. Total Productos en el catálogo
    $stmtProd = $pdo->query("SELECT COUNT(*) as total FROM productos");
    $total_productos = $stmtProd->fetchColumn() ?: 0;

    // 3. Ventas de stock común hoy
    $stmtVentas = $pdo->query("SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha_venta) = CURRENT_DATE");
    $ventas_hoy = $stmtVentas->fetchColumn() ?: 0;

    // 4. NUEVO KPI: Órdenes activas en taller (Para dar visibilidad al Admin)
    $stmtPedidos = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE estado != 'Entregado'");
    $ordenes_taller = $stmtPedidos->fetchColumn() ?: 0;

} catch (Exception $e) {
    $total_colab = 0;
    $total_productos = 0;
    $ventas_hoy = 0;
    $ordenes_taller = 0;
}

// Header del sistema
include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">

        <div class="page-header">
            <h1>📊 Panel de Administración</h1>
            <p>Bienvenido al centro de control global de Unideportes. Monitorea el rendimiento de tu negocio.</p>
        </div>

        <div class="resumen-kpi">
            
            <div class="kpi-card">
                <div class="kpi-icon icon-red">👥</div>
                <div class="kpi-data">
                    <small>Personal Activo</small>
                    <h2><?= htmlspecialchars($total_colab); ?> <span>Colab.</span></h2>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon icon-blue">🏷️</div>
                <div class="kpi-data">
                    <small>Catálogo</small>
                    <h2><?= htmlspecialchars($total_productos); ?> <span>Productos</span></h2>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon icon-green">📈</div>
                <div class="kpi-data">
                    <small>Ventas de Hoy</small>
                    <h2><?= htmlspecialchars($ventas_hoy); ?> <span>Órdenes</span></h2>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon icon-amber">⚙️</div>
                <div class="kpi-data">
                    <small>En Producción</small>
                    <h2><?= htmlspecialchars($ordenes_taller); ?> <span>Prendas</span></h2>
                </div>
            </div>

        </div>

        <h3 class="section-title">Acciones de Administración</h3>

        <div class="menu-maestro">
            
            <div class="dashboard-card card-highlight">
                <a href="/unideportes-system/views/pedidos_admin.php" class="card-link">
                    <div class="card-icon">🏭</div>
                    <div class="card-body">
                        <h3>Línea de Confección</h3>
                        <p>Monitorea y cambia las fases de fabricación (Corte, Costura, Terminado).</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card card-highlight">
                <a href="/unideportes-system/views/pedidos_vendedor.php" class="card-link">
                    <div class="card-icon">📦</div>
                    <div class="card-body">
                        <h3>Despacho Mayorista</h3>
                        <p>Busca pedidos por cliente/NIT, registra el pago de saldos y entrega.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card">
                <a href="/unideportes-system/views/nueva_venta.php" class="card-link">
                    <div class="card-icon">🛒</div>
                    <div class="card-body">
                        <h3>Realizar Venta</h3>
                        <p>Abre el módulo de punto de venta para facturar uniformes en stock.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card">
                <a href="/unideportes-system/views/admin_user.php" class="card-link">
                    <div class="card-icon">👤</div>
                    <div class="card-body">
                        <h3>Gestionar Personal</h3>
                        <p>Administra las cuentas, accesos y contraseñas de tus usuarios.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card">
                <a href="/unideportes-system/views/productos.php" class="card-link">
                    <div class="card-icon">🎽</div>
                    <div class="card-body">
                        <h3>Control de Productos</h3>
                        <p>Edita precios, añade referencias y revisa existencias de fábrica.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card">
                <a href="/unideportes-system/views/clientes.php" class="card-link">
                    <div class="card-icon">👥</div>
                    <div class="card-body">
                        <h3>Base de Clientes</h3>
                        <p>Mira el historial de clientes, NITs y cambia estados activo/inactivo.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card">
                <a href="/unideportes-system/views/reportes_ventas.php" class="card-link">
                    <div class="card-icon">📜</div>
                    <div class="card-body">
                        <h3>Reportes Globales</h3>
                        <p>Audita los ingresos totales y el rendimiento general de los vendedores.</p>
                    </div>
                </a>
            </div>

        </div>

    </main>

</div>

<style>
.page-header {
    margin-bottom: 30px;
}
.page-header h1 {
    color: #1e293b; 
    font-size: 1.8rem; 
    font-weight: 700;
    margin: 0;
}
.page-header p {
    color: #64748b; 
    margin-top: 5px;
    font-size: 0.95rem;
}

/* Contenedores KPI */
.resumen-kpi {
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
    gap: 20px; 
    margin-bottom: 35px;
}
.kpi-card {
    background: white; 
    padding: 20px; 
    border-radius: 12px; 
    border: 1px solid #e2e8f0; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.01); 
    display: flex; 
    align-items: center; 
    gap: 15px;
}
.kpi-icon {
    font-size: 1.6rem; 
    width: 50px; 
    height: 50px; 
    border-radius: 10px; 
    display: flex; 
    align-items: center; 
    justify-content: center;
}
.icon-red { background: #fee2e2; color: #c91a25; }
.icon-blue { background: #e0f2fe; color: #0284c7; }
.icon-green { background: #dcfce7; color: #16a34a; }
.icon-amber { background: #fef3c7; color: #d97706; }

.kpi-data small {
    color: #64748b; 
    font-weight: 600; 
    font-size: 0.75rem; 
    text-transform: uppercase; 
    letter-spacing: 0.5px;
}
.kpi-data h2 {
    color: #1e293b; 
    font-size: 1.4rem; 
    margin: 2px 0 0 0; 
    font-weight: 700;
}
.kpi-data h2 span {
    font-size: 0.9rem; 
    font-weight: 400; 
    color: #64748b;
}

/* Grilla de Opciones */
.section-title {
    color: #475569; 
    font-size: 1.1rem; 
    font-weight: 600; 
    margin-bottom: 20px;
}
.menu-maestro {
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); 
    gap: 20px;
}
.dashboard-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.01);
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}
.dashboard-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);
    border-color: #cbd5e1;
}

/* Resaltado suave para las dos opciones del nuevo módulo */
.card-highlight {
    border-left: 4px solid #c91a25;
}

.card-link {
    display: flex;
    align-items: flex-start;
    padding: 22px;
    text-decoration: none;
    gap: 15px;
    height: 100%;
    box-sizing: border-box;
}
.card-icon {
    font-size: 2rem;
    line-height: 1;
}
.card-body h3 {
    margin: 0;
    font-size: 1.05rem;
    color: #1e293b;
    font-weight: 600;
}
.card-body p {
    margin: 6px 0 0 0;
    font-size: 0.88rem;
    color: #64748b;
    line-height: 1.4;
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>