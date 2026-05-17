<?php
session_start();
require_once __DIR__ . '/../config/connection.php';
$conn = connection();

if (!isset($_SESSION['username'])) {
    header('Location: /unideportes-system/public/index.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: clientes.php?error=id_invalido');
    exit();
}

$id = intval($_GET['id']);
$result = mysqli_query($conn, "SELECT id, nombre_completo, nit_cedula, telefono, email, tipo_cliente FROM clientes WHERE id = $id LIMIT 1");
$cliente = mysqli_fetch_assoc($result);
if (!$cliente) {
    header('Location: clientes.php?error=cliente_no_encontrado');
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
        $sql = "UPDATE clientes SET nombre_completo = ?, nit_cedula = ?, telefono = ?, email = ?, tipo_cliente = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sssssi', $nombre, $nit, $telefono, $email, $tipo, $id);
            if ($stmt->execute()) {
                header('Location: clientes.php?msj=cliente_actualizado');
                exit();
            }
            $error = 'No fue posible actualizar el cliente.';
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