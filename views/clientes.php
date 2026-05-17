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
$res_clientes = mysqli_query($conn, "SELECT id, nombre_completo, nit_cedula, telefono, email, tipo_cliente FROM clientes ORDER BY nombre_completo ASC");

$msg = '';
$error = '';
if (!empty($_GET['msj'])) {
    if ($_GET['msj'] === 'cliente_creado') {
        $msg = 'Nuevo cliente creado exitosamente.';
    } elseif ($_GET['msj'] === 'cliente_eliminado') {
        $msg = 'Cliente eliminado correctamente.';
    }
}
if (!empty($_GET['error'])) {
    if ($_GET['error'] === 'cliente_tiene_pedidos') {
        $error = 'No se puede eliminar el cliente porque tiene pedidos asociados.';
    } elseif ($_GET['error'] === 'id_invalido') {
        $error = 'ID de cliente inválido.';
    }
}

// 3. INCLUIR HEADER
include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content-panel">
        <div class="content-header">
            <h1>Gestión de Clientes</h1>
            <p>Visualice y administre la base de datos de clientes.</p>
        </div>

        <hr class="divider">

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

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
                                <td><?= !empty($cli['created_at']) ? date("d/m/Y", strtotime($cli['created_at'])) : '-' ?></td>
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

<?php include(__DIR__ . "/footer.php"); ?>