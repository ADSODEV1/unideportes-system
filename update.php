<?php 
session_start();
// 1. SEGURIDAD: Solo admin puede editar
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') { 
    header("Location: index.php"); 
    exit(); 
}

include("connection.php");
include("header.php");

// 2. OBTENER DATOS: Buscamos al usuario por su ID
$id = $_GET['id'];
$sql = "SELECT * FROM usuarios WHERE id = '$id'";
$query = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($query);
?>

<div class="container py-5">
    <div class="card shadow mx-auto" style="max-width: 500px; border-radius: 15px;">
        <div class="card-header text-white text-center" style="background-color: #1A2B4C;">
            <h3 class="my-2">Editar Perfil</h3>
        </div>
        <div class="card-body p-4">
            <form action="edit_user.php" method="POST">
                <input type="hidden" name="id" value="<?= $row['id']?>">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre</label>
                    <input type="text" class="form-control" name="name" value="<?= $row['name']?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Apellidos</label>
                    <input type="text" class="form-control" name="lastname" value="<?= $row['lastname']?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= $row['email']?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Rol en Unideportes</label>
                    <select name="role" class="form-select">
                        <option value="colaborador" <?= ($row['role'] == 'colaborador') ? 'selected' : '' ?>>Colaborador</option>
                        <option value="admin" <?= ($row['role'] == 'admin') ? 'selected' : '' ?>>Administrador</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-lg w-100 text-white fw-bold" style="background-color: #E61E2A; border-radius: 10px;">
                    GUARDAR CAMBIOS
                </button>
                <a href="admin_user.php" class="btn btn-link w-100 text-muted mt-2">Volver atrás</a>
            </form>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>