<?php
// 1. SESIÓN
session_start();

// 2. CONEXIÓN
include("connection.php");

// 3. SEGURIDAD (solo vendedores)
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'vendedor') {
    header("Location: index.php?error=acceso_denegado");
    exit();
}

// 4. HEADER
include("header.php");
?>

<div class="container admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar-panel">

        <div class="sidebar-section">
            <h3>👤 Vendedor</h3>
            <p>Bienvenido:<br><strong><?= $_SESSION['username']; ?></strong></p>
        </div>

        <div class="sidebar-section">
            <h3>⚙️ Acciones</h3>
            <a href="inventario.php" class="btn-sidebar-action">📦 Inventario</a>
            <a href="clientes.php" class="btn-sidebar-action">🏆 Clientes</a>
            <a href="pedidos.php" class="btn-sidebar-action">🛒 Producción</a>
        </div>

    </aside>

    <!-- CONTENIDO -->
    <main class="main-content-panel">

        <h1>Panel de Vendedor</h1>
        <p>Gestiona pedidos, clientes e inventario.</p>

        <hr>

        <div class="menu-maestro">

            <div class="opcion">
                <a href="inventario.php">
                    <span>📦</span>
                    <h3>Inventario</h3>
                </a>
            </div>

            <div class="opcion">
                <a href="clientes.php">
                    <span>🏆</span>
                    <h3>Clientes</h3>
                </a>
            </div>

            <div class="opcion">
                <a href="pedidos.php">
                    <span>🛒</span>
                    <h3>Pedidos</h3>
                </a>
            </div>

        </div>

    </main>

</div>

<footer class="main-footer">
    <p>&copy; <?= date("Y"); ?> Unideportes - Sistema de Gestión Interno</p>
</footer>