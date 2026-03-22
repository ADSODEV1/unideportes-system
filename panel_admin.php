<?php
// 1. INICIAR SESIÓN (SIEMPRE PRIMERO)
session_start();

// 2. CONEXIÓN
include("connection.php");

// 3. SEGURIDAD
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php?error=acceso_denegado");
    exit();
}

// 4. CONSULTAS (ANTES DEL HTML)

// Colaboradores
$res_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM usuarios WHERE role = 'colaborador'");
$total_colab = mysqli_fetch_array($res_users)['total'] ?? 0;

// Ingresos
$res_ventas = mysqli_query($conn, "SELECT SUM(total_venta) as ingresos FROM ventas"); 
$ingresos_data = mysqli_fetch_array($res_ventas);
$total_ingresos = $ingresos_data['ingresos'] ?? 0;

// 5. HEADER
include("header.php");
?>

<div class="container admin-layout">

    <!-- SIDEBAR IZQUIERDO -->
   <aside class="sidebar-panel">

    <div class="sidebar-section">
        <h3>👑 Administrador</h3>
        <p>Bienvenido:<br><strong><?= $_SESSION['username']; ?></strong></p>
    </div>

    <div class="sidebar-section">
        <h3>📊 Resumen</h3>
        <div class="stat-box">
            Ingresos:<br>
            <strong>$<?= number_format($total_ingresos, 0, ',', '.'); ?></strong>
        </div>
        <div class="stat-box">
            Colaboradores:<br>
            <strong><?= $total_colab; ?></strong>
        </div>
    </div>

   

</aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content-panel">

        <h1>Panel Administrador - Unideportes</h1>

        <div class="resumen-kpi">
            <div class="kpi-card">
                <small>INGRESOS TOTALES</small>
                <h2>$<?= number_format($total_ingresos, 0, ',', '.'); ?></h2>
            </div>
            
            <div class="kpi-card">
                <small>COLABORADORES</small>
                <h2><?= $total_colab; ?> Activos</h2>
            </div>
        </div>

        <hr>

        <div class="menu-maestro">
            <div class="opcion">
                <a href="admin_user.php">
                    <span>🧑</span>
                    <h3>Gestionar Personal</h3>
                </a>
            </div>

            <div class="opcion">
                <a href="inventario.php">
                    <span>🧰</span>
                    <h3>Control de Inventario</h3>
                </a>
            </div>

            <div class="opcion">
                <a href="clientes.php">
                    <span>📓</span>
                    <h3>Base de Clientes</h3>
                </a>
            </div>

            <div class="opcion">
                <a href="reportes.php">
                    <span>📶</span>
                    <h3>Reportes de Ventas</h3>
                </a>
            </div>
        </div>

    </main>

</div>

<footer class="main-footer">
    <p>&copy; <?= date("Y"); ?> Unideportes - Sistema de Gestión Interno</p>
</footer>