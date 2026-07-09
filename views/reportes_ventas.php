<?php
/**
 * Vista de Reportes de Ventas y Cartera
 * 
 * Muestra KPIs de ventas, estado de cartera, métodos de pago,
 * productos más vendidos, pedidos pendientes y abonos recientes.
 * 
 * @author Unideportes System
 * @version 2.0
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
        'abonos_recientes' => $reportes->obtenerAbonosRecientes($fecha_inicio, $fecha_fin),
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin
    ];
} catch (Exception $e) {
    error_log("Error en reportes_ventas.php: " . $e->getMessage());
    // CAMBIA ESTA LÍNEA para mostrar el error en pantalla:
    $error_msg = "Error al cargar los reportes: " . $e->getMessage();
    $data = [];
}

// ========================================
// FUNCIONES AUXILIARES
// ========================================

/**
 * Formatea un número como moneda colombiana
 */
function formatMoney($amount) {
    return '$' . number_format((float)$amount, 0, ',', '.');
}

/**
 * Formatea una fecha a formato dd/mm/YYYY
 */
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Calcula el porcentaje de recuperación de cartera
 */
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
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>⚠️ Error:</strong> <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                
                <button type="submit" class="btn-primary btn-lg">
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
            <section class="report-section">
                <h2 class="section-title">⏳ Pedidos con Saldo Pendiente</h2>
                <?php include(__DIR__ . '/partials/_pedidos_pendientes.php'); ?>
            </section>

            <!-- ======================================== -->
            <!-- SECCIÓN 5: ABONOS RECIENTES -->
            <!-- ======================================== -->
            <section class="report-section">
                <h2 class="section-title">✅ Abonos Registrados en el Período</h2>
                <?php include(__DIR__ . '/partials/_abonos_recientes.php'); ?>
            </section>

            <!-- ======================================== -->
            <!-- SECCIÓN 6: VENTAS DETALLADAS -->
            <!-- ======================================== -->
            <section class="report-section">
                <h2 class="section-title">🧾 Ventas Detalladas</h2>
                <?php include(__DIR__ . '/partials/_ventas_detalladas.php'); ?>
            </section>

        <?php endif; ?>

    </main>
</div>

<?php include(__DIR__ . '/footer.php'); ?>