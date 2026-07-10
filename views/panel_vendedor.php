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

// ============================================
// CONSULTAS OPERATIVAS EN TIEMPO REAL
// ============================================
try {
    // 1. Ventas realizadas HOY por este vendedor
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE vendedor_id = ? AND DATE(fecha_venta) = CURRENT_DATE");
    $stmtCount->execute([$vendedor_id]);
    $mis_ventas_hoy = $stmtCount->fetchColumn() ?: 0;

    // 2. Pedidos listos esperando retiro
    $stmtPedidosListos = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE estado = 'Terminado'");
    $stmtPedidosListos->execute();
    $pedidos_por_entregar = $stmtPedidosListos->fetchColumn() ?: 0;
    
} catch (Exception $e) {
    $mis_ventas_hoy = 0;
    $pedidos_por_entregar = 0;
}

// ============================================
// MENSAJES DE ÉXITO (unificados)
// ============================================
$mensaje_exito = '';
$success = $_GET['success'] ?? '';

if ($success === 'venta_registrada' && intval($_GET['id'] ?? 0) > 0) {
    $venta_id = intval($_GET['id']);
    $ticketLink = "/unideportes-system/views/ticket_actual.php?id=$venta_id";
    $mensaje_exito = "🚀 ¡Venta registrada correctamente! <a href=\"" . htmlspecialchars($ticketLink) . "\" target=\"_blank\">Ver Comprobante</a>";
} elseif ($success === 'pedido_entregado') {
    $mensaje_exito = "📦 ¡Pedido entregado con éxito!";
} elseif ($success === 'ticket_creado') {
    $mensaje_exito = "🛠️ Ticket enviado correctamente al área de soporte.";
} elseif ($success === 'comentario_agregado') {
    $mensaje_exito = "💬 Comentario agregado correctamente.";
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        
        <!-- Mensaje de éxito (si existe) -->
        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert-success">
                <?= $mensaje_exito ?>
            </div>
        <?php endif; ?>

        <!-- ============================================
             ENCABEZADO DEL PANEL
             ============================================ -->
        <div class="page-header">
            <div>
                <h1>💼 Panel del Vendedor</h1>
                <p>
                    Bienvenido, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?></strong>. 
                    <span class="badge badge-info">⚡ <?= htmlspecialchars($mis_ventas_hoy); ?> facturas hoy</span>
                </p>
            </div>
            <a href="/unideportes-system/views/nueva_venta.php" class="btn-primary">
                🛒 Nueva Venta
            </a>
        </div>

        <!-- ============================================
             ALERTA DE PEDIDOS PENDIENTES
             ============================================ -->
        <?php if ($pedidos_por_entregar > 0): ?>
            <div class="alert-warning">
                <div class="alert-content">
                    <strong>📦 Pedidos listos en bodega</strong>
                    <p>Hay <strong><?= htmlspecialchars($pedidos_por_entregar) ?></strong> órdenes listas para cobrar saldo y entregar.</p>
                </div>
                <a href="/unideportes-system/views/mis_pedidos.php" class="btn-warning">
                    Gestionar Entregas
                </a>
            </div>
        <?php endif; ?>

        <!-- ============================================
             MENÚ DE ACCIONES PRINCIPALES
             ============================================ -->
        <h2 class="section-title">Acciones de Gestión Comercial</h2>
        
        <div class="menu-grid">
            
            <a href="/unideportes-system/views/nueva_venta.php" class="card">
                <div class="card-icon">🛒</div>
                <div class="card-body">
                    <h3>Nueva Venta</h3>
                    <p>Factura y genera comprobantes de uniformes disponibles.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/venta_mayorista.php" class="card">
                <div class="card-icon">🧵</div>
                <div class="card-body">
                    <h3>Venta Mayorista</h3>
                    <p>Pedidos de volumen con descuentos por cantidad.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/mis_pedidos.php" class="card">
                <div class="card-icon">🎁</div>
                <div class="card-body">
                    <h3>Entregar Pedidos</h3>
                    <p>Cobra saldos pendientes de ropa confeccionada.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/clientes.php" class="card">
                <div class="card-icon">👥</div>
                <div class="card-body">
                    <h3>Gestión de Clientes</h3>
                    <p>Consulta historiales y registra nuevos compradores.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/inventario.php" class="card">
                <div class="card-icon">📊</div>
                <div class="card-body">
                    <h3>Consultar Inventario</h3>
                    <p>Verifica stock, tallas y líneas de producto.</p>
                </div>
            </a>

            <a href="/unideportes-system/views/soporte_tecnico_vendedor.php" class="card">
                <div class="card-icon">🛠️</div>
                <div class="card-body">
                    <h3>Soporte Técnico</h3>
                    <p>Reporta incidencias o consulta tus tickets.</p>
                </div>
            </a>

        </div>
    </main>
</div>

<?php include(__DIR__ . "/footer.php"); ?>
