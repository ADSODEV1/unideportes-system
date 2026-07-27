<?php
/**
 * Partial: Tarjetas de KPIs de Cartera
 */
$porcentaje = calcularPorcentajeRecuperacion($data['kpis_cartera']);
$total_cobrado = ($data['kpis_cartera']['total_abonos_iniciales'] ?? 0) + 
                 ($data['kpis_cartera']['total_abonos_periodo'] ?? 0);
?>
<div class="kpis-grid">
    <a href="#sec-ventas-detalladas" class="kpi-card kpi-card--info kpi-clickable" style="text-decoration: none; color: inherit; cursor: pointer;">
        <div class="kpi-card__content">
            <h6 class="kpi-card__label">Total Facturado</h6>
            <h3 class="kpi-card__value">
                <?= formatMoney($data['kpis_cartera']['total_facturado'] ?? 0) ?>
            </h3>
        </div>
        <span class="kpi-card__icon">📄</span>
    </a>

    <a href="#sec-abonos-recientes" class="kpi-card kpi-card--success kpi-clickable" style="text-decoration: none; color: inherit; cursor: pointer;">
        <div class="kpi-card__content">
            <h6 class="kpi-card__label">Total Cobrado</h6>
            <h3 class="kpi-card__value">
                <?= formatMoney($total_cobrado) ?>
            </h3>
        </div>
        <span class="kpi-card__icon">✅</span>
    </a>

    <a href="#sec-pedidos-pendientes" class="kpi-card kpi-card--danger kpi-clickable" style="text-decoration: none; color: inherit; cursor: pointer;">
        <div class="kpi-card__content">
            <h6 class="kpi-card__label">Por Cobrar</h6>
            <h3 class="kpi-card__value">
                <?= formatMoney($data['kpis_cartera']['saldo_pendiente_total'] ?? 0) ?>
            </h3>
        </div>
        <span class="kpi-card__icon">⏳</span>
    </a>

    <a href="#sec-pedidos-pendientes" class="kpi-card kpi-card--secondary kpi-clickable" style="text-decoration: none; color: inherit; cursor: pointer;">
        <div class="kpi-card__content">
            <h6 class="kpi-card__label">% Recuperación</h6>
            <h3 class="kpi-card__value">
                <?= number_format($porcentaje, 1) ?>%
            </h3>
        </div>
        <span class="kpi-card__icon">📈</span>
    </a>
</div>