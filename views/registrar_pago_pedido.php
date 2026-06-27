<?php
// controllers/registrar_pago_pedido.php
// Registra un abono adicional para un pedido de fábrica
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin', 'colaborador', 'vendedor']);

$pdo = app();

// Solo acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /unideportes-system/views/mis_pedidos.php");
    exit();
}

// 1. Capturar y validar datos
$pedido_id = intval($_POST['pedido_id'] ?? 0);
$monto = floatval($_POST['monto'] ?? 0);
$metodo_pago = trim($_POST['metodo_pago'] ?? 'Efectivo');

// Validaciones básicas
if ($pedido_id <= 0) {
    header("Location: /unideportes-system/views/mis_pedidos.php?status=error_pedido");
    exit();
}

if ($monto <= 0) {
    header("Location: /unideportes-system/views/mis_pedidos.php?status=error_monto");
    exit();
}

// Validar método de pago
$metodosValidos = ['Efectivo', 'Tarjeta', 'Transferencia'];
if (!in_array($metodo_pago, $metodosValidos)) {
    header("Location: /unideportes-system/views/mis_pedidos.php?status=error_metodo");
    exit();
}

try {
    // 2. Verificar que el pedido existe y calcular saldo actual
    $stmt = $pdo->prepare("
        SELECT p.total_pedido, 
               p.abono AS abono_inicial,
               IFNULL((SELECT SUM(pa.monto) FROM pagos pa WHERE pa.id_pg_pedido = p.id), 0) AS pagos_adicionales
        FROM pedidos p
        WHERE p.id = ? AND p.estado != 'Entregado'
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        header("Location: /unideportes-system/views/mis_pedidos.php?status=pedido_no_existe");
        exit();
    }

    // 3. Calcular saldo pendiente
    $total_abonado = floatval($pedido['abono_inicial']) + floatval($pedido['pagos_adicionales']);
    $saldo_pendiente = floatval($pedido['total_pedido']) - $total_abonado;

    // Validar que el monto no exceda el saldo pendiente
    if ($monto > $saldo_pendiente) {
        header("Location: /unideportes-system/views/mis_pedidos.php?status=monto_excede");
        exit();
    }

    // 4. Registrar el pago usando transacción
    $pdo->beginTransaction();

    // Insertar el pago
    $stmt = $pdo->prepare("
        INSERT INTO pagos (id_pg_pedido, monto, metodo_pago, fecha) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$pedido_id, $monto, $metodo_pago]);

    $pdo->commit();

    // 5. Redirigir con mensaje de éxito
    header("Location: /unideportes-system/views/mis_pedidos.php?status=pago_registrado");
    exit();

} catch (Exception $e) {
    // Si hay error, hacer rollback
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log del error (opcional, para debugging)
    error_log("Error registrando pago pedido #{$pedido_id}: " . $e->getMessage());
    
    header("Location: /unideportes-system/views/mis_pedidos.php?status=error");
    exit();
}