<?php
session_start();
require_once __DIR__ . '/../config/connection.php';

// 1. SEGURIDAD
if (!isset($_SESSION['username'])) {
    header("Location: ../public/index.php");
    exit();
}

// 2. CONSULTAS DE ESTADO (KPIs)
$c_corte   = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as t FROM pedidos WHERE estado = 'En Corte'"))['t'] ?? 0;
$c_costura = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as t FROM pedidos WHERE estado = 'En Costura'"))['t'] ?? 0;
$c_termino = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as t FROM pedidos WHERE estado = 'Terminado'"))['t'] ?? 0;

// 3. LISTADO CON INNER JOIN
$sql = "SELECT pedidos.*, clientes.nombre_completo 
        FROM pedidos 
        INNER JOIN clientes ON pedidos.cliente_id = clientes.id 
        ORDER BY pedidos.fecha_entrega ASC";

$query = mysqli_query($conn, $sql);

include(__DIR__ . "/header.php");
?>

<div class="pedidos-container">
    <div class="flex-header">
        <h1>🧵 Órdenes de Producción</h1>
        <?php if($_SESSION['role'] == 'admin'): ?>
            <a href="nuevo_pedido.php" class="btn-principal">+ Nueva Orden</a>
        <?php endif; ?>
    </div>

    <div class="resumen-taller">
        <div class="kpi-taller aviso">
            <small>EN CORTE</small>
            <span><?= str_pad($c_corte, 2, "0", STR_PAD_LEFT) ?></span>
        </div>
        <div class="kpi-taller proceso">
            <small>EN COSTURA</small>
            <span><?= str_pad($c_costura, 2, "0", STR_PAD_LEFT) ?></span>
        </div>
        <div class="kpi-taller listo">
            <small>TERMINADOS</small>
            <span><?= str_pad($c_termino, 2, "0", STR_PAD_LEFT) ?></span>
        </div>
    </div>

    <div class="tabla-scroll">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Cliente / ID</th>
                    <th>Detalle</th>
                    <th>Cant.</th>
                    <th>Estado</th>
                    <th>Entrega</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_array($query)): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($row['nombre_completo']) ?></strong><br>
                        <small>#ORD-<?= htmlspecialchars($row['id']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($row['detalle']) ?></td>
                    <td><?= htmlspecialchars($row['cantidad']) ?></td>
                    <td>
                        <?php 
                            $clase = "";
                            if($row['estado'] == 'En Corte') $clase = "badge-alerta";
                            if($row['estado'] == 'En Costura') $clase = "badge-proceso";
                            if($row['estado'] == 'Terminado') $clase = "badge-exito";
                        ?>
                        <span class="badge <?= $clase ?>"><?= htmlspecialchars($row['estado']) ?></span>
                    </td>
                    <td><strong><?= date("d M Y", strtotime($row['fecha_entrega'])) ?></strong></td>
                    <td><button type="button" class="toggle-detalle" data-target="#pedido-<?= $row['id'] ?>">Ver</button></td>
                </tr>
                <tr id="pedido-<?= $row['id'] ?>" class="detalle-row">
                    <td colspan="6">
                        <div class="detalle-content">
                            <p><strong>Cliente:</strong> <?= htmlspecialchars($row['nombre_completo']) ?></p>
                            <p><strong>Detalle del pedido:</strong> <?= htmlspecialchars($row['detalle']) ?></p>
                            <p><strong>Cantidad:</strong> <?= htmlspecialchars($row['cantidad']) ?></p>
                            <p><strong>Estado:</strong> <?= htmlspecialchars($row['estado']) ?></p>
                            <p><strong>Entrega:</strong> <?= date("d M Y", strtotime($row['fecha_entrega'])) ?></p>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toggle-detalle').forEach(function(button) {
            button.addEventListener('click', function() {
                var target = document.querySelector(this.dataset.target);
                if (!target) return;
                target.classList.toggle('detalle-abierto');
                this.textContent = target.classList.contains('detalle-abierto') ? 'Cerrar' : 'Ver';
            });
        });
    });
</script>

<?php include(__DIR__ . "/footer.php"); ?>