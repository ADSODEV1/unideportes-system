<?php
session_start();
require_once __DIR__ . '/../config/connection.php';
$conn = connection();

// 1. SEGURIDAD: Solo admin puede editar usuarios
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: /unideportes-system/public/index.php?error=acceso_denegado");
    exit();
}

// 2. OBTENER ID DEL USUARIO A EDITAR
if (!isset($_GET['id'])) {
    header("Location: admin_user.php?error=id_no_encontrado");
    exit();
}

$id = intval($_GET['id']);

// 3. CONSULTAR DATOS ACTUALES DEL USUARIO
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: admin_user.php?error=usuario_no_existe");
    exit();
}

$row = $result->fetch_assoc();

// 4. PROCESAR FORMULARIO SI SE ENVÍA POR POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'] ?? '';

    // Validar que username sea único (excepto el del usuario actual)
    $check_sql = "SELECT id FROM usuarios WHERE username = '$username' AND id != $id";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "El usuario ya existe";
    } else {
        // Si hay contraseña nueva, hashearla
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $update_sql = "UPDATE usuarios SET name='$name', lastname='$lastname', username='$username', email='$email', password='$password_hash' WHERE id=$id";
        } else {
            // Si no hay contraseña nueva, solo actualizar otros datos
            $update_sql = "UPDATE usuarios SET name='$name', lastname='$lastname', username='$username', email='$email' WHERE id=$id";
        }
        
        if (mysqli_query($conn, $update_sql)) {
            header("Location: admin_user.php?success=usuario_actualizado");
            exit();
        } else {
            $error = "Error al actualizar: " . mysqli_error($conn);
        }
    }
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar-panel">
        <div class="sidebar-section">
            <h3>Administrador</h3>
            <p>Bienvenido:<br><strong><?= $_SESSION['username']; ?></strong></p>
        </div>
    </aside>

    <!-- CONTENIDO -->
    <main class="main-content-panel">
        <h1>✏️ Editar Usuario</h1>
        <p>Actualiza los datos del usuario: <strong><?= htmlspecialchars($row['username']) ?></strong></p>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="users-form">
            <form action="update.php?id=<?= $id ?>" method="POST">
                
                <label>Nombre:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>

                <label>Apellido:</label>
                <input type="text" name="lastname" value="<?= htmlspecialchars($row['lastname']) ?>" required>

                <label>Usuario:</label>
                <input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>" required>

                <label>Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>">

                <label>Nueva Contraseña (opcional - dejar en blanco para no cambiar):</label>
                <input type="password" name="password" placeholder="Dejar vacío para mantener la actual">

                <button type="submit" class="btn-guardar">✅ ACTUALIZAR USUARIO</button>
                <a href="admin_user.php" class="btn-cancelar">← Cancelar</a>
            </form>
        </div>

    </main>

</div>

<style>
.btn-guardar,
.btn-cancelar {
    display: inline-block;
    padding: 12px 20px;
    margin: 10px 10px 10px 0;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
}

.btn-guardar {
    background: var(--primary);
    color: white;
}

.btn-guardar:hover {
    background: #c91a25;
}

.btn-cancelar {
    background: #9ca3af;
    color: white;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #dc2626;
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>
