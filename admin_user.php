<?php
session_start();
include("connection.php");

// SEGURIDAD
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// CONSULTA
$query = mysqli_query($conn, "SELECT * FROM usuarios");

include("header.php");
?>

<div class="container admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar-panel">

        <div class="sidebar-section">
            <h3> Administrador</h3>
            <p>Bienvenido:<br><strong><?= $_SESSION['username']; ?></strong></p>
        </div>

        <div class="sidebar-section">
            <h3>⚙️ Acciones</h3>
            
        </div>

    </aside>

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
                    <?php while ($row = mysqli_fetch_assoc($query)): ?>
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
                                <a href="delete_user.php?id=<?= $row['id'] ?>">🗑️</a>
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