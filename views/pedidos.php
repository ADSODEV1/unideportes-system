<?php
// Minimal, readable version of pedidos.php
session_start();
require_once __DIR__ . '/../config/connection.php';

// simple auth
if (!isset($_SESSION['username'])) {
    header('Location: ../public/index.php');
    exit();
}

// get orders with client name
$sql = "SELECT p.id, p.detalle, p.cantidad, p.estado, p.fecha_entrega, c.nombre_completo
        FROM pedidos p
        JOIN clientes c ON p.cliente_id = c.id
        ORDER BY p.fecha_entrega ASC";
$res = mysqli_query($conn, $sql);

include __DIR__ . '/header.php';
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>
    <main class="main-content-panel">
        <h1>Órdenes de Producción</h1>

        <p>Listado simple de órdenes. Usa el botón "Ver" para detalles.</p>

        <table class="tabla-maestra">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Detalle</th>
                    <th>Cant.</th>
                    <th>Estado</th>
                    <th>Entrega</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($res)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nombre_completo']) ?></td>
                    <td><?= htmlspecialchars($row['detalle']) ?></td>
                    <td><?= (int)$row['cantidad'] ?></td>
                    <td><?= htmlspecialchars($row['estado']) ?></td>
                    <td><?= date('d-m-Y', strtotime($row['fecha_entrega'])) ?></td>
                    <td>
                        <details>
                            <summary>Ver</summary>
                            <div style="padding:8px">
                                <p><strong>ID:</strong> <?= (int)$row['id'] ?></p>
                                <p><strong>Cliente:</strong> <?= htmlspecialchars($row['nombre_completo']) ?></p>
                                <p><strong>Detalle:</strong> <?= htmlspecialchars($row['detalle']) ?></p>
                                <p><strong>Cantidad:</strong> <?= (int)$row['cantidad'] ?></p>
                                <p><strong>Estado:</strong> <?= htmlspecialchars($row['estado']) ?></p>
                                <p><strong>Entrega:</strong> <?= date('d-m-Y', strtotime($row['fecha_entrega'])) ?></p>
                            </div>
                        </details>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include __DIR__ . '/footer.php'; ?>