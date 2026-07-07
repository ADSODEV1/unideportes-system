<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

require_login();
$conn = app();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('clientes.php?error=id_invalido');
}

$id = intval($_GET['id']);
$cliente = obtenerClientePorId($conn, $id);
if (!$cliente) {
    header('Location: clientes.php?error=cliente_no_encontrado');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre_completo'   => $_POST['nombre_completo'] ?? '',
        'nit_cedula'        => $_POST['nit_cedula'] ?? '',
        'telefono'          => $_POST['telefono'] ?? '',
        'email'             => $_POST['email'] ?? '',
        'tipo_cliente'      => $_POST['tipo_cliente'] ?? 'Individual',
            'estado'            => $_POST['estado'] ?? 'activo',
        // NUEVO: Captura de campos de domicilio para actualizar el registro
        'direccion'         => !empty($_POST['direccion']) ? trim($_POST['direccion']) : null,
        'barrio'            => !empty($_POST['barrio']) ? trim($_POST['barrio']) : null,
        'ciudad'            => !empty($_POST['ciudad']) ? trim($_POST['ciudad']) : 'Sogamoso',
        'referencia_entrega'=> !empty($_POST['referencia_entrega']) ? trim($_POST['referencia_entrega']) : null,
    ];

    if (trim($data['nombre_completo']) === '' || trim($data['nit_cedula']) === '') {
        $error = 'Nombre y NIT/Cédula son obligatorios.';
    } elseif (!actualizarCliente($conn, $id, $data)) {
        $error = 'No fue posible actualizar el cliente.';
    } else {
        redirect('clientes.php?msj=cliente_actualizado');
    }
}

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>
    <main class="main-content-panel">
        <h1>Editar Cliente</h1>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div style="margin-bottom: 18px; display: inline-flex; align-items: center; gap: 10px;">
            <span style="font-weight: 600; color: #334155;">Estado actual:</span>
            <span style="padding: 6px 12px; border-radius: 999px; font-size: 0.9rem; font-weight: 700; color: <?= ($cliente['estado'] ?? 'activo') === 'activo' ? '#14532d' : '#7f1d1d' ?>; background: <?= ($cliente['estado'] ?? 'activo') === 'activo' ? '#dcfce7' : '#fee2e2' ?>; border: 1px solid <?= ($cliente['estado'] ?? 'activo') === 'activo' ? '#22c55e' : '#ef4444' ?>;">
                <?= ucfirst($cliente['estado'] ?? 'activo') ?>
            </span>
        </div>

        <form action="editar_cliente.php?id=<?= $cliente['id'] ?>" method="POST" class="simple-form">
            <label>Nombre completo</label>
            <input type="text" name="nombre_completo" value="<?= htmlspecialchars($cliente['nombre_completo']) ?>" required>

            <label>NIT / Cédula</label>
            <input type="text" name="nit_cedula" value="<?= htmlspecialchars($cliente['nit_cedula']) ?>" required>

            <label>Teléfono</label>
            <input type="text" name="telefono" value="<?= htmlspecialchars($cliente['telefono']) ?>">

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>">

            <label>Tipo de cliente</label>
            <select name="tipo_cliente">
                <?php foreach (['Individual','Equipo','Colegio','Empresa'] as $tipo): ?>
                    <option value="<?= $tipo ?>" <?= $cliente['tipo_cliente'] === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                <?php endforeach; ?>
            </select>

            <label>Estado</label>
            <select name="estado">
                <?php foreach (['activo' => 'Activo', 'inactivo' => 'Inactivo'] as $valor => $etiqueta): ?>
                    <option value="<?= $valor ?>" <?= ($cliente['estado'] ?? 'activo') === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
                <?php endforeach; ?>
            </select>

            <div style="margin-top: 25px; margin-bottom: 15px; padding: 15px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; color: #1e293b;">Información Predeterminada de Envío / Domicilio</h3>
                
                <label>Dirección base</label>
                <input type="text" name="direccion" value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>" placeholder="Ej: Calle 11 # 12-34">

                <label>Barrio</label>
                <input type="text" name="barrio" value="<?= htmlspecialchars($cliente['barrio'] ?? '') ?>" placeholder="Ej: Centro">

                <label>Ciudad</label>
                <input type="text" name="ciudad" value="<?= htmlspecialchars($cliente['ciudad'] ?: 'Sogamoso') ?>">

                <label>Referencias / Observaciones de entrega</label>
                <input type="text" name="referencia_entrega" value="<?= htmlspecialchars($cliente['referencia_entrega'] ?? '') ?>" placeholder="Ej: Frente al parque principal, casa de rejas negras">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Guardar cambios</button>
                <a href="clientes.php" class="btn-secondary">Volver</a>
            </div>
        </form>
    </main>
</div>

<?php include(__DIR__ . '/footer.php'); ?>