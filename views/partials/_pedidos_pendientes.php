<?php
/**
 * Partial: Pedidos con Saldo Pendiente
 */
?>
<div class="report-card">
    <div class="report-card__header report-card__header--warning">
        ⏳ Top 20 Pedidos con Saldo Pendiente
    </div>
    <div class="report-card__body report-card__body--no-padding">
        <div class="table-responsive">
            <table class="tabla-maestra">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>NIT/Cédula</th>
                        <th class="text-end">Total Pedido</th>
                        <th class="text-end">Pagado</th>
                        <th class="text-end">Saldo Pendiente</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['pedidos_pendientes'])): ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                ✅ No hay pedidos con saldo pendiente
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['pedidos_pendientes'] as $pend): ?>
                            <?php 
                            $total_pagado = $pend['abono_inicial'] + $pend['total_pagos'];
                            ?>
                            <tr>
                                <td><strong>#<?= $pend['id'] ?></strong></td>
                                <td><?= htmlspecialchars($pend['cliente']) ?></td>
                                <td><?= htmlspecialchars($pend['nit_cedula']) ?></td>
                                <td class="text-end">
                                    <?= formatMoney($pend['total_pedido']) ?>
                                </td>
                                <td class="text-end text-success">
                                    <?= formatMoney($total_pagado) ?>
                                </td>
                                <td class="text-end text-danger fw-bold">
                                    <?= formatMoney($pend['saldo_pendiente']) ?>
                                </td>
                                <td>
                                    <span class="report-badge report-badge--warning">
                                        <?= htmlspecialchars($pend['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>