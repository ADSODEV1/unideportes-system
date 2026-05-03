<?php
// 1. Iniciar sesión
session_start();

// 2. Conexión a la base de datos
require_once __DIR__ . '/../config/connection.php';
$conn = connection();

// 3. Seguridad: Solo admin puede entrar
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: /unideportes-system/public/index.php?error=acceso_denegado");
    exit();
}

// 4. Consultas del html dinámico

// Colaboradores
$res_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM usuarios WHERE role = 'colaborador'");
$total_colab = mysqli_fetch_array($res_users)['total'] ?? 0;

// 5. Header
include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <!-- Sidebar izquierdo -->
   <aside class="sidebar-panel">

    <div class="sidebar-section">
        <h3> Administrador</h3>
        <p>Bienvenido:<br><strong><?= $_SESSION['username']; ?></strong></p>
    </div>

    <div class="sidebar-section">
        <h3> Resumen</h3>
        <div class="stat-box">
            Colaboradores:<br>
            <strong><?= $total_colab; ?></strong>
        </div>
    </div>

   

</aside>

    <!-- Contenido Principal -->
    <main class="main-content-panel">

        <h1> Panel Administrador - Unideportes</h1>

        <div class="resumen-kpi">
            <div class="kpi-card">
                <small>COLABORADORES</small>
                <h2><?= $total_colab; ?> Activos</h2>
            </div>
        </div>

        <hr>

        <div class="menu-maestro">
            <div class="opcion">
                <a href="nueva_venta.php">
                    <span></span>
                    <h3>Realizar Venta</h3>
                </a>
            </div>

            <div class="opcion">
                <a href="admin_user.php">
                    <span></span>
                    <h3>Gestionar Personal</h3>
                </a>
            </div>

            <div class="opcion">
                <a href="inventario.php">
                    <span></span>
                    <h3>Control de Inventario</h3>
                </a>
            </div>

            <div class="opcion">
                <a href="clientes.php">
                    <span></span>
                    <h3>Base de Clientes</h3>
                </a>
            </div>

            <div class="opcion">
                <a href="reportes_ventas.php">
                    <span></span>
                    <h3>Reportes de Ventas</h3>
                </a>
            </div>
        </div>

    </main>

</div>

<footer class="main-footer">
    <p>&copy; <?= date("Y"); ?> Unideportes - Sistema de Gestión Interno</p>
</footer>