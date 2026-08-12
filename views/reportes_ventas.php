<?php
/**
 * Vista de Reportes de Ventas y Cartera
 * 
 * Muestra KPIs de ventas, estado de cartera, métodos de pago,
 * productos más vendidos, pedidos pendientes y seguimiento de entregas.
 * 
 * @author Unideportes System
 * @version 2.1
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_once __DIR__ . '/../controllers/ReportesVentasController.php';

// ========================================
// CONFIGURACIÓN Y FILTROS
// ========================================
$pdo = app();
$reportes = new ReportesVentasController($pdo);

// Validar y sanitizar fechas
$fecha_inicio = filter_input(INPUT_GET, 'fecha_inicio', FILTER_SANITIZE_STRING) 
                ?: date('Y-m-01');
$fecha_fin = filter_input(INPUT_GET, 'fecha_fin', FILTER_SANITIZE_STRING) 
             ?: date('Y-m-t');

// Validar formato de fechas
if (!DateTime::createFromFormat('Y-m-d', $fecha_inicio) || 
    !DateTime::createFromFormat('Y-m-d', $fecha_fin)) {
    $fecha_inicio = date('Y-m-01');
    $fecha_fin = date('Y-m-t');
}

// ========================================
// OBTENER DATOS
// ========================================
try {
    $data = [
        'kpis_ventas' => $reportes->obtenerKPIsVentas($fecha_inicio, $fecha_fin),
        'kpis_cartera' => $reportes->obtenerKPIsCartera($fecha_inicio, $fecha_fin),
        'ventas_metodo' => $reportes->obtenerVentasPorMetodo($fecha_inicio, $fecha_fin),
        'ventas_detalladas' => $reportes->obtenerVentasDetalladas($fecha_inicio, $fecha_fin),
        'top_productos' => $reportes->obtenerTopProductos($fecha_inicio, $fecha_fin),
        'pedidos_pendientes' => $reportes->obtenerPedidosPendientes(),
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin
    ];
    
    // 🚚 Consulta para el resumen de Seguimiento de Entregas
    $stmt_entregas = $pdo->query("
        SELECT v.id, v.ticket_numero, v.estado,
               v.direccion_entrega, v.barrio_entrega, v.ciudad_entrega,
               v.observaciones_entrega, v.fecha_venta,
               c.nombre_completo AS cliente_nombre, c.telefono AS cliente_telefono
        FROM ventas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        WHERE v.estado = 'En Camino'
        ORDER BY v.fecha_venta DESC
        LIMIT 5
    ");
    $entregas_recientes = $stmt_entregas->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_count = $pdo->query("SELECT COUNT(*) FROM ventas WHERE estado = 'En Camino'");
    $total_entregas_camino = (int)$stmt_count->fetchColumn();
    
} catch (Exception $e) {
    error_log("Error en reportes_ventas.php: " . $e->getMessage());
    $error_msg = "Error al cargar los reportes: " . $e->getMessage();
    $data = [];
    $entregas_recientes = [];
    $total_entregas_camino = 0;
}

// ========================================
// FUNCIONES AUXILIARES
// ========================================

function formatMoney($amount) {
    return '$' . number_format((float)$amount, 0, ',', '.');
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function calcularPorcentajeRecuperacion($kpis) {
    $total_facturado = (float)($kpis['total_facturado'] ?? 0);
    $total_cobrado = (float)($kpis['total_abonos_iniciales'] ?? 0) + 
                     (float)($kpis['total_abonos_periodo'] ?? 0);
    
    return $total_facturado > 0 ? ($total_cobrado / $total_facturado) * 100 : 0;
}

// Incluir header
include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        
        <!-- ======================================== -->
        <!-- ENCABEZADO DE PÁGINA -->
        <!-- ======================================== -->
        <header class="page-header">
            <div class="page-header__content">
                <h1 class="page-header__title">📈 Reportes de Ventas y Cartera</h1>
                <p class="page-header__subtitle">
                    Analiza ingresos, transacciones, métodos de pago y estado de cartera
                </p>
            </div>
            <a href="panel_admin.php" class="btn-secondary">
                ← Volver al panel
            </a>
        </header>

        <!-- ======================================== -->
        <!-- MENSAJES DE ERROR -->
        <!-- ======================================== -->
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger">
                <strong>⚠️ Error:</strong> <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- ======================================== -->
        <!-- FORMULARIO DE FILTROS -->
        <!-- ======================================== -->
        <section class="filters-section">
            <form method="GET" action="" class="filters-form">
                <div class="filters-grid">
                    <div class="form-group">
                        <label for="fecha_inicio" class="form-label">
                            📅 Fecha inicial
                        </label>
                        <input 
                            type="date" 
                            id="fecha_inicio"
                            name="fecha_inicio" 
                            value="<?= htmlspecialchars($fecha_inicio) ?>" 
                            class="form-control"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_fin" class="form-label">
                            📅 Fecha final
                        </label>
                        <input 
                            type="date" 
                            id="fecha_fin"
                            name="fecha_fin" 
                            value="<?= htmlspecialchars($fecha_fin) ?>" 
                            class="form-control"
                            required
                        >
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">
                    🔍 Filtrar reporte
                </button>
            </form>
        </section>

        <?php if (!empty($data)): ?>
            
            <!-- ======================================== -->
            <!-- SECCIÓN 1: KPIs DE VENTAS -->
            <!-- ======================================== -->
            <section class="report-section">
                <h2 class="section-title">💰 Resumen de Ventas</h2>
                <?php include(__DIR__ . '/partials/_kpis_ventas.php'); ?>
            </section>

            <!-- ======================================== -->
            <!-- SECCIÓN 2: KPIs DE CARTERA -->
            <!-- ======================================== -->
            <section class="report-section">
                <h2 class="section-title">💳 Estado de Cartera</h2>
                <?php include(__DIR__ . '/partials/_kpis_cartera.php'); ?>
            </section>

            <!-- ======================================== -->
            <!-- SECCIÓN 3: ANÁLISIS DETALLADO -->
            <!-- ======================================== -->
            <section class="report-section">
                <h2 class="section-title">📊 Análisis Detallado</h2>
                <div class="grid-2-columns">
                    <?php include(__DIR__ . '/partials/_metodos_pago.php'); ?>
                    <?php include(__DIR__ . '/partials/_top_productos.php'); ?>
                </div>
            </section>

            <!-- ======================================== -->
            <!-- SECCIÓN 4: PEDIDOS POR COBRAR -->
            <!-- ======================================== -->
            <section class="report-section" id="sec-pedidos-pendientes">
                <h2 class="section-title">⏳ Pedidos con Saldo Pendiente</h2>
                <?php include(__DIR__ . '/partials/_pedidos_pendientes.php'); ?>
            </section>

<!-- ======================================== -->
<!-- SECCIÓN 5: 🚚 SEGUIMIENTO DE ENTREGAS -->
<!-- (REEMPLAZA A "ABONOS REGISTRADOS EN EL PERÍODO") -->
<!-- ======================================== -->
<section class="report-section" id="sec-seguimiento-entregas">
    <div class="entregas-section-header">
        <h2 class="section-title">🚚 Seguimiento de Entregas</h2>
        <a href="seguimiento_entregas.php" class="btn-primary">
            Ver Reporte Completo →
        </a>
    </div>

    <!-- KPI destacado de entregas en camino -->
    <div class="report-card">
        <div class="report-card__header report-card__header--primary">
            🚚 Entregas en Camino
        </div>
        <div class="report-card__body entregas-kpi-body">
            <div class="entregas-kpi-numero"><?= $total_entregas_camino ?></div>
            <div class="entregas-kpi-info">
                <div class="fw-bold">
                    <?= $total_entregas_camino === 1 ? 'Entrega pendiente' : 'Entregas pendientes' ?> de domicilio
                </div>
                <div class="text-muted" style="font-size: 0.85rem;">
                    Mostrando las <?= min(5, $total_entregas_camino) ?> más recientes
                </div>
            </div>
        </div>
    </div>

    <?php if (count($entregas_recientes) > 0): ?>
        <!-- VISTA DESKTOP: Tabla -->
        <div class="table-responsive entregas-tabla-desktop">
            <table class="tabla-maestra">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Cliente</th>
                        <th>Dirección de Entrega</th>
                        <th>Fecha Venta</th>
                        <th style="text-align: center;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entregas_recientes as $entrega): ?>
                        <tr>
                            <td>
                                <strong>#<?= htmlspecialchars(substr($entrega['ticket_numero'], -5)) ?></strong>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($entrega['cliente_nombre']) ?></div>
                                <small class="text-muted">📞 <?= htmlspecialchars($entrega['cliente_telefono'] ?: 'Sin tel.') ?></small>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($entrega['direccion_entrega']) ?></div>
                                <?php if (!empty($entrega['barrio_entrega']) || !empty($entrega['ciudad_entrega'])): ?>
                                    <small class="text-muted">
                                        📍 <?= htmlspecialchars($entrega['barrio_entrega'] . ($entrega['ciudad_entrega'] ? ', ' . $entrega['ciudad_entrega'] : '')) ?>
                                    </small>
                                <?php endif; ?>
                                <?php if (!empty($entrega['observaciones_entrega'])): ?>
                                    <br>
                                    <small class="text-warning" style="font-size: 0.8rem;">
                                        ⚠️ <?= htmlspecialchars($entrega['observaciones_entrega']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($entrega['fecha_venta'])) ?></td>
                            <td style="text-align: center;">
                                <span class="report-badge report-badge--warning">🚚 En Camino</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- VISTA MÓVIL: Tarjetas apiladas -->
        <div class="entregas-tarjetas-movil">
            <?php foreach ($entregas_recientes as $entrega): ?>
                <div class="entrega-card">
                    <div class="entrega-card__header">
                        <span class="entrega-card__ticket">
                            #<?= htmlspecialchars(substr($entrega['ticket_numero'], -5)) ?>
                        </span>
                        <span class="report-badge report-badge--warning">🚚 En Camino</span>
                    </div>
                    <div class="entrega-card__body">
                        <div class="entrega-card__row">
                            <span class="entrega-card__label">👤 Cliente</span>
                            <span class="entrega-card__value">
                                <strong><?= htmlspecialchars($entrega['cliente_nombre']) ?></strong><br>
                                <small class="text-muted">📞 <?= htmlspecialchars($entrega['cliente_telefono'] ?: 'Sin tel.') ?></small>
                            </span>
                        </div>
                        <div class="entrega-card__row">
                            <span class="entrega-card__label">📍 Dirección</span>
                            <span class="entrega-card__value">
                                <?= htmlspecialchars($entrega['direccion_entrega']) ?>
                                <?php if (!empty($entrega['barrio_entrega'])): ?>
                                    <br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($entrega['barrio_entrega'] . ($entrega['ciudad_entrega'] ? ', ' . $entrega['ciudad_entrega'] : '')) ?>
                                    </small>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if (!empty($entrega['observaciones_entrega'])): ?>
                            <div class="entrega-card__row entrega-card__row--warning">
                                <span class="entrega-card__label">⚠️ Obs.</span>
                                <span class="entrega-card__value">
                                    <?= htmlspecialchars($entrega['observaciones_entrega']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="entrega-card__row">
                            <span class="entrega-card__label">📅 Fecha</span>
                            <span class="entrega-card__value">
                                <?= date('d/m/Y H:i', strtotime($entrega['fecha_venta'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Contador de entregas adicionales -->
        <?php if ($total_entregas_camino > 5): ?>
            <div class="entregas-footer-counter">
                <span class="text-muted">Mostrando 5 de <?= $total_entregas_camino ?> entregas.</span>
                <a href="seguimiento_entregas.php" class="fw-semibold text-primary">Ver todas →</a>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-state">
            <div style="font-size: 3rem; margin-bottom: 12px;">🎉</div>
            <p>No hay entregas pendientes en este momento.</p>
            <a href="seguimiento_entregas.php" class="btn-secondary" style="margin-top: 12px; display: inline-block;">
                Ver historial de entregas
            </a>
        </div>
    <?php endif; ?>
</section>
            <!-- ======================================== -->
            <!-- SECCIÓN 6: VENTAS DETALLADAS -->
            <!-- ======================================== -->
            <section class="report-section" id="sec-ventas-detalladas">
                <h2 class="section-title">🧾 Ventas Detalladas</h2>
                <?php include(__DIR__ . '/partials/_ventas_detalladas.php'); ?>
            </section>

        <?php endif; ?>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const clickableKpis = document.querySelectorAll('.kpi-clickable');
    
    clickableKpis.forEach(kpi => {
        kpi.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                targetSection.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
                
                document.querySelectorAll('.section-highlighted').forEach(el => {
                    el.classList.remove('section-highlighted');
                });
                
                setTimeout(() => {
                    targetSection.classList.add('section-highlighted');
                }, 600);
            }
        });
    });
});
</script>

<?php include(__DIR__ . '/footer.php'); ?>