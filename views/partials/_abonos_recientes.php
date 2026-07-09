<?php
/**
 * Partial: Abonos Recientes
 */
?>
<div class="report-card">
    <div class="report-card__header report-card__header--success">
        ✅ Abonos Registrados en el Período
    </div>
    <div class="report-card__body report-card__body--no-padding">
        <div class="table-responsive">
            <table class="tabla-maestra">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Método</th>
                        <th class="text-end">Monto Abono</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['abonos_recientes'])): ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                No hay abonos en este período
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['abonos_recientes'] as $abono): ?>
                            <tr>
                                <td><?= formatDate($abono['fecha']) ?></td>
                                <td><strong>#<?= $abono['id_pg_pedido'] ?></strong></td>
                                <td><?= htmlspecialchars($abono['cliente']) ?></td>
                                <td>
                                    <span class="report-badge report-badge--info">
                                        <?= htmlspecialchars($abono['metodo_pago'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td class="text-end text-success fw-bold">
                                    <?= formatMoney($abono['monto']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>