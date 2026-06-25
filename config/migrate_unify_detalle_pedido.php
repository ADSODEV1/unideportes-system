<?php
require_once __DIR__ . '/bootstrap.php';

$pdo = app();

/**
 * Elimina un objeto legacy por nombre sin importar si es VIEW o TABLE.
 */
function dropLegacyObject(PDO $pdo, string $objectName): void {
    $stmt = $pdo->prepare(
        "SELECT TABLE_TYPE
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :name
         LIMIT 1"
    );
    $stmt->execute([':name' => $objectName]);
    $type = $stmt->fetchColumn();

    if ($type === 'VIEW') {
        $pdo->exec("DROP VIEW IF EXISTS `{$objectName}`");
        echo "DROPPED VIEW: {$objectName}" . PHP_EOL;
        return;
    }

    if ($type === 'BASE TABLE') {
        $pdo->exec("DROP TABLE IF EXISTS `{$objectName}`");
        echo "DROPPED TABLE: {$objectName}" . PHP_EOL;
    }
}

try {
    // 1. Eliminar cualquier rastro legacy (vista o tabla) con ambos nombres.
    dropLegacyObject($pdo, 'vista_saldos_pedido');
    dropLegacyObject($pdo, 'vista_saldos_pedidos');
    echo "OK: objetos legacy vista_saldos_pedido* eliminados" . PHP_EOL;

    // 2. Agregar columnas de cartera a detalle_pedido si no existen
    $columnsToAdd = [
        "total_pedido DECIMAL(10,2) DEFAULT 0 COMMENT 'Total del pedido padre'",
        "abono_pedido DECIMAL(10,2) DEFAULT 0 COMMENT 'Abono registrado en pedido'",
        "pagos_registrados DECIMAL(10,2) DEFAULT 0 COMMENT 'Pagos posteriores registrados'",
        "saldo_pendiente DECIMAL(10,2) DEFAULT 0 COMMENT 'Saldo pendiente del pedido padre'",
        "estado_cartera VARCHAR(20) DEFAULT 'Por Pagar' COMMENT 'Estado de cartera: Pagado o Por Pagar'",
    ];

    foreach ($columnsToAdd as $colDef) {
        $colName = explode(' ', $colDef)[0];
        $checkStmt = $pdo->query("SHOW COLUMNS FROM detalle_pedido LIKE '$colName'");
        if ($checkStmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE detalle_pedido ADD COLUMN $colDef");
            echo "ADDED: $colName" . PHP_EOL;
        } else {
            echo "EXISTS: $colName" . PHP_EOL;
        }
    }

    // 3. Poblar las columnas con datos actuales
    $pdo->exec(<<<SQL
UPDATE detalle_pedido dp
INNER JOIN pedidos p ON p.id = dp.pedido_id
LEFT JOIN (
    SELECT id_pg_pedido, SUM(monto) AS total_pagado
    FROM pagos
    GROUP BY id_pg_pedido
) pg ON pg.id_pg_pedido = dp.pedido_id
SET
    dp.total_pedido = COALESCE(
        (SELECT SUM(dp2.cantidad * dp2.precio_unitario) FROM detalle_pedido dp2 WHERE dp2.pedido_id = dp.pedido_id),
        p.total_pedido,
        0
    ),
    dp.abono_pedido = COALESCE(p.abono, 0),
    dp.pagos_registrados = COALESCE(pg.total_pagado, 0),
    dp.saldo_pendiente = GREATEST(
        COALESCE(p.saldo_pendiente, 0),
        COALESCE(
            (SELECT SUM(dp2.cantidad * dp2.precio_unitario) FROM detalle_pedido dp2 WHERE dp2.pedido_id = dp.pedido_id),
            p.total_pedido,
            0
        ) - (COALESCE(p.abono, 0) + COALESCE(pg.total_pagado, 0))
    ),
    dp.estado_cartera = CASE
        WHEN GREATEST(
            COALESCE(p.saldo_pendiente, 0),
            COALESCE(
                (SELECT SUM(dp2.cantidad * dp2.precio_unitario) FROM detalle_pedido dp2 WHERE dp2.pedido_id = dp.pedido_id),
                p.total_pedido,
                0
            ) - (COALESCE(p.abono, 0) + COALESCE(pg.total_pagado, 0))
        ) <= 0 THEN 'Pagado'
        ELSE 'Por Pagar'
    END
SQL
    );

    echo "OK: detalle_pedido poblada con datos de cartera" . PHP_EOL;

    // 4. Verificar resultado
    $checkStmt = $pdo->query("SELECT COUNT(*) FROM detalle_pedido WHERE saldo_pendiente > 0");
    $porPagar = (int)$checkStmt->fetchColumn();
    
    $checkStmt2 = $pdo->query("SELECT COUNT(*) FROM detalle_pedido WHERE estado_cartera = 'Pagado'");
    $pagados = (int)$checkStmt2->fetchColumn();

    echo "FINAL: $porPagar detalles con saldo pendiente, $pagados pagados" . PHP_EOL;

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
