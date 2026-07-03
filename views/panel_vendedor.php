<?php
// views/panel_vendedor.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protegemos la vista para roles autorizados
require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();
$pagina_actual = basename($_SERVER['PHP_SELF']);
$vendedor_id = $_SESSION['user_id'] ?? 0;

// Consultas operativas en tiempo real
try {
    // 1. Cuántas ventas ha hecho este usuario HOY
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE vendedor_id = ? AND DATE(fecha_venta) = CURRENT_DATE");
    $stmtCount->execute([$vendedor_id]);
    $mis_ventas_hoy = $stmtCount->fetchColumn() ?: 0;

    // 2. Cuántos pedidos están listos esperando retiro (Estado 'Terminado' según el flujo de taller)
    $stmtPedidosListos = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE estado = 'Terminado'");
    $stmtPedidosListos->execute();
    $pedidos_por_entregar = $stmtPedidosListos->fetchColumn() ?: 0;
    
} catch (Exception $e) {
    $mis_ventas_hoy = 0;
    $pedidos_por_entregar = 0;
}

// Mensajes de éxito del sistema (Ticket dinámico de venta)
$success = request('success');
$venta_id = intval(request('id'));
$ticketLink = ($success === 'venta_registrada' && $venta_id > 0) ? "/unideportes-system/views/ticket_actual.php?id=$venta_id" : '';

$soporte_success = '';
if (!empty($_GET['success']) && $_GET['success'] === 'ticket_creado') {
    $soporte_success = 'Ticket enviado correctamente al área de soporte.';
}
$soporte_error = trim($_GET['error'] ?? '');

// Captura de mensajes de éxito del módulo de entregas de mercancía
$entrega_success = $_GET['success'] ?? '';
if ($entrega_success === 'pedido_entregado') {
    $success_msg = "📦 ¡Pedido entregado con éxito y saldo pendiente ingresado a caja correctamente!";
}

// Incluimos el header global
include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        
        <?php if ($ticketLink): ?>
            <div class="alert alert-success alert-panel-vendedor">
                🚀 ¡Venta registrada correctamente! 
                <a href="<?= htmlspecialchars($ticketLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="alert-link">Imprimir / Ver Comprobante</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-panel-vendedor text-semibold">
                <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <div class="page-header header-dashboard">
            <div>
                <h1>💼 Panel del Vendedor</h1>
                <p>
                    Bienvenido de nuevo, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?></strong>. 
                    <span class="badge azul">⚡ Llevas <?= htmlspecialchars($mis_ventas_hoy); ?> facturas hoy</span>
                </p>
            </div>
            <a href="/unideportes-system/views/nueva_venta.php" class="btn-primary btn-icon-gap">
                <span>🛒</span> Nueva Venta
            </a>
        </div>

        <?php if ($pedidos_por_entregar > 0): ?>
            <div class="alert-card warning alert-spaced">
                <div class="alert-combination">
                    <span class="alert-icon">📦</span>
                    <div class="alert-text">
                        <strong>Pedidos listos en bodega esperando retiro</strong>
                        <p>Hay <strong><?= htmlspecialchars($pedidos_por_entregar) ?></strong> órdenes confeccionadas listas para cobrar saldo remanente y entregar al cliente.</p>
                    </div>
                </div>
                <a href="/unideportes-system/views/mis_pedidos.php" class="btn-alert-action-warning">
                    Gestionar Entregas
                </a>
            </div>
        <?php endif; ?>

        <h3 class="section-title">Acciones de Gestión Comercial</h3>
        
        <div class="menu-maestro">
            
            <div class="dashboard-card border-red">
                <a href="/unideportes-system/views/nueva_venta.php" class="card-link">
                    <div class="card-icon icon-red">🛒</div>
                    <div class="card-body">
                        <h3>Nueva Venta</h3>
                        <p>Factura y genera comprobantes de uniformes y prendas disponibles de inmediato.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card border-amber">
                <a href="/unideportes-system/views/linea_confeccion.php" class="card-link">
                    <div class="card-icon icon-amber">📦</div>
                    <div class="card-body">
                        <h3>Venta Mayorista</h3>
                        <p>Realiza pedidos de volumen con descuentos automáticos por cantidad y registra el vendedor responsable.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card border-emerald">
                <a href="/unideportes-system/views/mis_pedidos.php" class="card-link">
                    <div class="card-icon icon-emerald">🎁</div>
                    <div class="card-body">
                        <h3>Entregar Pedidos</h3>
                        <p>Recibe saldos de dinero pendientes por ropa sobre medida confeccionada y lista.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card border-blue">
                <a href="/unideportes-system/views/clientes.php" class="card-link">
                    <div class="card-icon icon-blue">👥</div>
                    <div class="card-body">
                        <h3>Gestión de Clientes</h3>
                        <p>Consulta historiales, registra nuevos compradores o actualiza identificaciones.</p>
                    </div>
                </a>
            </div>

            <div class="dashboard-card border-slate">
                <a href="/unideportes-system/views/inventario.php" class="card-link">
                    <div class="card-icon icon-slate">📊</div>
                    <div class="card-body">
                        <h3>Consultar Inventario</h3>
                        <p>Verifica stock actual, tallas disponibles y líneas de producto en tiempo real.</p>
                    </div>
                </a>
            </div>

    </main>
</div>

<style>
/* Estilos Semánticos Limpios para el Dashboard del Vendedor */
.header-dashboard {
    background: var(--card);
    padding: 20px;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}
.header-dashboard h1 { color: #1e293b; font-size: 1.8rem; font-weight: 700; margin: 0; }
.header-dashboard p { color: #64748b; margin-top: 5px; margin-bottom: 0; }

.btn-icon-gap { display: flex; align-items: center; gap: 8px; text-decoration: none; }

.alert-panel-vendedor { margin-bottom: 25px; padding: 15px; background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; border-radius: 8px; }
.alert-link { color: #047857; font-weight: 700; text-decoration: underline; margin-left: 5px; }
.text-semibold { font-weight: 600; }

/* Estructura de las Alertas Activas */
.alert-card.warning { background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #d97706; }
.alert-spaced { padding: 15px; border-radius: 8px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; }
.alert-combination { display: flex; align-items: center; gap: 12px; }
.alert-icon { font-size: 1.5rem; }
.alert-text strong { color: #92400e; font-size: 0.95rem; }
.alert-text p { color: #b45309; font-size: 0.88rem; margin: 2px 0 0 0; }

.btn-alert-action-warning {
    background: #d97706;
    color: white;
    text-decoration: none;
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: background 0.2s;
}
.btn-alert-action-warning:hover { background: #b45309; }

.section-title { color: var(--text-light); font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; }

/* Reutilización de Clases de la Matriz del Sistema */
.menu-maestro { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
.dashboard-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); transition: transform 0.2s, box-shadow 0.2s; }
.dashboard-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }

.border-red     { border-top: 4px solid var(--primary); }
.border-emerald { border-top: 4px solid var(--success); }
.border-blue    { border-top: 4px solid #0284c7; }
.border-slate   { border-top: 4px solid var(--text-light); }
.border-amber   { border-top: 4px solid var(--warning); }

.card-link { display: flex; align-items: flex-start; padding: 24px 20px; text-decoration: none; gap: 15px; height: 100%; box-sizing: border-box; }
.card-icon { font-size: 2.2rem; line-height: 1; }
.icon-red { color: var(--primary); }
.icon-emerald { color: var(--success); }
.icon-blue { color: #0284c7; }
.icon-slate { color: var(--text-light); }
.icon-amber { color: var(--warning); }

.card-body h3 { margin: 0; font-size: 1.1rem; color: #1e293b; font-weight: 600; }
.card-body p { margin: 8px 0 0 0; font-size: 0.88rem; color: #64748b; line-height: 1.4; }

.full-width-card { grid-column: 1 / -1; }
.link-wide { padding: 15px 20px; }
.icon-small { font-size: 1.6rem; }
</style>

<?php include(__DIR__ . "/footer.php"); ?>