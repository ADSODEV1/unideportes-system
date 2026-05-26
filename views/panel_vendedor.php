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

// Consultas en tiempo real para motivar la actividad del vendedor
try {
    // 1. Cuántas ventas ha hecho este usuario HOY
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE vendedor_id = ? AND DATE(fecha_venta) = CURRENT_DATE");
    $stmtCount->execute([$vendedor_id]);
    $mis_ventas_hoy = $stmtCount->fetchColumn() ?: 0;

    // 2. Cuánto dinero total ha facturado este usuario HOY
    $stmtSum = $pdo->prepare("SELECT SUM(total_venta) FROM ventas WHERE vendedor_id = ? AND DATE(fecha_venta) = CURRENT_DATE");
    $stmtSum->execute([$vendedor_id]);
    $mi_total_hoy = $stmtSum->fetchColumn() ?: 0;

} catch (Exception $e) {
    $mis_ventas_hoy = 0;
    $mi_total_hoy = 0;
}

// Mensajes de éxito del sistema (Ticket dinámico)
$success = request('success');
$venta_id = intval(request('id'));
$ticketLink = ($success === 'venta_registrada' && $venta_id > 0) ? "/unideportes-system/views/ticket_actual.php?id=$venta_id" : '';

// Incluimos el header global
include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        
        <?php if ($ticketLink): ?>
            <div class="alert alert-success" style="margin-bottom: 25px; padding: 15px; background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; border-radius: 8px;">
                🚀 ¡Venta registrada correctamente! 
                <a href="<?= htmlspecialchars($ticketLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="color: #047857; font-weight: 700; text-decoration: underline; margin-left: 5px;">Imprimir / Ver Comprobante</a>
            </div>
        <?php endif; ?>

        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px;">
            <div>
                <h1 style="color: #1e293b; font-size: 1.8rem; font-weight: 700;">💼 Panel del Vendedor</h1>
                <p style="color: #64748b; margin-top: 5px;">Bienvenido de nuevo, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?></strong>. Registra y monitorea tu actividad.</p>
            </div>
            <a href="/unideportes-system/views/nueva_venta.php" class="btn-nueva-venta" style="padding: 12px 22px; text-decoration: none; border-radius: 6px; background: #c91a25; color: white; font-weight: 600; box-shadow: 0 2px 4px rgba(201, 26, 37, 0.2);">
                🛒 Nueva Venta
            </a>
        </div>

        <div class="vendedor-kpi" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 35px;">
            
            <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px;">
                <div style="background: #e0f2fe; color: #0369a1; font-size: 1.8rem; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">👕</div>
                <div>
                    <small style="color: #64748b; font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Mis Facturas Hoy</small>
                    <h2 style="color: #1e293b; font-size: 1.4rem; margin-top: 2px; font-weight: 700;"><?= htmlspecialchars($mis_ventas_hoy); ?> <span style="font-size: 0.9rem; font-weight: 400; color: #64748b;">atendidas</span></h2>
                </div>
            </div>

            <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px;">
                <div style="background: #dcfce7; color: #15803d; font-size: 1.8rem; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">💲</div>
                <div>
                    <small style="color: #64748b; font-weight: 600; font-size: 0.75rem; text-transform: uppercase;">Mi Recaudo Diario</small>
                    <h2 style="color: #1e293b; font-size: 1.4rem; margin-top: 2px; font-weight: 700;">$<?= number_format($mi_total_hoy, 0, ',', '.'); ?></h2>
                </div>
            </div>

        </div>

        <h2 style="font-size: 1.1rem; color: #475569; font-weight: 600; margin-bottom: 20px;">Gestión Comercial</h2>
        
        <div class="menu-maestro" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 20px;">
            
            <div class="vendedor-card">
                <a href="/unideportes-system/views/nueva_venta.php" class="vendedor-card-link">
                    <div class="vendedor-card-icon">🛒</div>
                    <div class="vendedor-card-body">
                        <h3>Nueva Venta</h3>
                        <p>Abre la caja para facturar nuevos uniformes deportivos.</p>
                    </div>
                </a>
            </div>

            <div class="vendedor-card">
                <a href="/unideportes-system/views/mis_ventas.php" class="vendedor-card-link">
                    <div class="vendedor-card-icon">📜</div>
                    <div class="vendedor-card-body">
                        <h3>Mis Ventas</h3>
                        <p>Consulta tu historial de facturas y reimprime tickets.</p>
                    </div>
                </a>
            </div>

            <div class="vendedor-card">
                <a href="/unideportes-system/views/clientes.php" class="vendedor-card-link">
                    <div class="vendedor-card-icon">👥</div>
                    <div class="vendedor-card-body">
                        <h3>Clientes</h3>
                        <p>Busca, registra o actualiza los datos de los compradores.</p>
                    </div>
                </a>
            </div>

            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'colaborador')): ?>
            <div class="vendedor-card" style="border-left: 3px solid #c91a25;">
                <a href="/unideportes-system/views/pedidos_admin.php" class="vendedor-card-link">
                    <div class="vendedor-card-icon">📦</div>
                    <div class="vendedor-card-body">
                        <h3>Pedidos</h3>
                        <p>Monitorea y despacha las órdenes enviadas a fábrica.</p>
                    </div>
                </a>
            </div>
            <?php endif; ?>

        </div>

    </main>
</div>

<style>
.vendedor-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}

.vendedor-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    border-color: #cbd5e1;
}

.vendedor-card-link {
    display: flex;
    align-items: flex-start;
    padding: 20px;
    text-decoration: none;
    gap: 15px;
    height: 100%;
    box-sizing: border-box;
}

.vendedor-card-icon {
    font-size: 2rem;
    line-height: 1;
}

.vendedor-card-body h3 {
    margin: 0;
    font-size: 1.1rem;
    color: #1e293b;
    font-weight: 600;
}

.vendedor-card-body p {
    margin: 5px 0 0 0;
    font-size: 0.88rem;
    color: #64748b;
    line-height: 1.4;
}

.btn-nueva-venta:hover {
    background: #b0131c !important;
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>