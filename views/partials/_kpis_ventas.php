<?php
/**
 * Partial: Tarjetas de KPIs de Ventas
 */
?>
<div class="kpis-grid">
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__content">
            <h6 class="kpi-card__label">Total Ingresos</h6>
            <h3 class="kpi-card__value">
                <?= formatMoney($data['kpis_ventas']['total_ingresos'] ?? 0) ?>
            </h3>
        </div>
        <span class="kpi-card__icon">💰</span>
    </div>

    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__content">
            <h6 class="kpi-card__label">Transacciones</h6>
            <h3 class="kpi-card__value">
                <?= number_format($data['kpis_ventas']['total_transacciones'] ?? 0, 0, ',', '.') ?> Facturas
            </h3>
        </div>
        <span class="kpi-card__icon">🧾</span>
    </div>

    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__content">
            <h6 class="kpi-card__label">Ticket Promedio</h6>
            <h3 class="kpi-card__value">
                <?= formatMoney($data['kpis_ventas']['promedio_ticket'] ?? 0) ?>
            </h3>
        </div>
        <span class="kpi-card__icon">📊</span>
    </div>
</div>