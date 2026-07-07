<?php
// views/admin_usuarios.php
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();
require_login(['admin']);

$query = $pdo->query("SELECT * FROM usuarios");

$mensaje = $_GET['msj'] ?? '';

include 'header.php';
?>

<div class="container admin-layout">

    <?php include __DIR__ . '/sidebar_control.php'; ?>

    <!-- CONTENIDO -->
    <main class="main-content-panel">

        <h1>Gestión de Usuarios</h1>

        <?php if ($mensaje === 'ok'): ?>
            <div class="alert alert-success" style="margin: 15px 0;">Usuario creado exitosamente.</div>
        <?php elseif ($mensaje === 'datos_invalidos' || $mensaje === 'Datos%20inv%C3%A1lidos' || $mensaje === 'Datos inválidos'): ?>
            <div class="alert alert-error" style="margin: 15px 0;">Datos inválidos.</div>
        <?php elseif ($mensaje === 'caracteres_no_permitidos' || $mensaje === 'Caracteres%20no%20permitidos' || $mensaje === 'Caracteres no permitidos'): ?>
            <div class="alert alert-error" style="margin: 15px 0;">Caracteres no permitidos.</div>
        <?php elseif ($mensaje === 'password_invalida'): ?>
            <div class="alert alert-error" style="margin: 15px 0;">La contraseña no cumple el mínimo requerido.</div>
        <?php elseif ($mensaje === 'email_invalido'): ?>
            <div class="alert alert-error" style="margin: 15px 0;">El correo electrónico no es válido.</div>
        <?php elseif ($mensaje === 'rol_invalido'): ?>
            <div class="alert alert-error" style="margin: 15px 0;">El rol seleccionado no es válido.</div>
        <?php elseif ($mensaje === 'usuario_o_correo_duplicado'): ?>
            <div class="alert alert-error" style="margin: 15px 0;">El usuario o correo ya existe.</div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <div class="users-form">
            <form action="../controllers/insert_user.php" method="POST">
                <h2>Crear Usuario</h2>

                <input type="text" name="name" placeholder="Nombre" required>
                <input type="text" name="lastname" placeholder="Apellido" required>
                <input type="text" name="username" placeholder="Usuario" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <input type="email" name="email" placeholder="Email" required>
                <select name="role" required>
                    <option value="vendedor">Vendedor</option>
                    <option value="admin">Administrador</option>
                </select>

                <input type="submit" value="Guardar Usuario">
            </form>
        </div>

        <hr>

        <!-- TABLA -->
        <div class="users-table">
            <table class="tabla-maestra">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = $query->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $row['id']) ?></td>
                            <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['lastname'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['username'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                            

                            <td>
                                <a href="update.php?id=<?= htmlspecialchars((string) $row['id']) ?>">✏️</a>
                            </td>
                            <td>
                                <a href="../controllers/delete_user.php?id=<?= htmlspecialchars((string) $row['id']) ?>" class="delete-user" onclick="return confirm('¿Estás seguro de eliminar este usuario?');">🗑️</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>

            </table>
        </div>

    </main>

</div>

<!-- FOOTER -->
<footer class="main-footer">
    <p>&copy; <?= date("Y"); ?> Unideportes - Sistema de Gestión Interno</p>
</footer>
