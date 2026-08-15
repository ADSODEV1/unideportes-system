<?php
// views/panel_colaborador.php
require_once __DIR__ . '/../config/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pdo = app();

// Seguridad: el colaborador opera la fábrica, el admin supervisa
require_login(['colaborador', 'admin']);

try {
    // 1. PEDIDOS NUEVOS: llegaron en las últimas 48 horas (feedback del jurado)
    $stmt = $pdo->query("SELECT COUNT(*) FROM pedidos
                         WHERE created_at >= NOW() - INTERVAL 48 HOUR
                           AND estado <> 'Entregado'");
    $pedidos_nuevos = $stmt->fetchColumn() ?: 0;

    // 2. VENCIDOS: misma lógica del sidebar (excluye Terminado para no duplicar con "listos")
    $stmt = $pdo->query("SELECT COUNT(*) FROM pedidos
                         WHERE estado NOT IN ('Entregado', 'Terminado')
                           AND fecha_entrega < CURDATE()");
    $pedidos_vencidos = $stmt->fetchColumn() ?: 0;

    // 3. URGENTES: aún en producción y entregan hoy o en los próximos 2 días
    $stmt = $pdo->query("SELECT COUNT(*) FROM pedidos
                         WHERE estado NOT IN ('Entregado', 'Terminado')
                           AND fecha_entrega >= CURDATE()
                           AND fecha_entrega <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)");
    $pedidos_urgentes = $stmt->fetchColumn() ?: 0;

    // 4. CARGA EN TALLER: producción activa
    $stmt = $pdo->query("SELECT COUNT(*) FROM pedidos
                         WHERE estado IN ('En Corte', 'En Costura')");
    $ordenes_taller = $stmt->fetchColumn() ?: 0;

    // 5. LISTOS PARA ENTREGA: el aporte del colaborador a la tienda
    $stmt = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'Terminado'");
    $pedidos_listos = $stmt->fetchColumn() ?: 0;

    // 6 y 7. INVENTARIO: stock bajo y agotados (mismo criterio del panel_admin)
    $stmt = $pdo->query("SELECT COUNT(*) FROM productos WHERE estado = 'activo' AND stock <= 5 AND stock > 0");
    $bajo_stock = $stmt->fetchColumn() ?: 0;
    $stmt = $pdo->query("SELECT COUNT(*) FROM productos WHERE estado = 'activo' AND stock <= 0");
    $agotados = $stmt->fetchColumn() ?: 0;
} catch (Exception $e) {
    $pedidos_nuevos = $pedidos_vencidos = $pedidos_urgentes = 0;
    $ordenes_taller = $pedidos_listos = $bajo_stock = $agotados = 0;
}

include(__DIR__ . "/header.php");
?>
<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>
    <main class="main-content-panel">

        <div class="page-header header-dashboard">
            <div>
                <h1>🏭 Panel de Producción</h1>
                <p>
                    Centro operativo de fábrica de Unideportes.
                    <span class="badge azul">🧵 <?= htmlspecialchars($ordenes_taller); ?> En taller</span>
                    <span class="badge azul">✅ <?= htmlspecialchars($pedidos_listos); ?> Listos</span>
                    <span class="badge rojo">⏰ <?= htmlspecialchars($pedidos_urgentes + $pedidos_vencidos); ?> Por vencer</span>
                </p>
            </div>
        </div>

        <!-- ===== SISTEMA DE ALERTAS (respuesta directa al feedback) ===== -->
        <div class="alert-grid">

            <div class="alert-card <?= $pedidos_nuevos > 0 ? 'warning' : 'neutral' ?>">
                <div class="alert-icon">🆕</div>
                <div class="alert-text">
                    <strong>Pedidos Nuevos (48 h)</strong>
                    <p>Han llegado <strong><?= htmlspecialchars($pedidos_nuevos); ?></strong> pedido(s) en las últimas 48 horas.</p>
                </div>
                <?php if ($pedidos_nuevos > 0): ?>
                    <a href="/unideportes-system/views/pedidos_admin.php" class="btn-alert-action">Ver</a>
                <?php endif; ?>
            </div>

            <div class="alert-card <?= $pedidos_vencidos > 0 ? 'danger' : 'neutral' ?>">
                <div class="alert-icon">🔴</div>
                <div class="alert-text">
                    <strong>Pedidos Vencidos</strong>
                    <p><strong><?= htmlspecialchars($pedidos_vencidos); ?></strong> pedido(s) en producción pasaron su fecha de entrega.</p>
                </div>
                <?php if ($pedidos_vencidos > 0): ?>
                    <a href="/unideportes-system/views/mis_pedidos.php?filtro=vencidos" class="btn-alert-action">Ver</a>
                <?php endif; ?>
            </div>

            <div class="alert-card <?= $pedidos_urgentes > 0 ? 'warning' : 'neutral' ?>">
                <div class="alert-icon">⏰</div>
                <div class="alert-text">
                    <strong>Entregas Urgentes (≤ 2 días)</strong>
                    <p><strong><?= htmlspecialchars($pedidos_urgentes); ?></strong> pedido(s) en producción deben salir en 2 días.</p>
                </div>
                <?php if ($pedidos_urgentes > 0): ?>
                    <a href="/unideportes-system/views/pedidos_admin.php" class="btn-alert-action">Ver</a>
                <?php endif; ?>
            </div>

            <div class="alert-card <?= $pedidos_listos > 0 ? 'success' : 'neutral' ?>">
                <div class="alert-icon">✅</div>
                <div class="alert-text">
                    <strong>Listos para Entrega</strong>
                    <p><strong><?= htmlspecialchars($pedidos_listos); ?></strong> pedido(s) terminados esperan despacho a tienda.</p>
                </div>
                <?php if ($pedidos_listos > 0): ?>
                    <a href="/unideportes-system/views/mis_pedidos.php?filtro=terminados" class="btn-alert-action">Ver</a>
                <?php endif; ?>
            </div>

            <div class="alert-card <?= ($bajo_stock + $agotados) > 0 ? 'danger' : 'neutral' ?>">
                <div class="alert-icon">⚠️</div>
                <div class="alert-text">
                    <strong>Alertas de Inventario</strong>
                    <p>
                        <?php if ($agotados > 0): ?> ¡<strong><?= htmlspecialchars($agotados); ?></strong> producto(s) agotado(s)!<?php endif; ?>
                        <?php if ($bajo_stock > 0): ?><br><strong><?= htmlspecialchars($bajo_stock); ?></strong> con stock bajo (≤ 5).<?php endif; ?>
                        <?php if (($bajo_stock + $agotados) === 0): ?> Inventario saludable en fábrica.<?php endif; ?>
                    </p>
                </div>
                <?php if (($bajo_stock + $agotados) > 0): ?>
                    <a href="/unideportes-system/views/inventario.php" class="btn-alert-action">Ver</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== ACCIONES DE FÁBRICA (sin ventas, sin dinero, sin personal) ===== -->
        <h3 class="section-title">Acciones de Fábrica</h3>
        <div class="menu-maestro">
            <div class="dashboard-card border-green">
                <a href="/unideportes-system/views/pedidos_admin.php" class="card-link">
                    <div class="card-icon">🏭</div>
                    <div class="card-body">
                        <h3>Línea de Confección</h3>
                        <p>Avanza los pedidos por las fases de fabricación (Corte, Costura, Terminado).</p>
                    </div>
                </a>
            </div>
            <div class="dashboard-card border-indigo">
                <a href="/unideportes-system/views/panel_produccion.php" class="card-link">
                    <div class="card-icon">👷</div>
                    <div class="card-body">
                        <h3>Gestión de Taller</h3>
                        <p>Control de producción y carga de trabajo del taller.</p>
                    </div>
                </a>
            </div>
            <div class="dashboard-card border-slate">
                <a href="/unideportes-system/views/inventario.php" class="card-link">
                    <div class="card-icon">🎽</div>
                    <div class="card-body">
                        <h3>Inventario y Entradas</h3>
                        <p>Registra producto terminado que entra a stock y verifica disponibilidad.</p>
                    </div>
                </a>
            </div>
            <div class="dashboard-card border-emerald">
                <a href="/unideportes-system/views/mis_pedidos.php" class="card-link">
                    <div class="card-icon">🚚</div>
                    <div class="card-body">
                        <h3>Preparación de Despacho</h3>
                        <p>Consulta pedidos listos y datos de entrega. (Saldos monetarios: solo admin).</p>
                    </div>
                </a>
            </div>
        </div>
    </main>
</div>

<style>
/* Panel Colaborador - mismos componentes visuales del panel_admin */
.header-dashboard { background: var(--card); padding: 20px; border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 30px; }
.badge.rojo { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.alert-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 35px; }
.alert-card { border: 1px solid var(--border); padding: 15px; border-radius: 10px; display: flex; align-items: center; gap: 15px; }
.alert-card.success { background: #f0fdf4; border-left: 4px solid var(--success); color: #166534; }
.alert-card.danger  { background: #fef2f2; border-left: 4px solid var(--danger); color: #991b1b; }
.alert-card.warning { background: #fffbeb; border-left: 4px solid var(--warning); color: #92400e; }
.alert-card.neutral { background: #f8fafc; border-left: 4px solid var(--text-light); color: var(--text); }
.alert-icon { font-size: 1.6rem; }
.alert-text p { font-size: 0.85rem; margin: 2px 0 0 0; opacity: 0.9; }
.btn-alert-action { background: var(--danger); color: white; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; margin-left: auto; }
.section-title { color: var(--text-light); font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; }
.menu-maestro { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
.dashboard-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); transition: transform 0.2s, box-shadow 0.2s; }
.dashboard-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
.border-green  { border-top: 4px solid #16a34a; }
.border-emerald{ border-top: 4px solid var(--success); }
.border-slate  { border-top: 4px solid var(--text-light); }
.border-indigo { border-top: 4px solid #4f46e5; }
.card-link { display: flex; align-items: flex-start; padding: 24px 20px; text-decoration: none; gap: 15px; }
.card-icon { font-size: 2rem; line-height: 1; }
.card-body h3 { margin: 0; font-size: 1.05rem; color: var(--navy); font-weight: 600; }
.card-body p { margin: 6px 0 0 0; font-size: 0.85rem; color: var(--text-light); line-height: 1.4; }
@media (max-width: 768px) {
    .header-dashboard { text-align: center; }
    .alert-grid, .menu-maestro { grid-template-columns: 1fr; }
}
</style>
<?php include(__DIR__ . "/footer.php"); ?>