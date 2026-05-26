<?php
// 1. Inicialización del entorno global y control de acceso
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

require_login(['vendedor', 'colaborador', 'admin']);
$conn = app();

// 2. Captura y limpieza del criterio de búsqueda
$search = trim(request('search'));

// NUEVA ESTRATEGIA: Solo se consulta la base de datos si el usuario ingresó un texto
$res_clientes = [];
if (!empty($search)) {
    $res_clientes = obtenerClientes($conn, $search);
}

// 3. Procesamiento de mensajes de retroalimentación (Éxito / Errores)
$msg = '';
$error = '';
if ($msj = request('msj')) {
    $msg = $msj === 'cliente_creado' ? 'Nuevo cliente creado exitosamente.' : 
          ($msj === 'cliente_eliminado' ? 'Cliente eliminado correctamente.' : 
          ($msj === 'estado_actualizado' ? 'Estado del cliente actualizado.' : ''));
}
if ($err = request('error')) {
    $error = $err === 'cliente_tiene_pedidos' ? 'No se puede eliminar el cliente porque tiene pedidos asociados.' : 
            ($err === 'id_invalido' ? 'ID de cliente inválido.' : 
            ($err === 'estado_invalido' ? 'Estado inválido.' : ''));
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        
        <div class="content-header">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 15px;">
                <div>
                    <h1>Gestión de Clientes</h1>
                    <p style="color: #64748b; margin: 5px 0 0 0;">Busca de forma rápida la información de un cliente para consultar o editar sus datos.</p>
                </div>
                <a href="nuevo_cliente.php" class="btn-primary" style="text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600;">
                    + Nuevo Cliente
                </a>
            </div>
        </div>

        <form class="search-bar" method="GET" action="clientes.php" style="margin-top: 25px; display: flex; gap: 10px;">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Escribe el nombre, NIT o Cédula del cliente..." style="flex: 1; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem;" required>
            <button type="submit" class="btn-principal" style="padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600;">Buscar</button>
        </form>

        <hr class="divider" style="margin: 25px 0; border: 0; border-top: 1px solid #e2e8f0;">

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px; padding: 12px; background: #d1fae5; color: #065f46; border-radius: 6px; border-left: 4px solid #10b981;"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error" style="margin-bottom: 20px; padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 6px; border-left: 4px solid #ef4444;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($search)): ?>
            
            <div style="text-align: center; padding: 60px 20px; color: #64748b; background: white; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #e2e8f0;">
                <span style="font-size: 3.5rem; display: block; margin-bottom: 15px;">🔍</span>
                <h2 style="color: #1e293b; margin-bottom: 8px; font-size: 1.4rem;">Panel de Búsqueda de Clientes</h2>
                <p style="max-width: 500px; margin: 0 auto; line-height: 1.5; color: #64748b;">
                    Introduce el criterio del cliente en la barra superior para procesar una búsqueda en el sistema.
                </p>
            </div>

        <?php else: ?>
            
            <div class="table-responsive" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); overflow-x: auto; border: 1px solid #e2e8f0;">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                    <thead style="background: #f1f5f9; border-bottom: 2px solid #e2e8f0;">
                        <tr>
                            <th style="padding: 14px; text-align: left; color: #475569;">Código</th>
                            <th style="padding: 14px; text-align: left; color: #475569;">Nombre Completo</th>
                            <th style="padding: 14px; text-align: left; color: #475569;">NIT / Cédula</th>
                            <th style="padding: 14px; text-align: left; color: #475569;">Teléfono</th>
                            <th style="padding: 14px; text-align: left; color: #475569;">Ciudad / Barrio</th>
                            <th style="padding: 14px; text-align: left; color: #475569;">Tipo</th>
                            <th style="padding: 14px; text-align: left; color: #475569;">Estado</th>
                            <th style="padding: 14px; text-align: center; color: #475569;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($res_clientes) > 0): ?>
                            <?php foreach ($res_clientes as $cli): ?>
                                <tr style="border-bottom: 1px solid #e2e8f0; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 14px; font-weight: 600; color: #475569;">
                                        <?= htmlspecialchars($cli['codigo_descriptivo'] ?? 'S/C') ?>
                                    </td>
                                    <td style="padding: 14px; color: #1e293b;"><strong><?= htmlspecialchars($cli['nombre_completo']) ?></strong></td>
                                    <td style="padding: 14px; color: #334155;"><?= htmlspecialchars($cli['nit_cedula']) ?></td>
                                    <td style="padding: 14px; color: #64748b;"><?= htmlspecialchars($cli['telefono'] ?? 'Sin registrar') ?></td>
                                    <td style="padding: 14px; font-size: 0.9rem; color: #475569;">
                                        <?= htmlspecialchars($cli['ciudad'] ?? 'Sogamoso') ?><?= !empty($cli['barrio']) ? ' - ' . htmlspecialchars($cli['barrio']) : '' ?>
                                    </td>
                                    <td style="padding: 14px;">
                                        <span class="badge badge-<?= ($cli['tipo_cliente'] === 'Individual' ? 'info' : 'success') ?>" style="padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">
                                            <?= htmlspecialchars($cli['tipo_cliente']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px;">
                                        <?php if (isset($cli['estado'])): ?>
                                            <span class="badge badge-<?= ($cli['estado'] === 'activo' ? 'success' : 'danger') ?>" style="padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">
                                                <?= ucfirst($cli['estado']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-muted" style="background: #e2e8f0; color: #64748b; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 500;">Activo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px; text-align: center;">
                                        <a href="editar_cliente.php?id=<?= $cli['id'] ?>" class="btn-action btn-edit" title="Editar" style="text-decoration: none; font-size: 1.1rem; margin-right: 8px;">✏️</a>
                                        
                                        <?php if (isset($cli['estado'])): ?>
                                            <?php if ($cli['estado'] === 'activo'): ?>
                                                <a href="../controllers/clientes.php?action=toggle_status&id=<?= $cli['id'] ?>&estado=inactivo&search=<?= urlencode($search) ?>" class="btn-action btn-inactive" title="Desactivar" style="text-decoration: none; font-size: 1.1rem;">⏸️</a>
                                            <?php else: ?>
                                                <a href="../controllers/clientes.php?action=toggle_status&id=<?= $cli['id'] ?>&estado=activo&search=<?= urlencode($search) ?>" class="btn-action btn-active" title="Activar" style="text-decoration: none; font-size: 1.1rem;">▶️</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #94a3b8; padding: 40px;">
                                    <span style="font-size: 2rem; display: block; margin-bottom: 10px;">⚠️</span>
                                    No se encontraron clientes que coincidan con la búsqueda: <strong>"<?= htmlspecialchars($search) ?>"</strong>.
                                    <br>
                                    <a href="nuevo_cliente.php" style="display: inline-block; margin-top: 15px; color: #3b82f6; font-weight: 600; text-decoration: underline;">¿Deseas registrar este cliente nuevo?</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="action-footer" style="margin-top: 20px; text-align: right;">
                <a href="clientes.php" class="btn-secondary" style="text-decoration: none; padding: 10px 16px; border-radius: 6px; margin-right: 10px; font-size: 0.9rem;">Limpiar Búsqueda</a>
                <a href="nuevo_cliente.php" class="btn-primary" style="text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600;">+ Nuevo Cliente</a>
            </div>

        <?php endif; ?>

    </main>
</div>

<?php include(__DIR__ . "/footer.php"); ?>