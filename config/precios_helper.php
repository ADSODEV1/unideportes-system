<?php
// config/precios_helper.php
// Fuente única de verdad de la línea de confección a medida.

if (!defined('CONFECCION_CANTIDAD_MINIMA')) {
    define('CONFECCION_CANTIDAD_MINIMA', 35); // mínimo de unidades por pedido (suma de líneas)
}

/** Tipos de prenda activos con su precio base. */
function precios_base_activos(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, tipo_prenda, precio_base, descripcion
                         FROM precios_base_confeccion
                         WHERE activo = 1
                         ORDER BY tipo_prenda ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Prenda activa por id (si no existe o está inactiva, error). */
function precio_base_por_id(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare("SELECT id, tipo_prenda, precio_base
                           FROM precios_base_confeccion
                           WHERE id = ? AND activo = 1");
    $stmt->execute([$id]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$fila) {
        throw new Exception("Tipo de prenda inexistente o inactivo.");
    }
    return $fila;
}

/**
* Valida y cotiza las líneas que llegan del formulario.
* El navegador NUNCA envía precios ni totales: todo se calcula aquí.
*/
function construir_lineas_pedido(PDO $pdo, array $post): array
{
    $lineas = [];
    foreach (($post['tipo_prenda_id'] ?? []) as $i => $id) {
        $id       = intval($id);
        $cantidad = intval($post['cantidad_linea'][$i] ?? 0);
        
        if ($id <= 0 || $cantidad <= 0) continue; // fila vacía del formulario

        // 🔴 NUEVA REGLA: Mínimo 35 unidades POR CADA TIPO DE PRENDA
        if ($cantidad < CONFECCION_CANTIDAD_MINIMA) {
            $stmt = $pdo->prepare("SELECT tipo_prenda FROM precios_base_confeccion WHERE id = ?");
            $stmt->execute([$id]);
            $nombrePrenda = $stmt->fetchColumn() ?: 'la prenda seleccionada';
            throw new Exception("Cada tipo de prenda debe tener un mínimo de " . CONFECCION_CANTIDAD_MINIMA . " unidades. Revisa la cantidad de: " . $nombrePrenda);
        }

        $prenda = precio_base_por_id($pdo, $id);
        $precio = floatval($prenda['precio_base']); // foto del precio de hoy
        
        $lineas[] = [
            'tipo_prenda_id'  => $id,
            'tipo_prenda'     => $prenda['tipo_prenda'],
            'cantidad'        => $cantidad,
            'precio_unitario' => $precio,
            'subtotal'        => round($precio * $cantidad, 2),
            'color'           => mb_substr(trim($post['color_linea'][$i] ?? ''), 0, 50),
            'talla'           => mb_substr(trim($post['talla_linea'][$i] ?? ''), 0, 10),
            'comentario'      => mb_substr(trim($post['comentario_linea'][$i] ?? ''), 0, 500),
        ];
    }
    
    if (empty($lineas)) {
        throw new Exception("Agrega al menos una línea con tipo de prenda y cantidad.");
    }
    
    // Ya no necesitamos validar la suma total, porque cada línea ya cumple el mínimo.
    return $lineas;
}

/** Total del pedido = suma de subtotales (calculado en servidor). */
function total_de_lineas(array $lineas): float
{
    return round(array_sum(array_column($lineas, 'subtotal')), 2);
}

/** Título automático para pedidos.detalle (B confirmado). */
function resumen_de_lineas(array $lineas): string
{
    $partes = [];
    foreach ($lineas as $l) {
        $partes[] = $l['cantidad'] . '× ' . $l['tipo_prenda'];
    }
    return 'Confección: ' . implode(', ', $partes);
}

/**
 * ÚNICO lugar que escribe pedidos.abono / saldo_pendiente después de crear el pedido.
 * pagos sigue siendo la fuente de verdad; esto es solo caché sincronizada (A confirmado).
 */
function sincronizar_saldo_pedido(PDO $pdo, int $pedido_id): void
{
    $stmt = $pdo->prepare("UPDATE pedidos
                           SET abono = (SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE id_pg_pedido = pedidos.id),
                               saldo_pendiente = total_pedido - (SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE id_pg_pedido = pedidos.id)
                           WHERE id = ?");
    $stmt->execute([$pedido_id]);
}
if (!defined('CONFECCION_ABONO_MINIMO_PORCENTAJE')) {
    define('CONFECCION_ABONO_MINIMO_PORCENTAJE', 50); // % del total exigido como abono inicial
}

/** Monto mínimo de abono para un total dado. */
function abono_minimo_de(float $total): float
{
    return round($total * CONFECCION_ABONO_MINIMO_PORCENTAJE / 100, 2);
}