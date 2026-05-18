<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

require_login();
$conn = app();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('clientes.php?error=id_invalido');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: clientes.php?error=id_invalido');
    exit();
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
        'nombre_completo' => $_POST['nombre_completo'] ?? '',
        'nit_cedula' => $_POST['nit_cedula'] ?? '',
        'telefono' => $_POST['telefono'] ?? '',
        'email' => $_POST['email'] ?? '',
        'tipo_cliente' => $_POST['tipo_cliente'] ?? 'Individual',
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

            <div class="form-actions">
                <button type="submit" class="btn-primary">Guardar cambios</button>
                <a href="clientes.php" class="btn-secondary">Volver</a>
            </div>
        </form>
    </main>
</div>

<?php include(__DIR__ . '/footer.php'); ?>