<?php
/**
 * Partial: Top 5 Productos
 */
?>
<div class="report-card">
    <div class="report-card__header">
        🏆 Top 5 Productos Más Vendidos
    </div>
    <div class="report-card__body report-card__body--no-padding">
        <div class="table-responsive">
            <table class="tabla-maestra">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Ref</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-end">Ingresos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['top_productos'])): ?>
                        <tr>
                            <td colspan="4" class="empty-state">Sin datos de ventas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['top_productos'] as $prod): ?>
                            <tr>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($prod['nombre']) ?>
                                </td>
                                <td>
                                    <span class="report-badge report-badge--light">
                                        <?= htmlspecialchars($prod['referencia']) ?>
                                    </span>
                                </td>
                                <td class="text-center fw-bold">
                                    <?= $prod['total_vendido'] ?>
                                </td>
                                <td class="text-end text-success fw-bold">
                                    <?= formatMoney($prod['total_recaudado']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>