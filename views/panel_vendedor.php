<?php
// views/panel_vendedor.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();
$vendedor_id = $_SESSION['user_id'] ?? 0;

// CONSULTAS OPERATIVAS
try {
    // Ventas realizadas HOY
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) 
        FROM ventas 
        WHERE vendedor_id = ? 
        AND DATE(fecha_venta) = CURRENT_DATE
    ");
    $stmtCount->execute([$vendedor_id]);
    $mis_ventas_hoy = $stmtCount->fetchColumn() ?: 0;

    // Pedidos listos para entrega
    $stmtPedidosListos = $pdo->prepare("
        SELECT COUNT(*) 
        FROM pedidos 
        WHERE estado = 'Terminado'
    ");
    $stmtPedidosListos->execute();
    $pedidos_por_entregar = $stmtPedidosListos->fetchColumn() ?: 0;
    
} catch (Exception $e) {
    $mis_ventas_hoy = 0;
    $pedidos_por_entregar = 0;
}

// MENSAJES DEL SISTEMA
$mensaje_exito = '';

if (request('success') === 'venta_registrada' && intval(request('id')) > 0) {
    $venta_id = intval(request('id'));
    $ticketLink = "/unideportes-system/views/ticket_actual.php?id=$venta_id";
    $mensaje_exito = "✅ Venta registrada correctamente. <a href=\"" . htmlspecialchars($ticketLink) . "\" target=\"_blank\">Ver comprobante</a>";
}

if (($_GET['success'] ?? '') === 'pedido_entregado') {
    $mensaje_exito = "✅ Pedido entregado con éxito.";
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        
        <!-- Mensaje de éxito -->
        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert-success">
                <?= $mensaje_exito ?>
            </div>
        <?php endif; ?>

        <!-- Encabezado -->
        <div class="page-header">
            <div>
                <h1>Panel del Vendedor</h1>
                <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?></strong></p>
                <p class="ventas-hoy">Ventas hoy: <strong><?= htmlspecialchars($mis_ventas_hoy) ?></strong></p>
            </div>
            <a href="/unideportes-system/views/nueva_venta.php" class="btn-primary">
                Nueva Venta
            </a>
        </div>

        <!-- Alerta de pedidos pendientes -->
        <?php if ($pedidos_por_entregar > 0): ?>
            <div class="alert-warning">
                <strong>📦 Pedidos listos en bodega</strong>
                <p>Hay <strong><?= htmlspecialchars($pedidos_por_entregar) ?></strong> pedidos listos para entregar.</p>
                <a href="/unideportes-system/views/mis_pedidos.php" class="btn-warning">
                    Gestionar Entregas
                </a>
            </div>
        <?php endif; ?>

        <!-- Menú de Acciones -->
        <h2 class="section-title">Acciones</h2>
        
        <div class="menu-grid">
            
            <a href="/unideportes-system/views/nueva_venta.php" class="card">
                <div class="card-icon">🛒</div>
                <div class="card-body">
                    <h3>Nueva Venta</h3>
                    <p>Venta directa de productos disponibles.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/venta_mayorista.php" class="card">
                <div class="card-icon">📦</div>
                <div class="card-body">
                    <h3>Venta Mayorista</h3>
                    <p>Pedidos por volumen con descuentos.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/mis_pedidos.php" class="card">
                <div class="card-icon">🎁</div>
                <div class="card-body">
                    <h3>Entregar Pedidos</h3>
                    <p>Gestionar pedidos listos para entrega.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/clientes.php" class="card">
                <div class="card-icon">👥</div>
                <div class="card-body">
                    <h3>Clientes</h3>
                    <p>Consultar y gestionar clientes.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/inventario.php" class="card">
                <div class="card-icon">📊</div>
                <div class="card-body">
                    <h3>Inventario</h3>
                    <p>Ver stock disponible en tiempo real.</p>
                </div>
            </a>

        </div>
    </main>
</div>

<style>
/* ============================================
   PANEL DEL VENDEDOR - DISEÑO SIMPLIFICADO
   ============================================ */

/* Layout principal */
.admin-layout {
    display: flex;
    gap: 20px;
    padding: 20px;
}

.main-content-panel {
    flex: 1;
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Alertas simplificadas */
.alert-success {
    padding: 15px;
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-success a {
    color: #047857;
    font-weight: 600;
    text-decoration: underline;
    margin-left: 5px;
}

.alert-warning {
    padding: 15px;
    background: #fef3c7;
    color: #92400e;
    border-left: 4px solid #f59e0b;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.alert-warning p {
    margin: 5px 0 0 0;
    font-size: 0.9rem;
}

.btn-warning {
    background: #f59e0b;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: background 0.2s;
}

.btn-warning:hover {
    background: #d97706;
}

/* Encabezado de página */
.page-header {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 30px;
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

.ventas-hoy {
    margin-top: 8px !important;
    font-size: 0.9rem !important;
}

.ventas-hoy strong {
    color: #2563eb;
    font-size: 1.1rem;
}

/* Botón principal */
.btn-primary {
    background: #2563eb;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: background 0.2s;
}

.btn-primary:hover {
    background: #1d4ed8;
}

/* Sección de acciones */
.section-title {
    color: #475569;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
}

/* Grid de tarjetas */
.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

/* Tarjetas simplificadas */
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
}

.card-body h3 {
    margin: 0;
    font-size: 1.05rem;
    color: #1e293b;
    font-weight: 600;
}

.card-body p {
    margin: 8px 0 0 0;
    font-size: 0.85rem;
    color: #64748b;
    line-height: 1.4;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-layout {
        flex-direction: column;
    }
    
    .page-header {
        flex-direction: column;
        text-align: center;
    }
    
    .menu-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>