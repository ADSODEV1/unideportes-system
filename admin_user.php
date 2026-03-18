<div class="users-form">
    <form action="insert_user.php" method="POST">
        <h2>Crear Usuario</h2>
        <input type="text" name="name" placeholder="Nombre">
        <input type="text" name="lastname" placeholder="Apellido">
        <input type="text" name="username" placeholder="Usuario">
        <input type="password" name="password" placeholder="Contraseña">
        <input type="email" name="email" placeholder="Email">
        
        <input type="submit" value="Guardar Usuario">
    </form>
</div>

<hr>

<div class="users-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Usuario</th>
                <th>Email</th>
                <th></th> <th></th> </tr>
        </thead>
        <tbody>
            <?php while ($row = $query->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['name'] ?></td>
                    <td><?= $row['lastname'] ?></td>
                    <td><?= $row['username'] ?></td>
                    <td><?= $row['email'] ?></td>

                    <td>
                        <a href="update.php?id=<?= $row['id'] ?>" class="users-table--edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    </td>
                    <td>
                        <a href="delete_user.php?id=<?= $row['id'] ?>" class="users-table--delete">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>