<?php
// views/editar_usuario.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin']);

$pdo = app();

// 1. Validar ID por URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_usuarios.php?error=id_no_encontrado");
    exit();
}

$id = intval($_GET['id']);

// 2. Buscar usuario actual
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header("Location: admin_usuarios.php?error=usuario_no_existe");
    exit();
}

$error = null;

// 3. Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'vendedor');
    $password = $_POST['password'] ?? '';

    // VALIDACIONES MEJORADAS
    $errores = [];

    // Campos obligatorios
    if (empty($name)) $errores[] = "El nombre es obligatorio.";
    if (empty($lastname)) $errores[] = "El apellido es obligatorio.";
    if (empty($username)) $errores[] = "El nombre de usuario es obligatorio.";
    
    // Longitud mínima de username
    if (strlen($username) < 3) {
        $errores[] = "El nombre de usuario debe tener al menos 3 caracteres.";
    }

    // Validar formato de email (si se proporciona)
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del correo electrónico no es válido.";
    }

    // Validar que el rol sea válido
    $rolesValidos = ['admin', 'vendedor', 'colaborador'];
    if (!in_array($role, $rolesValidos)) {
        $errores[] = "El rol seleccionado no es válido.";
    }

    // Validar contraseña (si se proporciona)
    if (!empty($password) && strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }

    // Verificar username duplicado
    $checkStmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
    $checkStmt->execute([$username, $id]);
    if ($checkStmt->fetch()) {
        $errores[] = "El nombre de usuario ya está registrado por otra persona.";
    }

    // Si hay errores, mostrarlos
    if (!empty($errores)) {
        $error = implode("<br>", $errores);
    } else {
        // Actualizar usuario
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
            header("Location: admin_usuarios.php?success=usuario_actualizado");
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
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Editar Usuario</h1>
                <p>Modifica los datos y credenciales de: <strong><?= htmlspecialchars($row['username']) ?></strong></p>
            </div>
            <a href="admin_usuarios.php" class="btn-secondary">← Volver a Usuarios</a>
        </div>

        <!-- ALERTA DE ERROR -->
        <?php if ($error): ?>
            <div class="alert-error">
                ⚠️ <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <form action="" method="POST" class="form-usuario">
            
            <!-- SECCIÓN 1: DATOS PERSONALES -->
            <div class="form-section">
                <h2 class="section-subtitle">👤 Datos Personales</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Nombre *</label>
                        <input type="text" name="name" id="name" required 
                               class="form-input" 
                               value="<?= htmlspecialchars($row['name']) ?>"
                               placeholder="Ej: Juan">
                    </div>
                    
                    <div class="form-group">
                        <label for="lastname">Apellido *</label>
                        <input type="text" name="lastname" id="lastname" required 
                               class="form-input" 
                               value="<?= htmlspecialchars($row['lastname']) ?>"
                               placeholder="Ej: Pérez">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: DATOS DE ACCESO -->
            <div class="form-section">
                <h2 class="section-subtitle">🔐 Datos de Acceso</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Nombre de Usuario *</label>
                        <input type="text" name="username" id="username" required 
                               class="form-input" 
                               value="<?= htmlspecialchars($row['username']) ?>"
                               placeholder="Ej: jperez" minlength="3">
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Rol del Usuario *</label>
                        <select name="role" id="role" class="form-input" required>
                            <option value="vendedor" <?= ($row['role'] ?? '') === 'vendedor' ? 'selected' : '' ?>>
                                Vendedor (Punto de Venta)
                            </option>
                            <option value="colaborador" <?= ($row['role'] ?? '') === 'colaborador' ? 'selected' : '' ?>>
                                Colaborador (Producción)
                            </option>
                            <option value="admin" <?= ($row['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                                Administrador
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group form-group-full">
                        <label for="email">Correo Electrónico</label>
                        <input type="email" name="email" id="email" 
                               class="form-input" 
                               value="<?= htmlspecialchars($row['email'] ?? '') ?>"
                               placeholder="Ej: jperez@unideportes.com">
                    </div>
                    
                    <div class="form-group form-group-full">
                        <label for="password">Nueva Contraseña</label>
                        <small class="form-hint">Dejar en blanco para conservar la contraseña actual. Mínimo 6 caracteres.</small>
                        <input type="password" name="password" id="password" 
                               class="form-input" 
                               placeholder="Escribe la nueva contraseña segura..."
                               minlength="6">
                    </div>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div class="form-actions">
                <a href="admin_usuarios.php" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">💾 Actualizar Usuario</button>
            </div>
            
        </form>
    </main>
</div>

<style>
/* ============================================
   EDITAR USUARIO - ESTILOS SIMPLIFICADOS
   ============================================ */

/* Encabezado */
.page-header {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.page-header h1 {
    color: #1e293b;
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0;
}

.page-header p {
    color: #64748b;
    margin: 5px 0 0 0;
    font-size: 0.95rem;
}

/* Alerta de error */
.alert-error {
    padding: 12px 16px;
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}

/* Formulario */
.form-usuario {
    background: white;
}

.form-section {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.section-subtitle {
    color: #475569;
    font-size: 1.05rem;
    font-weight: 600;
    margin: 0 0 15px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid #e2e8f0;
}

/* Grid del formulario */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group-full {
    grid-column: 1 / -1;
}

/* Labels */
.form-group label {
    display: block;
    font-size: 0.9rem;
    font-weight: 500;
    color: #334155;
    margin-bottom: 5px;
}

.form-hint {
    font-size: 0.85rem;
    color: #64748b;
    margin-bottom: 5px;
    display: block;
}

/* Inputs */
.form-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.95rem;
    background: white;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input::placeholder {
    color: #94a3b8;
}

/* Botones */
.btn-primary {
    padding: 10px 20px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-block;
    transition: background 0.2s;
}

.btn-primary:hover {
    background: #1d4ed8;
}

.btn-secondary {
    padding: 10px 20px;
    background: white;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

/* Acciones del formulario */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 10px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

/* Responsive */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group-full {
        grid-column: 1;
    }
    
    .page-header {
        flex-direction: column;
        text-align: center;
    }
    
    .form-actions {
        flex-direction: column-reverse;
    }
    
    .form-actions > * {
        width: 100%;
        text-align: center;
    }
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>