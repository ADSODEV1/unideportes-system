<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

require_login(['vendedor', 'colaborador', 'admin']);
$conn = app();

$search = trim(request('search'));
$res_clientes = obtenerClientes($conn, $search);

$msg = '';
$error = '';
if ($msj = request('msj')) {
    $msg = $msj === 'cliente_creado' ? 'Nuevo cliente creado exitosamente.' : ($msj === 'cliente_eliminado' ? 'Cliente eliminado correctamente.' : ($msj === 'estado_actualizado' ? 'Estado del cliente actualizado.' : ''));
}
if ($err = request('error')) {
    $error = $err === 'cliente_tiene_pedidos' ? 'No se puede eliminar el cliente porque tiene pedidos asociados.' : ($err === 'id_invalido' ? 'ID de cliente inválido.' : ($err === 'estado_invalido' ? 'Estado inválido.' : ''));
}

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

        <form class="search-bar" method="GET" action="clientes.php">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar cliente por nombre o NIT...">
            <button type="submit" class="btn-principal">Buscar</button>
        </form>

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
                        <th>Estado</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($res_clientes) > 0): ?>
                        <?php foreach ($res_clientes as $cli): ?>
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
                                <td>
                                    <span class="badge badge-<?= ($cli['estado'] == 'activo' ? 'success' : 'danger') ?>">
                                        <?= ucfirst($cli['estado']) ?>
                                    </span>
                                </td>
                                <td><?= !empty($cli['created_at']) ? date("d/m/Y", strtotime($cli['created_at'])) : '-' ?></td>
                                <td>
                                    <a href="editar_cliente.php?id=<?= $cli['id'] ?>" class="btn-action btn-edit" title="Editar">✏️</a>
                                    <?php if ($cli['estado'] === 'activo'): ?>
                                        <a href="../controllers/cambiar_estado_cliente.php?id=<?= $cli['id'] ?>&estado=inactivo" class="btn-action btn-inactive" title="Desactivar">⏸️</a>
                                    <?php else: ?>
                                        <a href="../controllers/cambiar_estado_cliente.php?id=<?= $cli['id'] ?>&estado=activo" class="btn-action btn-active" title="Activar">▶️</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align:center; color: #888; padding: 30px;">
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

<?php include(__DIR__ . "/footer.php"); ?>