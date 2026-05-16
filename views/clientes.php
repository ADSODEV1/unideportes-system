<?php
session_start();
require_once __DIR__ . '/../config/connection.php';
$conn = connection();

// 1. SEGURIDAD
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'] ?? '', ['vendedor', 'colaborador', 'admin'], true)) {
    header("Location: /unideportes-system/public/index.php?error=acceso_denegado");
    exit();
}

// 2. OBTENER CLIENTES
$res_clientes = mysqli_query($conn, "SELECT id, nombre_completo, nit_cedula, telefono, email, tipo_cliente, created_at FROM clientes ORDER BY nombre_completo ASC");

// 3. INCLUIR HEADER
include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <!-- SIDEBAR (Igual que en panel_vendedor.php) -->
    <aside class="sidebar-panel">
        <div class="sidebar-section">
            <h3>Panel de Control</h3>
            <p>Bienvenido:<br><strong><?= $_SESSION['username']; ?></strong></p>
            <p style="font-size: 0.8rem; color: #666;">Rol: <?= ucfirst($_SESSION['role']); ?></p>
        </div>
        <nav class="sidebar-nav">
            <a href="panel_vendedor.php" class="nav-link">📊 Panel Principal</a>
            <a href="nueva_venta.php" class="nav-link">🛒 Nueva Venta</a>
            <a href="clientes.php" class="nav-link active">👥 Clientes</a>
            <a href="pedidos.php" class="nav-link">📦 Pedidos</a>
            <a href="productos.php" class="nav-link">🏷️ Productos</a>
            <a href="logout.php" class="nav-link logout">🚪 Cerrar Sesión</a>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content-panel">
        <div class="content-header">
            <h1>Gestión de Clientes</h1>
            <p>Visualice y administre la base de datos de clientes.</p>
        </div>

        <hr class="divider">

        <!-- Tabla de Clientes -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>NIT/Cédula</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res_clientes) > 0): ?>
                        <?php while($cli = mysqli_fetch_assoc($res_clientes)): ?>
                            <tr>
                                <td><?= $cli['id'] ?></td>
                                <td><strong><?= htmlspecialchars($cli['nombre_completo']) ?></strong></td>
                                <td><?= htmlspecialchars($cli['nit_cedula']) ?></td>
                                <td><?= htmlspecialchars($cli['telefono']) ?></td>
                                <td><?= htmlspecialchars($cli['email']) ?></td>
                                <td>
                                    <span class="badge badge-<?= ($cli['tipo_cliente'] == 'normal' ? 'info' : 'success') ?>">
                                        <?= ucfirst($cli['tipo_cliente']) ?>
                                    </span>
                                </td>
                                <td><?= date("d/m/Y", strtotime($cli['created_at'])) ?></td>
                                <td>
                                    <a href="editar_cliente.php?id=<?= $cli['id'] ?>" class="btn-action btn-edit" title="Editar">✏️</a>
                                    <a href="eliminar_cliente.php?id=<?= $cli['id'] ?>" class="btn-action btn-delete" title="Eliminar" onclick="return confirm('¿Seguro que desea eliminar este cliente?')">🗑️</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; color: #888; padding: 30px;">
                                No hay clientes registrados en el sistema.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="action-footer">
            <a href="nuevo_cliente.php" class="btn-primary">+ Nuevo Cliente</a>
        </div>

    </main>
</div>

<footer class="main-footer">
    <p>&copy; <?= date("Y"); ?> Unideportes - Sistema de Gestión</p>
</footer>

<style>
/* Estilos Base heredados del Panel */
.container.admin-layout {
    display: flex;
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Sidebar */
.sidebar-panel {
    width: 250px;
    background: #1A2B4C; /* Color marca */
    color: #fff;
    padding: 20px;
    display: flex;
    flex-direction: column;
    position: fixed;
    height: 100vh;
    left: 0;
    top: 0;
    z-index: 1000;
}

.sidebar-section h3 {
    color: #E61E2A; /* Acento marca */
    margin-bottom: 10px;
    font-size: 1.2rem;
}

.sidebar-nav {
    margin-top: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.nav-link {
    text-decoration: none;
    color: #e0e0e0;
    padding: 12px 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.nav-link:hover, .nav-link.active {
    background: #E61E2A;
    color: #fff;
    transform: translateX(5px);
}

.nav-link.logout {
    margin-top: auto;
    background: transparent;
}

/* Contenido Principal */
.main-content-panel {
    margin-left: 250px;
    flex: 1;
    padding: 30px;
    background: #f4f6f9;
    width: calc(100% - 250px);
}

.content-header h1 {
    color: #1A2B4C;
    margin-bottom: 5px;
}

.content-header p {
    color: #666;
    margin-bottom: 20px;
}

.divider {
    border: 0;
    border-top: 1px solid #ddd;
    margin: 20px 0;
}

/* Tabla Responsiva */
.table-responsive {
    overflow-x: auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px; /* Ancho mínimo para scroll en móvil */
}

.data-table th, .data-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.data-table th {
    background: #1A2B4C;
    color: #fff;
    font-weight: 600;
    white-space: nowrap;
}

.data-table tr:hover {
    background-color: #f9f9f9;
}

/* Badges y Botones */
.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: bold;
    color: #fff;
}
.badge-info { background: #3498db; }
.badge-success { background: #27ae60; }

.btn-action {
    text-decoration: none;
    margin-right: 5px;
    font-size: 1.1rem;
}
.btn-edit { color: #3498db; }
.btn-delete { color: #e74c3c; }

.btn-primary {
    display: inline-block;
    background: #E61E2A;
    color: #fff;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    margin-top: 20px;
}
.btn-primary:hover {
    background: #c91a25;
}

.action-footer {
    margin-top: 20px;
    text-align: right;
}

/* FOOTER */
.main-footer {
    text-align: center;
    padding: 20px;
    color: #888;
    font-size: 0.9rem;
    margin-left: 250px; /* Ajuste para no tapar el sidebar */
}

/* RESPONSIVE (Móviles y Tablets) */
@media (max-width: 768px) {
    .sidebar-panel {
        transform: translateX(-100%); /* Ocultar sidebar */
        transition: transform 0.3s ease;
    }
    
    .main-content-panel, .main-footer {
        margin-left: 0;
        width: 100%;
    }

    /* Botón para mostrar menú en móvil (opcional, requiere JS) */
    .mobile-menu-toggle {
        display: block !important;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1100;
        background: #1A2B4C;
        color: #fff;
        padding: 10px;
        border-radius: 5px;
        border: none;
        font-size: 1.2rem;
    }

    /* Mover el header hacia abajo para dar espacio al botón */
    .main-content-panel {
        padding-top: 60px;
    }
}

@media (min-width: 769px) {
    .mobile-menu-toggle {
        display: none;
    }
}
</style>

<!-- Script simple para menú móvil -->
<script>
    // Si quieres agregar un botón de hamburguesa, descomenta esto:
    /*
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.createElement('button');
        toggle.className = 'mobile-menu-toggle';
        toggle.innerHTML = '☰';
        toggle.onclick = function() {
            const sidebar = document.querySelector('.sidebar-panel');
            sidebar.style.transform = sidebar.style.transform === 'translateX(0)' ? 'translateX(-100%)' : 'translateX(0)';
        };
        document.body.appendChild(toggle);
    });
    */
</script>

<?php include(__DIR__ . "/footer.php"); ?>