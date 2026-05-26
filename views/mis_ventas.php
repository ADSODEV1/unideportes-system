<?php
// 1. Inicializar entorno global y protección
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();
$pagina_actual = basename($_SERVER['PHP_SELF']);
$base = "/unideportes-system";

// Obtener el ID del vendedor desde la sesión activa
$vendedor_id = $_SESSION['user_id'] ?? 0; 

// 2. Consulta para las tarjetas de resumen del vendedor logueado
try {
    $sqlResumen = "SELECT 
                    COUNT(*) as total_ventas, 
                    IFNULL(SUM(total_venta), 0) as total_ingresos, 
                    IFNULL(AVG(total_venta), 0) as promedio_venta 
                   FROM ventas 
                   WHERE vendedor_id = :vendedor_id";
    $stmtResumen = $pdo->prepare($sqlResumen);
    $stmtResumen->execute(['vendedor_id' => $vendedor_id]);
    $resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $resumen = ['total_ventas' => 0, 'total_ingresos' => 0, 'promedio_venta' => 0];
}

// 3. Consulta para la tabla histórica de transacciones del vendedor
try {
    $sqlVentas = "SELECT v.*, c.nombre_completo as cliente_nombre 
                  FROM ventas v
                  INNER JOIN clientes c ON v.cliente_id = c.id
                  WHERE v.vendedor_id = :vendedor_id
                  ORDER BY v.fecha_venta DESC";
    $stmtVentas = $pdo->prepare($sqlVentas);
    $stmtVentas->execute(['vendedor_id' => $vendedor_id]);
    $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $ventas = [];
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1>Mis Ventas</h1>
                <p style="color: #64748b;">Historial completo de transacciones realizadas por ti.</p>
            </div>
            <a href="panel_vendedor.php" class="btn-secondary">Volver al Panel</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 4px solid #3b82f6;">
                <h3 style="margin: 0 0 5px 0; font-size: 0.9rem; color: #64748b;">Total Ventas</h3>
                <p style="font-size: 1.8rem; font-weight: bold; margin: 0;"><?= $resumen['total_ventas'] ?></p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 4px solid #10b981;">
                <h3 style="margin: 0 0 5px 0; font-size: 0.9rem; color: #64748b;">Total Ingresos</h3>
                <p style="font-size: 1.8rem; font-weight: bold; margin: 0;">$<?= number_format($resumen['total_ingresos'], 0, ',', '.') ?></p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 4px solid #f59e0b;">
                <h3 style="margin: 0 0 5px 0; font-size: 0.9rem; color: #64748b;">Promedio Venta</h3>
                <p style="font-size: 1.5rem; font-weight: bold; margin: 0;">$<?= number_format($resumen['promedio_venta'], 0, ',', '.') ?></p>
            </div>
        </div>

        <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f1f5f9; border-bottom: 2px solid #e2e8f0;">
                    <tr>
                        <th style="padding: 15px; text-align: left;">Ticket</th>
                        <th style="padding: 15px; text-align: left;">Fecha/Hora</th>
                        <th style="padding: 15px; text-align: left;">Cliente</th>
                        <th style="padding: 15px; text-align: left;">Método</th>
                        <th style="padding: 15px; text-align: right;">Total</th>
                        <th style="padding: 15px; text-align: center;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ventas)): ?>
                        <?php foreach ($ventas as $venta): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 15px; font-weight: 600; color: #1e293b;">
                                    <?= htmlspecialchars($venta['ticket_numero'] ?? 'S/N') ?>
                                </td>
                                <td style="padding: 15px; color: #64748b;">
                                    <?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])) ?>
                                </td>
                                <td style="padding: 15px;">
                                    <?= htmlspecialchars($venta['cliente_nombre']) ?>
                                </td>
                                <td style="padding: 15px;">
                                    <span style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;">
                                        <?= htmlspecialchars($venta['metodo_pago']) ?>
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: right; font-weight: bold; color: #1e293b;">
                                    $<?= number_format($venta['total_venta'], 0, ',', '.') ?>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <a href="ticket_actual.php?id=<?= $venta['id'] ?>" 
                                       style="color: #3b82f6; text-decoration: none; font-weight: 600; font-size: 0.9rem;">
                                       Ver Ticket
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center; color: #94a3b8;">
                                No has realizado ventas aún.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include(__DIR__ . "/footer.php"); ?>