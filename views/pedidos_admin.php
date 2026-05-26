<?php
// views/pedidos_admin.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Restricción estricta: Solo producción y administración
require_login(['admin', 'colaborador']);

$pdo = app();
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;

// PROCESAR CAMBIO DE ESTADO DESDE LA FÁBRICA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $pedido_id = intval($_POST['pedido_id']);
    $nuevo_estado = $_POST['estado_fabrica'];

    // Validación de seguridad para que producción no salte a 'Entregado' sin cobrar en tienda
    $estados_validos = ['En Corte', 'En Costura', 'Terminado'];
    
    if (in_array($nuevo_estado, $estados_validos)) {
        try {
            $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $pedido_id]);
            header("Location: pedidos_admin.php?success=estado_actualizado");
            exit();
        } catch (Exception $e) {
            header("Location: pedidos_admin.php?error=db_error");
            exit();
        }
    } else {
        header("Location: pedidos_admin.php?error=estado_invalido");
        exit();
    }
}

// Consultar todas las órdenes activas en el taller (Excluyendo las ya entregadas en tienda)
$stmt = $pdo->query("SELECT p.*, c.nombre_completo, c.nit_cedula 
                     FROM pedidos p 
                     INNER JOIN clientes c ON p.cliente_id = c.id 
                     WHERE p.estado != 'Entregado' 
                     ORDER BY p.fecha_entrega ASC");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        <div class="page-header" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 style="color: #1e293b; font-size: 1.8rem; font-weight: 700;">🏭 Control de Producción Taller</h1>
                <p style="color: #64748b; margin-top: 5px;">Mapeo de órdenes mayoristas en confección. Cambia el estado para que se refleje en el punto de venta.</p>
            </div>
            <a href="nuevo_pedido.php" class="btn-primary" style="background: #1e293b; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                ➕ Crear Orden Mayorista
            </a>
        </div>

        <?php if ($success === 'estado_actualizado'): ?>
            <div style="padding: 12px; background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; border-radius: 6px; margin-bottom: 20px; font-weight: 600; font-size: 0.9rem;">
                🔄 Estado del pedido sincronizado con éxito.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="tabla-maestra" style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                        <th style="padding: 12px;">Cliente</th>
                        <th style="padding: 12px;">Detalles de Confección</th>
                        <th style="padding: 12px; text-align: center;">Cantidad</th>
                        <th style="padding: 12px;">Fecha Límite</th>
                        <th style="padding: 12px; text-align: center;">Fase de Fábrica</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pedidos) > 0): ?>
                        <?php foreach ($pedidos as $row): ?>
                            <tr style="border-bottom: 1px solid #edf2f7;">
                                <td style="padding: 12px;">
                                    <strong style="color: #1e293b; display: block;"><?= htmlspecialchars($row['nombre_completo']) ?></strong>
                                    <span style="font-size: 0.8rem; color: #64748b;">NIT: <?= htmlspecialchars($row['nit_cedula']) ?></span>
                                </td>
                                <td style="padding: 12px;">
                                    <span style="font-weight: 600; color: #475569;"><?= htmlspecialchars($row['detalle']) ?></span>
                                    <p style="margin: 3px 0 0 0; font-size: 0.85rem; color: #64748b;"><em><?= htmlspecialchars($row['descripcion']) ?></em></p>
                                </td>
                                <td style="padding: 12px; text-align: center; font-weight: 700; color: #1e293b;"><?= (int)$row['cantidad'] ?></td>
                                <td style="padding: 12px; color: #dc2626; font-weight: 600; font-size: 0.9rem;">
                                    📅 <?= date('d-m-Y', strtotime($row['fecha_entrega'])) ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <form method="POST" action="">
                                        <input type="hidden" name="pedido_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="actualizar_estado" value="1">
                                        <select name="estado_fabrica" onchange="this.form.submit()" style="padding: 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-weight: 600; color: #334155; background: #f8fafc; cursor: pointer;">
                                            <option value="En Corte" <?= $row['estado'] === 'En Corte' ? 'selected' : '' ?>>✂️ En Corte</option>
                                            <option value="En Costura" <?= $row['estado'] === 'En Costura' ? 'selected' : '' ?>>🪡 En Costura</option>
                                            <option value="Terminado" <?= $row['estado'] === 'Terminado' ? 'selected' : '' ?>>✅ Terminado (Listo)</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 25px; color: #64748b;">No hay prendas en la línea de producción actualmente.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include(__DIR__ . "/footer.php"); ?>