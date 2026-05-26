<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

require_login();
$conn = app();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre_completo'   => request('nombre_completo'),
        'nit_cedula'        => request('nit_cedula'),
        'telefono'          => request('telefono'),
        'email'             => request('email'),
        'tipo_cliente'      => request('tipo_cliente') ?: 'Individual',
        // NUEVO: Captura de campos de domicilio para el modelo
        'direccion'         => request('direccion') ?: null,
        'barrio'            => request('barrio') ?: null,
        'ciudad'            => request('ciudad') ?: 'Sogamoso',
        'referencia_entrega'=> request('referencia_entrega') ?: null,
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

            <div style="margin-top: 25px; margin-bottom: 15px; padding: 15px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; color: #1e293b;">Información Predeterminada de Envío / Domicilio</h3>
                
                <label>Dirección base</label>
                <input type="text" name="direccion" placeholder="Ej: Calle 11 # 12-34">

                <label>Barrio</label>
                <input type="text" name="barrio" placeholder="Ej: Centro">

                <label>Ciudad</label>
                <input type="text" name="ciudad" value="Sogamoso">

                <label>Referencias / Observaciones de entrega</label>
                <input type="text" name="referencia_entrega" placeholder="Ej: Frente al parque principal, casa de rejas negras">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Guardar cliente</button>
                <a href="clientes.php" class="btn-secondary">Volver</a>
            </div>
        </form>
    </main>
</div>

<?php include(__DIR__ . '/footer.php'); ?>