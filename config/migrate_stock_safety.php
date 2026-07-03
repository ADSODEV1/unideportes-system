<?php
require_once __DIR__ . '/bootstrap.php';

$pdo = app();

try {
    // 1) Normaliza valores de stock inválidos heredados.
    $updated = $pdo->exec("UPDATE productos SET stock = 0 WHERE stock < 0");

    // 2) Evita sobredescuento: los pedidos de confección (detalle_pedido)
    // no deben modificar stock de inventario físico.
    $triggersToDrop = [
        'tg_restar_stock_pedido',
        'tg_actualizar_stock_pedido',
        'tg_devolver_stock_pedido',
    ];

    foreach ($triggersToDrop as $trigger) {
        $pdo->exec("DROP TRIGGER IF EXISTS `{$trigger}`");
    }

    $remainingNegatives = (int)$pdo->query("SELECT COUNT(*) FROM productos WHERE stock < 0")->fetchColumn();

    echo "OK: stock_normalized_rows={$updated}; negative_remaining={$remainingNegatives}" . PHP_EOL;
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
