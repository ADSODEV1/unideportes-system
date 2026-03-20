<?php
session_start();
include("connection.php");

// 1. EL GRAN GUARDIÁN: Seguridad de Doble Llave
// No solo debe estar logueado, sino que su ROL debe ser 'admin'
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php?error=acceso_denegado");
    exit();
}

// 2. LÓGICA DE NEGOCIO: Los números del jefe (KPIs)

// Contar colaboradores (usuarios que no son admin)
$res_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM usuarios WHERE role = 'colaborador'");
$total_colab = mysqli_fetch_array($res_users)['total'];

// Sumar todos los ingresos de la tabla ventas
$res_ventas = mysqli_query($conn, "SELECT SUM(total_venta) as ingresos FROM ventas"); 
$ingresos_data = mysqli_fetch_array($res_ventas);
$total_ingresos = $ingresos_data['ingresos'] ?? 0;

include("header.php");
?>

<div class="panel-admin-container">
    <h1>Panel Maestro - Unideportes</h1>
    <p>Bienvenido Administrador: <strong><?php echo $_SESSION['username']; ?></strong></p>

    <div class="resumen-kpi">
        <div class="kpi-card">
            <small>INGRESOS TOTALES</small>
            <h2>$<?php echo number_format($total_ingresos, 0, ',', '.'); ?></h2>
        </div>
        
        <div class="kpi-card">
            <small>COLABORADORES</small>
            <h2><?php echo $total_colab; ?> Activos</h2>
        </div>
    </div>

    <hr>

    <div class="menu-maestro">
        <div class="opcion">
            <a href="admin_user.php">
                <span>👥</span>
                <h3>Gestionar Personal</h3>
            </a>
        </div>

        <div class="opcion">
            <a href="inventario.php">
                <span>📦</span>
                <h3>Control de Inventario</h3>
            </a>
        </div>

        <div class="opcion">
            <a href="clientes.php">
                <span>🏆</span>
                <h3>Base de Clientes</h3>
            </a>
        </div>

        <div class="opcion">
            <a href="reportes.php">
                <span>📊</span>
                <h3>Reportes de Ventas</h3>
            </a>
        </div>
    </div>

    <br>
    <a href="logout.php">Cerrar Sesión Segura</a>
</div>

<?php include("footer.php"); ?>