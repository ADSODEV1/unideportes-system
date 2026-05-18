<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

require_login();
$conn = app();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre_completo' => request('nombre_completo'),
        'nit_cedula' => request('nit_cedula'),
        'telefono' => request('telefono'),
        'email' => request('email'),
        'tipo_cliente' => request('tipo_cliente') ?: 'Individual',
    ];

    if (trim($data['nombre_completo']) === '' || trim($data['nit_cedula']) === '') {
        $error = 'Nombre y NIT/Cédula son obligatorios.';
    } elseif (!crearCliente($conn, $data)) {
        $error = 'No fue posible crear el cliente.';
    } else {
        redirect('clientes.php?msj=cliente_creado');
    }
}

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>
    <main class="main-content-panel">
        <h1>Nuevo Cliente</h1>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="nuevo_cliente.php" method="POST" class="simple-form">
            <label>Nombre completo</label>
            <input type="text" name="nombre_completo" required>

            <label>NIT / Cédula</label>
            <input type="text" name="nit_cedula" required>

            <label>Teléfono</label>
            <input type="text" name="telefono">

            <label>Email</label>
            <input type="email" name="email">

            <label>Tipo de cliente</label>
            <select name="tipo_cliente">
                <option value="Individual">Persona Individual</option>
                <option value="Equipo">Equipo Deportivo</option>
                <option value="Colegio">Institución Educativa</option>
                <option value="Empresa">Empresa</option>
            </select>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Guardar cliente</button>
                <a href="clientes.php" class="btn-secondary">Volver</a>
            </div>
        </form>
    </main>
</div>

<?php include(__DIR__ . '/footer.php'); ?>