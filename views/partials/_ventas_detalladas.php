<?php
/**
 * Partial: Ventas Detalladas
 */
?>
<div class="report-card">
    <div class="report-card__header">
        🧾 Listado Completo de Ventas
    </div>
    <div class="report-card__body report-card__body--no-padding">
        <div class="table-responsive">
            <table class="tabla-maestra">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Vendedor</th>
                        <th>Método</th>
                        <th>Entrega</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['ventas_detalladas'])): ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                No hay ventas en este rango de fechas
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['ventas_detalladas'] as $venta): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($venta['ticket_numero']) ?></strong></td>
                                <td><?= formatDate($venta['fecha_venta']) ?></td>
                                <td><?= htmlspecialchars($venta['cliente']) ?></td>
                                <td><?= htmlspecialchars($venta['vendedor']) ?></td>
                                <td>
                                    <span class="report-badge report-badge--light">
                                        <?= htmlspecialchars($venta['metodo_pago']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($venta['tipo_entrega']) ?></td>
                                <td class="text-end fw-bold">
                                    <?= formatMoney($venta['total_venta']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>