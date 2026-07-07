<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/migration_runtime.php';

$pdo = app();

try {
    migrationRunAllMinimal($pdo);
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
