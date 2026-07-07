<?php
// views/panel_vendedor.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protegemos la vista para roles autorizados
require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();
$vendedor_id = $_SESSION['user_id'] ?? 0;

// Consultas operativas en tiempo real
try {
    // 1. Cuántas ventas ha hecho este usuario HOY
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE vendedor_id = ? AND DATE(fecha_venta) = CURRENT_DATE");
    $stmtCount->execute([$vendedor_id]);
    $mis_ventas_hoy = $stmtCount->fetchColumn() ?: 0;

    // 2. Cuántos pedidos están listos esperando retiro
    $stmtPedidosListos = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE estado = 'Terminado'");
    $stmtPedidosListos->execute();
    $pedidos_por_entregar = $stmtPedidosListos->fetchColumn() ?: 0;
    
} catch (Exception $e) {
    $mis_ventas_hoy = 0;
    $pedidos_por_entregar = 0;
}

// Mensajes de éxito del sistema
$mensaje_exito = '';

if (request('success') === 'venta_registrada' && intval(request('id')) > 0) {
    $venta_id = intval(request('id'));
    $ticketLink = "/unideportes-system/views/ticket_actual.php?id=$venta_id";
    $mensaje_exito = "🚀 ¡Venta registrada correctamente! <a href=\"" . htmlspecialchars($ticketLink) . "\" target=\"_blank\">Imprimir / Ver Comprobante</a>";
}

if (($_GET['success'] ?? '') === 'pedido_entregado') {
    $mensaje_exito = "📦 ¡Pedido entregado con éxito y saldo pendiente ingresado a caja correctamente!";
}

$soporte_success = '';
if (!empty($_GET['success']) && $_GET['success'] === 'ticket_creado') {
    $soporte_success = 'Ticket enviado correctamente al área de soporte.';
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        
        <!-- Mensajes de éxito -->
        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert-success">
                <?= $mensaje_exito ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($soporte_success)): ?>
            <div class="alert-success">
                <?= htmlspecialchars($soporte_success) ?>
            </div>
        <?php endif; ?>

        <!-- Encabezado -->
        <div class="page-header">
            <div>
                <h1>💼 Panel del Vendedor</h1>
                <p>
                    Bienvenido de nuevo, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?></strong>. 
                    <span class="badge azul">⚡ Llevas <?= htmlspecialchars($mis_ventas_hoy); ?> facturas hoy</span>
                </p>
            </div>
            <a href="/unideportes-system/views/nueva_venta.php" class="btn-primary">
                <span>🛒</span> Nueva Venta
            </a>
        </div>

        <!-- Alerta de pedidos pendientes -->
        <?php if ($pedidos_por_entregar > 0): ?>
            <div class="alert-warning">
                <div class="alert-content">
                    <strong>📦 Pedidos listos en bodega esperando retiro</strong>
                    <p>Hay <strong><?= htmlspecialchars($pedidos_por_entregar) ?></strong> órdenes confeccionadas listas para cobrar saldo y entregar al cliente.</p>
                </div>
                <a href="/unideportes-system/views/mis_pedidos.php" class="btn-warning">
                    Gestionar Entregas
                </a>
            </div>
        <?php endif; ?>

        <!-- Menú de Acciones -->
        <h2 class="section-title">Acciones de Gestión Comercial</h2>
        
        <div class="menu-grid">
            
            <a href="/unideportes-system/views/nueva_venta.php" class="card">
                <div class="card-icon">🛒</div>
                <div class="card-body">
                    <h3>Nueva Venta</h3>
                    <p>Factura y genera comprobantes de uniformes disponibles de inmediato.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/venta_mayorista.php" class="card">
                <div class="card-icon">🧵</div>
                <div class="card-body">
                    <h3>Venta Mayorista</h3>
                    <p>Realiza pedidos de volumen con descuentos automáticos por cantidad.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/mis_pedidos.php" class="card">
                <div class="card-icon">🎁</div>
                <div class="card-body">
                    <h3>Entregar Pedidos</h3>
                    <p>Recibe saldos pendientes por ropa sobre medida confeccionada y lista.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/clientes.php" class="card">
                <div class="card-icon">👥</div>
                <div class="card-body">
                    <h3>Gestión de Clientes</h3>
                    <p>Consulta historiales, registra nuevos compradores o actualiza identificaciones.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/inventario.php" class="card">
                <div class="card-icon">📊</div>
                <div class="card-body">
                    <h3>Consultar Inventario</h3>
                    <p>Verifica stock actual, tallas disponibles y líneas de producto en tiempo real.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/soporte_tecnico_vendedor.php" class="card">
                <div class="card-icon">🛠️</div>
                <div class="card-body">
                    <h3>Soporte Técnico</h3>
                    <p>Reporta incidencias del sistema o consulta el estado de tus tickets.</p>
                </div>
            </a>

        </div>
    </main>
</div>

<style>
/* ============================================
   PANEL DEL VENDEDOR - DISEÑO PROFESIONAL
   ============================================ */

/* Alertas */
.alert-success {
    padding: 15px;
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success a {
    color: #047857;
    font-weight: 700;
    text-decoration: underline;
    margin-left: 5px;
}

.alert-warning {
    padding: 15px;
    background: #fffbeb;
    color: #92400e;
    border-left: 4px solid #d97706;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.alert-warning p {
    margin: 5px 0 0 0;
    font-size: 0.9rem;
    color: #b45309;
}

.alert-content strong {
    display: block;
    margin-bottom: 4px;
    font-size: 0.95rem;
}

.btn-warning {
    background: #d97706;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: background 0.2s;
    white-space: nowrap;
}

.btn-warning:hover {
    background: #b45309;
}

/* Encabezado de página */
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

.badge.azul {
    background: #dbeafe;
    color: #1e40af;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-left: 8px;
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
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-primary:hover {
    background: #1d4ed8;
}

/* Sección de acciones */
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
    transform: translateY(-3px);
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
    
    .menu-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>