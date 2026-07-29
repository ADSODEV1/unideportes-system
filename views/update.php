<?php
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();
require_login(['admin']);

// 1. Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_usuarios.php?msj=id_no_encontrado");
    exit();
}

$id = intval($_GET['id']);

// 2. Buscar usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header("Location: admin_usuarios.php?msj=usuario_no_existe");
    exit();
}

$error = null;

// 3. Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'vendedor');
    $password = $_POST['password'] ?? '';

    // Validaciones básicas
    if (empty($name) || empty($lastname) || empty($username) || empty($email)) {
        $error = "datos_invalidos";
    }

    // Validar rol
    $rolesValidos = ['vendedor', 'admin'];
    if (!$error && !in_array($role, $rolesValidos, true)) {
        $error = "rol_invalido";
    }

    // Validar formato de username
    if (!$error && !preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        $error = "caracteres_no_permitidos";
    }

    // Validar email
    if (!$error && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "email_invalido";
    }

    // Validar contraseña si se proporciona
    if (!$error && !empty($password)) {
        if (strlen($password) < 8 || strlen($password) > 72) {
            $error = "password_invalida";
        }
    }

    // Verificar duplicados (username)
    if (!$error) {
        $checkStmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
        $checkStmt->execute([$username, $id]);
        if ($checkStmt->fetch()) {
            $error = "usuario_o_correo_duplicado";
        }
    }

    // Verificar duplicados (email)
    if (!$error) {
        $checkEmail = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $checkEmail->execute([$email, $id]);
        if ($checkEmail->fetch()) {
            $error = "usuario_o_correo_duplicado";
        }
    }

    // Si no hay errores, actualizar
    if (!$error) {
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $updateSql = "UPDATE usuarios SET name = ?, lastname = ?, username = ?, email = ?, role = ?, password = ? WHERE id = ?";
            $params = [$name, $lastname, $username, $email, $role, $password_hash, $id];
        } else {
            $updateSql = "UPDATE usuarios SET name = ?, lastname = ?, username = ?, email = ?, role = ? WHERE id = ?";
            $params = [$name, $lastname, $username, $email, $role, $id];
        }

        $updateStmt = $pdo->prepare($updateSql);
        if ($updateStmt->execute($params)) {
            header("Location: admin_usuarios.php?msj=usuario_actualizado");
            exit();
        }
        $error = "datos_invalidos";
    }
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">

        <div class="page-header">
            <div>
                <h1 class="page-header__title">✏️ Editar Usuario</h1>
                <p class="page-header__subtitle">
                    Modifica los datos y credenciales del usuario: 
                    <strong><?= htmlspecialchars($row['username']) ?></strong>
                </p>
            </div>
        </div>

        <?php if ($error): ?>
            <?php
            // Mapeo de errores a mensajes
            $errores = [
                'datos_invalidos'            => 'Datos inválidos o incompletos.',
                'caracteres_no_permitidos'   => 'Caracteres no permitidos en los datos.',
                'password_invalida'          => 'La contraseña debe tener entre 8 y 72 caracteres.',
                'email_invalido'             => 'El correo electrónico no es válido.',
                'rol_invalido'               => 'El rol seleccionado no es válido.',
                'usuario_o_correo_duplicado' => 'El usuario o correo ya existe.',
            ];
            $mensajeError = $errores[$error] ?? 'Ocurrió un error inesperado.';
            ?>
            <div class="alert alert-danger">
                <span>⚠️ <?= htmlspecialchars($mensajeError) ?></span>
                <button class="btn-close" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endif; ?>

        <div class="users-form" style="max-width: 650px;">
            <form action="" method="POST" class="form-grid">
                <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">

                <div class="form-group">
                    <label for="name">Nombre</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($row['name']) ?>" required minlength="2" maxlength="60">
                </div>

                <div class="form-group">
                    <label for="lastname">Apellido</label>
                    <input type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($row['lastname']) ?>" required minlength="2" maxlength="60">
                </div>

                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($row['username']) ?>" required minlength="4" maxlength="30" pattern="[a-zA-Z0-9._-]+">
                </div>

                <div class="form-group">
                    <label for="role">Rol</label>
                    <select id="role" name="role" required>
                        <option value="vendedor" <?= ($row['role'] ?? '') === 'vendedor' ? 'selected' : '' ?>>Vendedor (Punto de Venta)</option>
                        <option value="admin" <?= ($row['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                    </select>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" required>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="password">Nueva Contraseña</label>
                    <small style="color: var(--text-light); font-size: 0.8rem; margin-bottom: 4px; display: block;">
                        Dejar en blanco para conservar la actual (mínimo 8 caracteres si se cambia)
                    </small>
                    <input type="password" id="password" name="password" placeholder="Escribe la nueva contraseña segura..." minlength="8" maxlength="72">
                </div>

                <div style="grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px;">
                    <a href="admin_usuarios.php" class="btn-secondary">← Cancelar</a>
                    <button type="submit" class="btn-primary">✅ Actualizar Usuario</button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php include(__DIR__ . "/footer.php"); ?>