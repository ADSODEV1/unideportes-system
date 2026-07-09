<?php
/**
 * Partial: Métodos de Pago
 */
?>
<div class="report-card">
    <div class="report-card__header">
        💳 Ingresos por Método de Pago
    </div>
    <div class="report-card__body report-card__body--no-padding">
        <?php if (empty($data['ventas_metodo'])): ?>
            <p class="empty-state">No hay registros en este período</p>
        <?php else: ?>
            <ul class="report-list">
                <?php foreach ($data['ventas_metodo'] as $metodo): ?>
                    <li class="report-list__item">
                        <div>
                            <strong class="report-list__title">
                                <?= htmlspecialchars($metodo['metodo_pago']) ?>
                            </strong>
                            <small class="report-list__subtitle">
                                <?= $metodo['cantidad'] ?> operaciones
                            </small>
                        </div>
                        <span class="report-badge report-badge--secondary">
                            <?= formatMoney($metodo['total']) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>