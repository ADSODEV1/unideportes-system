<?php
// views/reportes_ventas.php
require_once __DIR__ . '/../config/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_login(['admin', 'vendedor', 'colaborador']);
$pdo = app();

// Detectar rol del usuario
$rol = $_SESSION['role'] ?? 'vendedor';
$vendedor_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
$es_admin = ($rol === 'admin');

// ==========================================
// 1. CONTROL DE FILTROS Y VALIDACIÓN
// ==========================================
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t');

// Validar formato de fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio) || 
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
    $fecha_inicio = date('Y-m-01');
    $fecha_fin = date('Y-m-t');
}

// Validar que fecha_inicio no sea mayor que fecha_fin
if ($fecha_inicio > $fecha_fin) {
    $temp = $fecha_inicio;
    $fecha_inicio = $fecha_fin;
    $fecha_fin = $temp;
}

// Filtro de estado (excluir canceladas por defecto)
$estado_filtro = $_GET['estado'] ?? 'activas'; // 'activas', 'todas', 'pendientes', 'entregadas'
$filtro_estado = "";
switch ($estado_filtro) {
    case 'pendientes':
        $filtro_estado = " AND v.estado = 'Pendiente'";
        break;
    case 'entregadas':
        $filtro_estado = " AND v.estado = 'Entregado'";
        break;
    case 'canceladas':
        $filtro_estado = " AND v.estado = 'Cancelado'";
        break;
    case 'todas':
        $filtro_estado = "";
        break;
    default: // 'activas' - excluye canceladas
        $filtro_estado = " AND v.estado != 'Cancelado'";
        break;
}

// ==========================================
// 2. CONSTRUIR CONSULTAS SEGÚN EL ROL
// ==========================================
$filtro_vendedor = '';
$params_base = [
    'inicio' => $fecha_inicio . ' 00:00:00', 
    'fin' => $fecha_fin . ' 23:59:59'
];

if (!$es_admin) {
    $filtro_vendedor = " AND v.vendedor_id = :vendedor_id";
    $params_base['vendedor_id'] = $vendedor_id;
}

// ==========================================
// 3. KPIs PRINCIPALES (PERIODO ACTUAL)
// ==========================================
$sql_kpis = "SELECT
    IFNULL(SUM(total_venta), 0) as total_ingresos,
    COUNT(id) as total_transacciones,
    IFNULL(AVG(total_venta), 0) as promedio_ticket,
    IFNULL(SUM(CASE WHEN estado = 'Entregado' THEN total_venta ELSE 0 END), 0) as ingresos_entregados,
    IFNULL(SUM(CASE WHEN estado = 'Pendiente' THEN total_venta ELSE 0 END), 0) as ingresos_pendientes,
    IFNULL(SUM(descuento_monto), 0) as total_descuentos,
    IFNULL(SUM(costo_envio), 0) as total_envios
FROM ventas v
WHERE v.fecha_venta BETWEEN :inicio AND :fin
{$filtro_vendedor}
{$filtro_estado}";

$stmt = $pdo->prepare($sql_kpis);
$stmt->execute($params_base);
$kpis = $stmt->fetch(PDO::FETCH_ASSOC);

// ==========================================
// 4. VENTAS DEL DÍA (dato rápido)
// ==========================================
$sql_hoy = "SELECT 
    COUNT(*) as ventas_hoy, 
    IFNULL(SUM(total_venta), 0) as total_hoy,
    COUNT(CASE WHEN estado = 'Entregado' THEN 1 END) as entregadas_hoy
FROM ventas v
WHERE DATE(fecha_venta) = CURRENT_DATE
{$filtro_vendedor}
AND estado != 'Cancelado'";

$stmt_hoy = $pdo->prepare($sql_hoy);
$params_hoy = [];
if (!$es_admin) {
    $params_hoy['vendedor_id'] = $vendedor_id;
}
$stmt_hoy->execute($params_hoy);
$datos_hoy = $stmt_hoy->fetch(PDO::FETCH_ASSOC);

// ==========================================
// 5. COMPARACIÓN CON PERIODO ANTERIOR
// ==========================================
$dias_periodo = (strtotime($fecha_fin) - strtotime($fecha_inicio)) / 86400 + 1;
$fecha_inicio_anterior = date('Y-m-d', strtotime($fecha_inicio . " -{$dias_periodo} days"));
$fecha_fin_anterior = date('Y-m-d', strtotime($fecha_inicio . " -1 day"));

$params_anterior = [
    'inicio' => $fecha_inicio_anterior . ' 00:00:00',
    'fin' => $fecha_fin_anterior . ' 23:59:59'
];
if (!$es_admin) {
    $params_anterior['vendedor_id'] = $vendedor_id;
}

$sql_anterior = "SELECT IFNULL(SUM(total_venta), 0) as total_anterior
FROM ventas v
WHERE v.fecha_venta BETWEEN :inicio AND :fin
{$filtro_vendedor}
{$filtro_estado}";

$stmt_ant = $pdo->prepare($sql_anterior);
$stmt_ant->execute($params_anterior);
$total_anterior = floatval($stmt_ant->fetchColumn());

$total_actual = floatval($kpis['total_ingresos'] ?? 0);
$variacion = 0;
if ($total_anterior > 0) {
    $variacion = (($total_actual - $total_anterior) / $total_anterior) * 100;
} elseif ($total_actual > 0) {
    $variacion = 100;
}

// ==========================================
// 6. MÉTODOS DE PAGO
// ==========================================
$reporte_metodos = [];
if ($es_admin) {
    $sql_metodos = "SELECT metodo_pago, 
        IFNULL(SUM(total_venta), 0) as total, 
        COUNT(id) as cantidad,
        ROUND(COUNT(id) * 100.0 / (SELECT COUNT(*) FROM ventas WHERE fecha_venta BETWEEN :inicio AND :fin {$filtro_vendedor} {$filtro_estado}), 1) as porcentaje
    FROM ventas v
    WHERE fecha_venta BETWEEN :inicio AND :fin
    {$filtro_vendedor}
    {$filtro_estado}
    GROUP BY metodo_pago
    ORDER BY total DESC";
    $stmt_metodos = $pdo->prepare($sql_metodos);
    $stmt_metodos->execute($params_base);
    $reporte_metodos = $stmt_metodos->fetchAll(PDO::FETCH_ASSOC);
}

// ==========================================
// 7. VENTAS POR TIPO (Directa vs Mayorista)
// ==========================================
$sql_tipos = "SELECT tipo_venta, 
    COUNT(id) as cantidad,
    IFNULL(SUM(total_venta), 0) as total
FROM ventas v
WHERE fecha_venta BETWEEN :inicio AND :fin
{$filtro_vendedor}
{$filtro_estado}
GROUP BY tipo_venta";
$stmt_tipos = $pdo->prepare($sql_tipos);
$stmt_tipos->execute($params_base);
$ventas_por_tipo = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 8. LISTADO DE VENTAS DETALLADO
// ==========================================
$sql_detallado = "SELECT
    v.id, v.codigo_descriptivo, v.ticket_numero, v.fecha_venta, v.estado,
    c.nombre_completo as cliente,
    u.username as vendedor,
    v.metodo_pago, v.tipo_entrega, v.tipo_venta, v.total_venta
FROM ventas v
INNER JOIN clientes c ON v.cliente_id = c.id
INNER JOIN usuarios u ON v.vendedor_id = u.id
WHERE v.fecha_venta BETWEEN :inicio AND :fin
{$filtro_vendedor}
{$filtro_estado}
ORDER BY v.fecha_venta DESC
LIMIT 100";

$stmt_detallado = $pdo->prepare($sql_detallado);
$stmt_detallado->execute($params_base);
$ventas_detalladas = $stmt_detallado->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 9. TOP PRODUCTOS
// ==========================================
$sql_top = "SELECT
    p.nombre, p.referencia,
    SUM(dv.cantidad) as total_vendido,
    SUM(dv.cantidad * dv.precio_unitario) as total_recaudado
FROM detalle_venta dv
INNER JOIN productos p ON dv.producto_id = p.id
INNER JOIN ventas v ON dv.venta_id = v.id
WHERE v.fecha_venta BETWEEN :inicio AND :fin
{$filtro_vendedor}
{$filtro_estado}
GROUP BY p.id
ORDER BY total_vendido DESC
LIMIT " . ($es_admin ? 5 : 3);

$stmt_top = $pdo->prepare($sql_top);
$stmt_top->execute($params_base);
$top_productos = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 10. TOP CLIENTES (solo admin)
// ==========================================
$top_clientes = [];
if ($es_admin) {
    $sql_top_clientes = "SELECT
        c.nombre_completo,
        COUNT(v.id) as total_compras,
        IFNULL(SUM(v.total_venta), 0) as total_gastado
    FROM clientes c
    INNER JOIN ventas v ON c.id = v.cliente_id
    WHERE v.fecha_venta BETWEEN :inicio AND :fin
    {$filtro_vendedor}
    {$filtro_estado}
    GROUP BY c.id
    ORDER BY total_gastado DESC
    LIMIT 5";
    $stmt_tc = $pdo->prepare($sql_top_clientes);
    $stmt_tc->execute($params_base);
    $top_clientes = $stmt_tc->fetchAll(PDO::FETCH_ASSOC);
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
<?php include(__DIR__ . "/sidebar_control.php"); ?>
<main class="main-content-panel">

<!-- ENCABEZADO -->
<div class="page-header header-dashboard">
    <div>
        <h1>
            <?= $es_admin ? '📊 Reportes Globales de Ventas' : '📈 Mi Rendimiento Comercial' ?>
        </h1>
        <p>
            <?= $es_admin 
                ? 'Análisis completo del rendimiento comercial de Unideportes.' 
                : 'Consulta tu productividad, ventas y productos destacados.' ?>
        </p>
    </div>
    <div style="text-align: right;">
        <span class="badge azul">
            <?= $es_admin ? '👑 Vista Administrador' : '👤 Vista Vendedor' ?>
        </span>
        <br>
        <small style="color: #64748b;">
            Periodo: <?= date('d/m/Y', strtotime($fecha_inicio)) ?> al <?= date('d/m/Y', strtotime($fecha_fin)) ?>
        </small>
    </div>
</div>

<!-- FORMULARIO DE FILTROS MEJORADO -->
<div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid var(--border); margin-bottom: 25px;">
    <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
        <div>
            <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 0.9rem;">Fecha Inicial</label>
            <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>"
                style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>
        <div>
            <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 0.9rem;">Fecha Final</label>
            <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>"
                style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>
        <div>
            <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 0.9rem;">Estado</label>
            <select name="estado" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                <option value="activas" <?= $estado_filtro === 'activas' ? 'selected' : '' ?>>✅ Activas (sin canceladas)</option>
                <option value="todas" <?= $estado_filtro === 'todas' ? 'selected' : '' ?>>📋 Todas</option>
                <option value="entregadas" <?= $estado_filtro === 'entregadas' ? 'selected' : '' ?>>📦 Entregadas</option>
                <option value="pendientes" <?= $estado_filtro === 'pendientes' ? 'selected' : '' ?>>⏳ Pendientes</option>
                <option value="canceladas" <?= $estado_filtro === 'canceladas' ? 'selected' : '' ?>>❌ Canceladas</option>
            </select>
        </div>
        <button type="submit" class="btn-primary" style="padding: 10px 20px;">
            🔍 Filtrar
        </button>
    </form>
</div>

<!-- DATO RÁPIDO DEL DÍA (solo vendedor) -->
<?php if (!$es_admin): ?>
<div style="background: linear-gradient(135deg, #1e293b, #334155); color: white; padding: 20px; border-radius: 10px; margin-bottom: 25px; display: flex; justify-content: space-around; flex-wrap: wrap; gap: 15px;">
    <div style="text-align: center;">
        <div style="font-size: 0.85rem; opacity: 0.8;">📅 Ventas HOY</div>
        <div style="font-size: 1.8rem; font-weight: 700;"><?= intval($datos_hoy['ventas_hoy']) ?></div>
        <small style="opacity: 0.7;"><?= intval($datos_hoy['entregadas_hoy']) ?> entregadas</small>
    </div>
    <div style="text-align: center;">
        <div style="font-size: 0.85rem; opacity: 0.8;">💵 Recaudado HOY</div>
        <div style="font-size: 1.8rem; font-weight: 700;">$<?= number_format(floatval($datos_hoy['total_hoy']), 0, ',', '.') ?></div>
    </div>
</div>
<?php endif; ?>

<!-- KPIs PRINCIPALES CON VARIACIÓN -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px;">
    <!-- Total Ingresos con variación -->
    <div style="background: #f0fdf4; padding: 18px; border-radius: 10px; border-left: 4px solid var(--success);">
        <div style="color: #166534; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
            Total Ingresos
            <?php if ($variacion != 0): ?>
                <span style="float: right; font-size: 0.8rem;">
                    <?= $variacion > 0 ? '📈' : '📉' ?>
                    <?= number_format(abs($variacion), 1) ?>%
                </span>
            <?php endif; ?>
        </div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #166534; margin-top: 5px;">
            $<?= number_format(floatval($kpis['total_ingresos'] ?? 0), 0, ',', '.') ?>
        </div>
        <small style="color: #64748b;">
            vs periodo anterior: $<?= number_format($total_anterior, 0, ',', '.') ?>
        </small>
    </div>
    
    <!-- Transacciones -->
    <div style="background: #eff6ff; padding: 18px; border-radius: 10px; border-left: 4px solid #2563eb;">
        <div style="color: #1e40af; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Transacciones</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #1e40af; margin-top: 5px;">
            <?= intval($kpis['total_transacciones'] ?? 0) ?> facturas
        </div>
    </div>
    
    <!-- Ticket Promedio -->
    <div style="background: #fef3c7; padding: 18px; border-radius: 10px; border-left: 4px solid #d97706;">
        <div style="color: #92400e; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Ticket Promedio</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #92400e; margin-top: 5px;">
            $<?= number_format(floatval($kpis['promedio_ticket'] ?? 0), 0, ',', '.') ?>
        </div>
    </div>

    <!-- Descuentos Otorgados -->
    <div style="background: #fef2f2; padding: 18px; border-radius: 10px; border-left: 4px solid #dc2626;">
        <div style="color: #991b1b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Descuentos</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #991b1b; margin-top: 5px;">
            $<?= number_format(floatval($kpis['total_descuentos'] ?? 0), 0, ',', '.') ?>
        </div>
    </div>
</div>

<!-- DESGLOSE DE INGRESOS POR ESTADO -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
    <div style="background: white; padding: 18px; border-radius: 10px; border: 1px solid var(--border);">
        <div style="color: #059669; font-size: 0.85rem; font-weight: 600;">✅ Ingresos Entregados</div>
        <div style="font-size: 1.3rem; font-weight: 700; color: #059669; margin-top: 5px;">
            $<?= number_format(floatval($kpis['ingresos_entregados'] ?? 0), 0, ',', '.') ?>
        </div>
    </div>
    <div style="background: white; padding: 18px; border-radius: 10px; border: 1px solid var(--border);">
        <div style="color: #d97706; font-size: 0.85rem; font-weight: 600;">⏳ Ingresos Pendientes</div>
        <div style="font-size: 1.3rem; font-weight: 700; color: #d97706; margin-top: 5px;">
            $<?= number_format(floatval($kpis['ingresos_pendientes'] ?? 0), 0, ',', '.') ?>
        </div>
    </div>
</div>

<!-- MÉTODOS DE PAGO Y TIPOS DE VENTA -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
    <!-- Métodos de Pago (Solo Admin) -->
    <?php if ($es_admin && !empty($reporte_metodos)): ?>
    <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid var(--border);">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #c91a25; padding-bottom: 8px;">
            💳 Métodos de Pago
        </h3>
        <div style="display: grid; gap: 10px; margin-top: 15px;">
            <?php foreach($reporte_metodos as $metodo): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8fafc; border-radius: 6px;">
                <div>
                    <strong><?= htmlspecialchars($metodo['metodo_pago']) ?></strong>
                    <small style="color: #64748b; display: block;">
                        <?= intval($metodo['cantidad']) ?> ops (<?= $metodo['porcentaje'] ?>%)
                    </small>
                </div>
                <span style="background: #1e293b; color: white; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 0.9rem;">
                    $<?= number_format($metodo['total'], 0, ',', '.') ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tipos de Venta -->
    <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid var(--border);">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #c91a25; padding-bottom: 8px;">
            🛒 Tipos de Venta
        </h3>
        <div style="display: grid; gap: 10px; margin-top: 15px;">
            <?php foreach($ventas_por_tipo as $tipo): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8fafc; border-radius: 6px;">
                <div>
                    <strong><?= $tipo['tipo_venta'] === 'mayorista' ? '🏭 Mayorista' : '🛍️ Directa' ?></strong>
                    <small style="color: #64748b; display: block;"><?= intval($tipo['cantidad']) ?> ventas</small>
                </div>
                <span style="background: <?= $tipo['tipo_venta'] === 'mayorista' ? '#7c3aed' : '#2563eb' ?>; color: white; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 0.9rem;">
                    $<?= number_format($tipo['total'], 0, ',', '.') ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- TOP PRODUCTOS Y TOP CLIENTES -->
<div style="display: grid; grid-template-columns: <?= $es_admin ? '1fr 1fr' : '1fr' ?>; gap: 15px; margin-bottom: 25px;">
    <!-- Top Productos -->
    <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid var(--border);">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #c91a25; padding-bottom: 8px;">
            🏆 <?= $es_admin ? 'Top 5 Productos Más Vendidos' : 'Mis 3 Productos Estrella' ?>
        </h3>
        <div class="table-responsive" style="margin-top: 15px;">
            <table class="tabla-maestra">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Ref</th>
                        <th style="text-align: center;">Cant.</th>
                        <th style="text-align: right;">Recaudado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_productos)): ?>
                    <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">Sin datos en este periodo</td></tr>
                    <?php else: ?>
                    <?php foreach($top_productos as $i => $prod): ?>
                    <tr>
                        <td>
                            <strong>
                            <?php
                            $medallas = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                            echo $medallas[$i] ?? '•';
                            ?>
                            <?= htmlspecialchars($prod['nombre']) ?>
                            </strong>
                        </td>
                        <td><span class="badge gris"><?= htmlspecialchars($prod['referencia']) ?></span></td>
                        <td style="text-align: center; font-weight: 700;"><?= intval($prod['total_vendido']) ?></td>
                        <td style="text-align: right; color: #059669; font-weight: 700;">
                            $<?= number_format($prod['total_recaudado'], 0, ',', '.') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Clientes (Solo Admin) -->
    <?php if ($es_admin): ?>
    <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid var(--border);">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #c91a25; padding-bottom: 8px;">
            👑 Top 5 Clientes
        </h3>
        <div class="table-responsive" style="margin-top: 15px;">
            <table class="tabla-maestra">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th style="text-align: center;">Compras</th>
                        <th style="text-align: right;">Total Gastado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_clientes)): ?>
                    <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">Sin datos</td></tr>
                    <?php else: ?>
                    <?php foreach($top_clientes as $i => $cli): ?>
                    <tr>
                        <td>
                            <strong>
                            <?php
                            $medallas = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                            echo $medallas[$i] ?? '•';
                            ?>
                            <?= htmlspecialchars($cli['nombre_completo']) ?>
                            </strong>
                        </td>
                        <td style="text-align: center; font-weight: 700;"><?= intval($cli['total_compras']) ?></td>
                        <td style="text-align: right; color: #059669; font-weight: 700;">
                            $<?= number_format($cli['total_gastado'], 0, ',', '.') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- BITÁCORA DE VENTAS CON CÓDIGO DESCRIPTIVO -->
<div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid var(--border);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid #c91a25; padding-bottom: 8px;">
        <h3 style="margin: 0; color: #1e293b; font-size: 1.1rem;">
            📋 <?= $es_admin ? 'Bitácora Global de Ventas' : 'Mis Ventas del Periodo' ?>
            <small style="color: #64748b; font-weight: normal;">(últimas 100)</small>
        </h3>
        <button onclick="window.print();" class="btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;">
            🖨️ Imprimir
        </button>
    </div>
    <div class="table-responsive">
        <table class="tabla-maestra">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Fecha/Hora</th>
                    <th>Cliente</th>
                    <?php if ($es_admin): ?><th>Vendedor</th><?php endif; ?>
                    <th>Tipo</th>
                    <th>Método</th>
                    <th>Estado</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ventas_detalladas)): ?>
                <tr><td colspan="<?= $es_admin ? 8 : 7 ?>" style="text-align: center; color: #94a3b8; padding: 30px;">
                    No hay ventas en este periodo.
                </td></tr>
                <?php else: ?>
                <?php foreach($ventas_detalladas as $v): ?>
                <tr>
                    <td>
                        <strong style="color: #2563eb;">
                            <?= htmlspecialchars($v['codigo_descriptivo'] ?: 'VEN-' . str_pad($v['id'], 6, '0', STR_PAD_LEFT)) ?>
                        </strong>
                        <br>
                        <small style="color: #94a3b8; font-size: 0.75rem;">
                            <?= htmlspecialchars($v['ticket_numero']) ?>
                        </small>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($v['fecha_venta'])) ?></td>
                    <td><?= htmlspecialchars($v['cliente']) ?></td>
                    <?php if ($es_admin): ?>
                    <td><span class="badge azul"><?= htmlspecialchars($v['vendedor']) ?></span></td>
                    <?php endif; ?>
                    <td>
                        <span class="badge <?= $v['tipo_venta'] === 'mayorista' ? 'morado' : 'verde' ?>">
                            <?= $v['tipo_venta'] === 'mayorista' ? '🏭 Mayorista' : '🛍️ Directa' ?>
                        </span>
                    </td>
                    <td><span class="badge gris"><?= htmlspecialchars($v['metodo_pago']) ?></span></td>
                    <td>
                        <?php
                        $estado_colors = [
                            'Entregado' => '#10b981',
                            'Pendiente' => '#f59e0b',
                            'Cancelado' => '#ef4444'
                        ];
                        $color = $estado_colors[$v['estado']] ?? '#64748b';
                        ?>
                        <span style="background: <?= $color ?>; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                            <?= $v['estado'] ?>
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: 700; color: #059669;">
                        $<?= number_format($v['total_venta'], 0, ',', '.') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main>
</div>

<style>
.badge.morado {
    background: #7c3aed;
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}
.badge.verde {
    background: #10b981;
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}
@media print {
    .sidebar-panel, .page-header, form, button { display: none !important; }
    .container, .admin-layout, .main-content-panel {
        margin: 0 !important; padding: 0 !important;
        width: 100% !important; max-width: 100% !important;
    }
}
@media (max-width: 768px) {
    form {
        grid-template-columns: 1fr !important;
    }
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>