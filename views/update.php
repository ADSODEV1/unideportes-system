<?php
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();

// 1. Protección de acceso: Solo los administradores pueden entrar aquí
require_login(['admin']);

// 2. Validar que nos pasen un ID válido por la URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_user.php?error=id_no_encontrado");
    exit();
}

$id = intval($_GET['id']);

// 3. Buscar los datos actuales del usuario en la Base de Datos
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header("Location: admin_user.php?error=usuario_no_existe");
    exit();
}

$error = null;

// 4. Procesar los datos CUANDO el administrador presione el botón de guardar (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'vendedor'); // Guardamos el rol (admin, vendedor, colaborador)
    $password = $_POST['password'] ?? '';

    // Validar que el nuevo nombre de usuario no lo tenga OTRA persona
    $checkStmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
    $checkStmt->execute([$username, $id]);

    if ($checkStmt->fetch()) {
        $error = "El nombre de usuario ya está registrado por otra persona.";
    } else {
        // Contraseña inteligente: Si la dejan en blanco, conservamos la antigua
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
            // Si todo sale bien, volvemos a la lista de usuarios con mensaje de éxito
            header("Location: admin_user.php?success=usuario_actualizado");
            exit();
        }

        $error = "Ocurrió un error inesperado al actualizar en la base de datos.";
    }
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        
        <div class="content-header" style="margin-bottom: 25px;">
            <h1>✏️ Editar Usuario</h1>
            <p style="color: #64748b; margin-top: 5px;">Modifica los datos y credenciales del usuario: <strong><?= htmlspecialchars($row['username']) ?></strong></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); max-width: 650px; border: 1px solid #e2e8f0;">
            
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 18px;">
                <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #475569;">Nombre:</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;" required>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #475569;">Apellido:</label>
                        <input type="text" name="lastname" value="<?= htmlspecialchars($row['lastname']) ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #475569;">Nombre de Usuario:</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;" required>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #475569;">Rol del Usuario:</label>
                        <select name="role" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: white;" required>
                            <option value="vendedor" <?= ($row['role'] ?? '') === 'vendedor' ? 'selected' : '' ?>>Vendedor (Punto de Venta)</option>
                            <option value="colaborador" <?= ($row['role'] ?? '') === 'colaborador' ? 'selected' : '' ?>>Colaborador (Producción)</option>
                            <option value="admin" <?= ($row['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #475569;">Correo Electrónico:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;">
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 4px; color: #475569;">Nueva Contraseña:</label>
                    <span style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 6px;">(Dejar en blanco para conservar la actual)</span>
                    <input type="password" name="password" placeholder="Escribe la nueva contraseña segura..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;">
                </div>

                <div style="margin-top: 10px; display: flex; justify-content: flex-end; gap: 12px;">
                    <a href="admin_user.php" class="btn-cancelar" style="margin: 0; text-decoration: none; line-height: 20px;">← Cancelar</a>
                    <button type="submit" class="btn-guardar" style="margin: 0;">✅ Actualizar Usuario</button>
                </div>

            </form>
        </div>

    </main>
</div>

<style>
.btn-guardar, .btn-cancelar { display: inline-block; padding: 12px 22px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; text-align: center; font-size: 0.95rem; transition: background 0.2s; }
.btn-guardar { background: var(--primary, #c91a25); color: white; }
.btn-guardar:hover { background: #b0131c; }
.btn-cancelar { background: #64748b; color: white; }
.btn-cancelar:hover { background: #475569; }
.alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; }
.alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; }
</style>

<?php include(__DIR__ . "/footer.php"); ?>