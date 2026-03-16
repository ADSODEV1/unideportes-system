<?php
session_start();
// 1. SEGURIDAD: Solo el admin puede estar aquí
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php?error=acceso_denegado");
    exit();
}

include("connection.php");
include("header.php");

// 2. CONSULTA: Traemos los datos de la tabla correcta 'usuarios'
$sql = "SELECT * FROM usuarios";
$query = mysqli_query($conn, $sql);
?>

<div class="container py-4">
    
    <div class="alert shadow-sm border-0 mb-4 bg-white" style="border-left: 5px solid #1A2B4C;">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">Panel Administrativo - Unideportes</h5>
                <small class="text-muted">Hola, <strong><?= $_SESSION['username'] ?></strong> (Administrador)</small>
            </div>
            <a href="auth.php?logout=1" class="btn btn-sm btn-outline-danger">Cerrar Sesión</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0" style="border-radius: 15px; border-top: 5px solid #E61E2A;">
                <div class="card-body p-4">
                    <h4 class="h5 mb-4 fw-bold">Registrar Personal</h4>
                    <form action="insert_user.php" method="POST">
                        <input type="text" name="name" class="form-control mb-2" placeholder="Nombre" required>
                        <input type="text" name="lastname" class="form-control mb-2" placeholder="Apellidos" required>
                        <input type="text" name="username" class="form-control mb-2" placeholder="Usuario" required>
                        <input type="password" name="password" class="form-control mb-2" placeholder="Contraseña" required>
                        <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
                        
                        <label class="small fw-bold">Asignar Rol</label>
                        <select name="role" class="form-select mb-4">
                            <option value="colaborador">Colaborador / Ventas</option>
                            <option value="admin">Administrador</option>
                        </select>
                        
                        <button type="submit" class="btn w-100 text-white fw-bold" style="background-color: #E61E2A;">
                            GUARDAR USUARIO
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="table-responsive shadow-sm" style="border-radius: 12px; background: white;">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background-color: #1A2B4C; color: white;">
                        <tr>
                            <th class="p-3">Personal</th>
                            <th class="p-3 text-center">Rol</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_array($query)) : ?>
                            <tr>
                                <td class="p-3">
                                    <div class="fw-bold"><?= $row['name'] ?> <?= $row['lastname'] ?></div>
                                    <div class="small text-muted"><?= $row['email'] ?></div>
                                </td>
                                <td class="p-3 text-center">
                                    <span class="badge <?= ($row['role'] == 'admin') ? 'bg-danger' : 'bg-info text-dark' ?>">
                                        <?= strtoupper($row['role']) ?>
                                    </span>
                                </td>
                                <td class="p-3 text-center">
                                    <a href="update.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary">✏️</a>
                                    <a href="delete_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar?')">🗑️</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>