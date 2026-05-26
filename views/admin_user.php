<?php
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();
require_login(['admin']);

$query = $pdo->query("SELECT * FROM usuarios");

include("header.php");
?>

<div class="container admin-layout">

    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <!-- CONTENIDO -->
    <main class="main-content-panel">

        <h1>Gestión de Usuarios</h1>

        <!-- FORMULARIO -->
        <div class="users-form">
            <form action="insert_user.php" method="POST">
                <h2>Crear Usuario</h2>

                <input type="text" name="name" placeholder="Nombre" required>
                <input type="text" name="lastname" placeholder="Apellido" required>
                <input type="text" name="username" placeholder="Usuario" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <input type="email" name="email" placeholder="Email" required>

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
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['name'] ?></td>
                            <td><?= $row['lastname'] ?></td>
                            <td><?= $row['username'] ?></td>
                            <td><?= $row['email'] ?></td>
                            

                            <td>
                                <a href="update.php?id=<?= $row['id'] ?>">✏️</a>
                            </td>
                            <td>
                                <a href="../controllers/delete_user.php?id=<?= $row['id'] ?>" class="delete-user" onclick="return confirm('¿Estás seguro de eliminar este usuario?');">🗑️</a>
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