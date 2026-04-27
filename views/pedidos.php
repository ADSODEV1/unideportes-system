<?php
session_start();
include("connection.php");

// 1. SEGURIDAD
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
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

include("header.php");
?>

<div class="pedidos-container">
    <div class="flex-header">
        <h1>🧵 Ordenes de Producción</h1>
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
                    <th>Ver</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_array($query)): ?>
                <tr>
                    <td>
                        <strong><?= $row['nombre_completo'] ?></strong><br>
                        <small>#ORD-<?= $row['id'] ?></small>
                    </td>
                    <td><?= $row['detalle'] ?></td>
                    <td><?= $row['cantidad'] ?></td>
                    <td>
                        <?php 
                            $clase = "";
                            if($row['estado'] == 'En Corte') $clase = "badge-alerta";
                            if($row['estado'] == 'En Costura') $clase = "badge-proceso";
                            if($row['estado'] == 'Terminado') $clase = "badge-exito";
                        ?>
                        <span class="badge <?= $clase ?>"><?= $row['estado'] ?></span>
                    </td>
                    <td><strong><?= date("d M Y", strtotime($row['fecha_entrega'])) ?></strong></td>
                    <td><a href="detalle_pedido.php?id=<?= $row['id'] ?>">📋</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include("footer.php"); ?>