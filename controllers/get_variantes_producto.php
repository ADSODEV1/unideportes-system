<?php
// controllers/get_variantes_producto.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../config/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');

// Constantes para evitar duplicación de literales (SonarCloud)
define('SIN_COLOR', 'Sin color');
define('SIN_TALLA', 'Sin talla');

// Verificar conexión a BD
try {
    $pdo = app();
    // Test de conexión
    $pdo->query('SELECT 1');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error de conexión a base de datos',
        'details' => $e->getMessage()
    ]);
    exit();
}

$pdo = app();

$nombre = trim($_GET['nombre'] ?? '');
$color = trim($_GET['color'] ?? '');
$talla = trim($_GET['talla'] ?? '');

if ($nombre === '') {
    http_response_code(400);
    echo json_encode(['error' => 'El nombre del producto es obligatorio.']);
    exit();
}

function normalizeVariantValue(string $value): string {
    return trim($value);
}

$color = normalizeVariantValue($color);
$talla = normalizeVariantValue($talla);

$response = [
    'colors' => [],
    'tallas' => [],
    'variant' => null,
];

try {
    if ($color === '') {
        $stmt = $pdo->prepare(
            "SELECT DISTINCT COALESCE(NULLIF(color, ''), '" . SIN_COLOR . "') AS color
             FROM productos
             WHERE nombre = ? AND stock > 0
             ORDER BY color ASC"
        );
        $stmt->execute([$nombre]);
        $response['colors'] = array_filter(array_map('trim', $stmt->fetchAll(PDO::FETCH_COLUMN)));

        if (empty($response['colors'])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE nombre = ? AND stock > 0");
            $stmt->execute([$nombre]);
            if ((int)$stmt->fetchColumn() > 0) {
                $response['colors'] = [SIN_COLOR];
            }
        }
    } elseif ($talla === '') {
        $colorQuery = $color === SIN_COLOR ? "(color IS NULL OR color = '')" : "color = ?";
        $params = $color === SIN_COLOR ? [$nombre] : [$nombre, $color];

        $stmt = $pdo->prepare(
            "SELECT DISTINCT COALESCE(NULLIF(talla, ''), '" . SIN_TALLA . "') AS talla
             FROM productos
             WHERE nombre = ? AND stock > 0 AND {$colorQuery}
             ORDER BY talla ASC"
        );
        $stmt->execute($params);
        $response['tallas'] = array_filter(array_map('trim', $stmt->fetchAll(PDO::FETCH_COLUMN)));

        if (empty($response['tallas'])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE nombre = ? AND stock > 0 AND {$colorQuery}");
            $stmt->execute($params);
            if ((int)$stmt->fetchColumn() > 0) {
                $response['tallas'] = [SIN_TALLA];
            }
        }
    } else {
        $colorCondition = $color === SIN_COLOR ? "(color IS NULL OR color = '')" : "color = ?";
        $tallaCondition = $talla === SIN_TALLA ? "(talla IS NULL OR talla = '')" : "talla = ?";

        $params = [$nombre];
        if ($color !== SIN_COLOR) {
            $params[] = $color;
        }
        if ($talla !== SIN_TALLA) {
            $params[] = $talla;
        }

        $query = "SELECT id, nombre, referencia, precio, stock, talla, color, categoria, descripcion
                  FROM productos
                  WHERE nombre = ? AND stock > 0 AND {$colorCondition} AND {$tallaCondition}
                  LIMIT 1";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fallback resiliente: si no existe coincidencia exacta de color/talla,
        // devuelve la primera variante disponible del producto para no bloquear la venta.
        if (!$variant) {
            $stmtFallback = $pdo->prepare(
                "SELECT id, nombre, referencia, precio, stock, talla, color, categoria, descripcion
                 FROM productos
                 WHERE nombre = ? AND stock > 0
                 ORDER BY id ASC
                 LIMIT 1"
            );
            $stmtFallback->execute([$nombre]);
            $variant = $stmtFallback->fetch(PDO::FETCH_ASSOC);
        }

        if ($variant) {
            $variant['color'] = $variant['color'] === null || trim($variant['color']) === '' ? SIN_COLOR : $variant['color'];
            $variant['talla'] = $variant['talla'] === null || trim($variant['talla']) === '' ? SIN_TALLA : $variant['talla'];
            $response['variant'] = $variant;
        }
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al consultar variantes de producto.', 'details' => $e->getMessage()]);
}