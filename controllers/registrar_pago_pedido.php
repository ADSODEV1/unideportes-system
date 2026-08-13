<?php
// controllers/registrar_pago_pedido.php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/precios_helper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_login(['admin', 'colaborador', 'vendedor']);

$pdo = app();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Método no permitido.");

    $pedido_id  = intval($_POST['pedido_id'] ?? 0);
    $monto      = round(floatval($_POST['monto'] ?? 0), 2);
    $metodo     = trim($_POST['metodo_pago'] ?? 'Efectivo');
    $plataforma = trim($_POST['plataforma'] ?? '');
    $referencia = trim($_POST['referencia'] ?? '');

    if ($pedido_id <= 0) throw new Exception("Pedido inválido.");
    if ($monto <= 0)     throw new Exception("El monto debe ser mayor a cero.");
    if (!in_array($metodo, ['Efectivo','Tarjeta','Transferencia','Otro'], true)) $metodo = 'Efectivo';

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT total_pedido FROM pedidos WHERE id = ? FOR UPDATE");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pedido) throw new Exception("El pedido no existe.");

    // No permitir pagar de más
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE id_pg_pedido = ?");
    $stmt->execute([$pedido_id]);
    $abonado = floatval($stmt->fetchColumn());
    $saldo   = floatval($pedido['total_pedido']) - $abonado;
    if ($monto > $saldo + 0.009) {
        throw new Exception("El monto supera el saldo pendiente ($" . number_format($saldo, 0, ',', '.') . ").");
    }

    $stmt = $pdo->prepare("INSERT INTO pagos (id_pg_pedido, monto, metodo_pago, plataforma, referencia)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$pedido_id, $monto, $metodo,
        $plataforma !== '' ? $plataforma : null,
        $referencia !== '' ? $referencia : null]);

    sincronizar_saldo_pedido($pdo, $pedido_id); // ÚNICO punto que toca la caché (A)
    $pdo->commit();

    header("Location: ../views/ver_ticket_pedido.php?id=" . $pedido_id);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header("Location: ../views/mis_pedidos.php?status=error&msg=" . urlencode($e->getMessage()));
    exit;
}