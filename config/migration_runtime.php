<?php

function migrationExecOrFail(PDO $pdo, string $sql, string $label): void {
    try {
        $pdo->exec($sql);
    } catch (Throwable $e) {
        throw new RuntimeException($label . ': ' . $e->getMessage());
    }
}

function migrationDropLegacyObject(PDO $pdo, string $objectName): void {
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

function migrationNormalizeStock(PDO $pdo): void {
    $updated = $pdo->exec("UPDATE productos SET stock = 0 WHERE stock < 0");

    $triggersToDrop = [
        'tg_restar_stock_pedido',
        'tg_actualizar_stock_pedido',
        'tg_devolver_stock_pedido',
    ];

    foreach ($triggersToDrop as $trigger) {
        $pdo->exec("DROP TRIGGER IF EXISTS `{$trigger}`");
    }

    $remainingNegatives = (int) $pdo->query("SELECT COUNT(*) FROM productos WHERE stock < 0")->fetchColumn();
    echo "OK: stock_normalized_rows={$updated}; negative_remaining={$remainingNegatives}" . PHP_EOL;
}

function migrationEnsureDetalleColumns(PDO $pdo): void {
    migrationDropLegacyObject($pdo, 'vista_saldos_pedido');
    migrationDropLegacyObject($pdo, 'vista_saldos_pedidos');
    echo "OK: objetos legacy vista_saldos_pedido* eliminados" . PHP_EOL;

    $columnsToAdd = [
        "total_pedido DECIMAL(10,2) DEFAULT 0 COMMENT 'Total del pedido padre'",
        "abono_pedido DECIMAL(10,2) DEFAULT 0 COMMENT 'Abono registrado en pedido'",
        "pagos_registrados DECIMAL(10,2) DEFAULT 0 COMMENT 'Pagos posteriores registrados'",
        "saldo_pendiente DECIMAL(10,2) DEFAULT 0 COMMENT 'Saldo pendiente del pedido padre'",
        "estado_cartera VARCHAR(20) DEFAULT 'Por Pagar' COMMENT 'Estado de cartera: Pagado o Por Pagar'",
    ];

    foreach ($columnsToAdd as $colDef) {
        $colName = explode(' ', $colDef)[0];
        $checkStmt = $pdo->query("SHOW COLUMNS FROM detalle_pedido LIKE '{$colName}'");
        if ($checkStmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE detalle_pedido ADD COLUMN {$colDef}");
            echo "ADDED: {$colName}" . PHP_EOL;
        } else {
            echo "EXISTS: {$colName}" . PHP_EOL;
        }
    }
}

function migrationBackfillDetalleCartera(PDO $pdo): void {
    $updated = $pdo->exec(
        "UPDATE detalle_pedido dp
         INNER JOIN pedidos p ON p.id = dp.pedido_id
         LEFT JOIN (
             SELECT id_pg_pedido, SUM(monto) AS total_pagado
             FROM pagos
             GROUP BY id_pg_pedido
         ) pg ON pg.id_pg_pedido = dp.pedido_id
         SET
             dp.total_pedido = COALESCE(
                 (SELECT SUM(dp2.cantidad * dp2.precio_unitario)
                  FROM detalle_pedido dp2
                  WHERE dp2.pedido_id = dp.pedido_id),
                 p.total_pedido,
                 0
             ),
             dp.abono_pedido = COALESCE(p.abono, 0),
             dp.pagos_registrados = COALESCE(pg.total_pagado, 0),
             dp.saldo_pendiente = GREATEST(
                 0,
                 COALESCE(
                     (SELECT SUM(dp3.cantidad * dp3.precio_unitario)
                      FROM detalle_pedido dp3
                      WHERE dp3.pedido_id = dp.pedido_id),
                     p.total_pedido,
                     0
                 ) - (COALESCE(p.abono, 0) + COALESCE(pg.total_pagado, 0))
             ),
             dp.estado_cartera = CASE
                 WHEN GREATEST(
                     0,
                     COALESCE(
                         (SELECT SUM(dp4.cantidad * dp4.precio_unitario)
                          FROM detalle_pedido dp4
                          WHERE dp4.pedido_id = dp.pedido_id),
                         p.total_pedido,
                         0
                     ) - (COALESCE(p.abono, 0) + COALESCE(pg.total_pagado, 0))
                 ) <= 0 THEN 'Pagado'
                 ELSE 'Por Pagar'
             END"
    );

    $porPagar = (int) $pdo->query("SELECT COUNT(*) FROM detalle_pedido WHERE saldo_pendiente > 0")->fetchColumn();
    $pagados = (int) $pdo->query("SELECT COUNT(*) FROM detalle_pedido WHERE estado_cartera = 'Pagado'")->fetchColumn();

    echo "OK: detalle_pedido_cartera_sync rows={$updated}; por_pagar={$porPagar}; pagados={$pagados}" . PHP_EOL;
}

function migrationInstallCarteraSyncTriggers(PDO $pdo): void {
    migrationExecOrFail($pdo, 'DROP PROCEDURE IF EXISTS sp_sync_detalle_pedido_cartera', 'DROP PROCEDURE');

    $createProcedure = <<<'SQL'
CREATE PROCEDURE sp_sync_detalle_pedido_cartera(IN p_pedido_id INT)
BEGIN
    proc: BEGIN
        IF p_pedido_id IS NULL OR p_pedido_id <= 0 THEN
            LEAVE proc;
        END IF;

        UPDATE detalle_pedido dp
        INNER JOIN pedidos p ON p.id = dp.pedido_id
        LEFT JOIN (
            SELECT id_pg_pedido, SUM(monto) AS total_pagado
            FROM pagos
            GROUP BY id_pg_pedido
        ) pg ON pg.id_pg_pedido = dp.pedido_id
        SET
            dp.total_pedido = COALESCE(
                (SELECT SUM(dp2.cantidad * dp2.precio_unitario)
                 FROM detalle_pedido dp2
                 WHERE dp2.pedido_id = dp.pedido_id),
                p.total_pedido,
                0
            ),
            dp.abono_pedido = COALESCE(p.abono, 0),
            dp.pagos_registrados = COALESCE(pg.total_pagado, 0),
            dp.saldo_pendiente = GREATEST(
                0,
                COALESCE(
                    (SELECT SUM(dp3.cantidad * dp3.precio_unitario)
                     FROM detalle_pedido dp3
                     WHERE dp3.pedido_id = dp.pedido_id),
                    p.total_pedido,
                    0
                ) - (COALESCE(p.abono, 0) + COALESCE(pg.total_pagado, 0))
            ),
            dp.estado_cartera = CASE
                WHEN GREATEST(
                    0,
                    COALESCE(
                        (SELECT SUM(dp4.cantidad * dp4.precio_unitario)
                         FROM detalle_pedido dp4
                         WHERE dp4.pedido_id = dp.pedido_id),
                        p.total_pedido,
                        0
                    ) - (COALESCE(p.abono, 0) + COALESCE(pg.total_pagado, 0))
                ) <= 0 THEN 'Pagado'
                ELSE 'Por Pagar'
            END
        WHERE dp.pedido_id = p_pedido_id;
    END;
END
SQL;

    migrationExecOrFail($pdo, $createProcedure, 'CREATE PROCEDURE');

    $triggers = [
        'trg_pedidos_ai_sync_cartera',
        'trg_pagos_ai_sync_cartera',
        'trg_pagos_au_sync_cartera',
        'trg_pagos_ad_sync_cartera',
        'trg_pedidos_au_sync_cartera',
        'trg_detalle_pedido_ai_sync_cartera',
        'trg_detalle_pedido_au_sync_cartera',
        'trg_detalle_pedido_ad_sync_cartera',
    ];

    foreach ($triggers as $trigger) {
        migrationExecOrFail($pdo, "DROP TRIGGER IF EXISTS `{$trigger}`", 'DROP TRIGGER ' . $trigger);
    }

    migrationExecOrFail(
        $pdo,
        "CREATE TRIGGER trg_pedidos_ai_sync_cartera
         AFTER INSERT ON pedidos
         FOR EACH ROW
         BEGIN
             CALL sp_sync_detalle_pedido_cartera(NEW.id);
         END",
        'CREATE TRIGGER pedidos AI'
    );

    migrationExecOrFail(
        $pdo,
        "CREATE TRIGGER trg_pagos_ai_sync_cartera
         AFTER INSERT ON pagos
         FOR EACH ROW
         BEGIN
             CALL sp_sync_detalle_pedido_cartera(NEW.id_pg_pedido);
         END",
        'CREATE TRIGGER pagos AI'
    );

    migrationExecOrFail(
        $pdo,
        "CREATE TRIGGER trg_pagos_au_sync_cartera
         AFTER UPDATE ON pagos
         FOR EACH ROW
         BEGIN
             CALL sp_sync_detalle_pedido_cartera(NEW.id_pg_pedido);
             IF OLD.id_pg_pedido <> NEW.id_pg_pedido THEN
                 CALL sp_sync_detalle_pedido_cartera(OLD.id_pg_pedido);
             END IF;
         END",
        'CREATE TRIGGER pagos AU'
    );

    migrationExecOrFail(
        $pdo,
        "CREATE TRIGGER trg_pagos_ad_sync_cartera
         AFTER DELETE ON pagos
         FOR EACH ROW
         BEGIN
             CALL sp_sync_detalle_pedido_cartera(OLD.id_pg_pedido);
         END",
        'CREATE TRIGGER pagos AD'
    );

    migrationExecOrFail(
        $pdo,
        "CREATE TRIGGER trg_pedidos_au_sync_cartera
         AFTER UPDATE ON pedidos
         FOR EACH ROW
         BEGIN
             CALL sp_sync_detalle_pedido_cartera(NEW.id);
         END",
        'CREATE TRIGGER pedidos AU'
    );

    echo 'OK: triggers_unificados=5' . PHP_EOL;
}

function migrationRunAllMinimal(PDO $pdo): void {
    migrationNormalizeStock($pdo);
    migrationEnsureDetalleColumns($pdo);
    migrationInstallCarteraSyncTriggers($pdo);
    migrationBackfillDetalleCartera($pdo);
    echo 'OK: migration_all_minimal completed' . PHP_EOL;
}
