<?php
session_start();
require_once __DIR__ . '/../config/connection.php';
$conn = connection();

if (!isset($_SESSION['username'])) {
    header('Location: /unideportes-system/public/index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre_completo'] ?? '');
    $nit = mysqli_real_escape_string($conn, $_POST['nit_cedula'] ?? '');
    $telefono = mysqli_real_escape_string($conn, $_POST['telefono'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $tipo = mysqli_real_escape_string($conn, $_POST['tipo_cliente'] ?? 'Individual');

    if ($nombre === '' || $nit === '') {
        $error = 'Nombre y NIT/Cédula son obligatorios.';
    } else {
        $stmt = $conn->prepare('INSERT INTO clientes (nombre_completo, nit_cedula, telefono, email, tipo_cliente) VALUES (?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('sssss', $nombre, $nit, $telefono, $email, $tipo);
            if ($stmt->execute()) {
                header('Location: clientes.php?msj=cliente_creado');
                exit();
            }
            $error = 'No fue posible crear el cliente.';
            $stmt->close();
        } else {
            $error = 'Error en la preparación de la consulta.';
        }
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