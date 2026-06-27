<?php
// views/admin_usuarios.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin']);

$pdo = app();

// Capturar búsqueda
$search = trim($_GET['search'] ?? '');

// Consulta de usuarios con filtro
try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT * FROM usuarios 
            WHERE name LIKE :search 
               OR lastname LIKE :search 
               OR username LIKE :search 
               OR email LIKE :search
            ORDER BY name ASC
        ");
        $stmt->execute(['search' => "%{$search}%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY name ASC");
    }
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $usuarios = [];
    $error_msg = "Error al cargar usuarios: " . $e->getMessage();
}

// Mensajes de éxito/error
$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Gestión de Usuarios</h1>
                <p>Administra los usuarios del sistema, sus roles y permisos.</p>
            </div>
            <button onclick="document.getElementById('modalCrear').style.display='flex'" class="btn-primary">
                + Crear Usuario
            </button>
        </div>

        <!-- ALERTAS DE ÉXITO -->
        <?php if ($success === 'usuario_creado'): ?>
            <div class="alert-success">✅ Usuario creado exitosamente.</div>
        <?php elseif ($success === 'usuario_actualizado'): ?>
            <div class="alert-success">✅ Usuario actualizado correctamente.</div>
        <?php elseif ($success === 'usuario_eliminado'): ?>
            <div class="alert-success">✅ Usuario eliminado correctamente.</div>
        <?php elseif ($success === 'estado_actualizado'): ?>
            <div class="alert-success">✅ Estado del usuario actualizado.</div>
        <?php elseif ($success === 'usuario_desactivado_historial'): ?>
            <div class="alert-success">⚠️ Usuario desactivado (tiene historial de ventas). No se puede eliminar permanentemente.</div>
        <?php endif; ?>

        <!-- ALERTAS DE ERROR -->
        <?php if ($error === 'username_duplicado'): ?>
            <div class="alert-error">⚠️ El nombre de usuario ya existe.</div>
        <?php elseif ($error === 'email_duplicado'): ?>
            <div class="alert-error">⚠️ El correo electrónico ya está registrado.</div>
        <?php elseif ($error === 'error_creacion'): ?>
            <div class="alert-error">⚠️ Error al crear el usuario.</div>
        <?php elseif ($error === 'no_puedes_desactivarte'): ?>
            <div class="alert-error">⚠️ No puedes desactivar tu propia cuenta.</div>
        <?php elseif ($error === 'no_puedes_eliminarte'): ?>
            <div class="alert-error">⚠️ No puedes eliminar tu propia cuenta.</div>
        <?php elseif ($error === 'ultimo_admin'): ?>
            <div class="alert-error">⚠️ No se puede desactivar/eliminar: debe haber al menos un administrador activo.</div>
        <?php elseif ($error === 'usuario_no_existe'): ?>
            <div class="alert-error">⚠️ El usuario no existe.</div>
        <?php elseif ($error === 'estado_invalido'): ?>
            <div class="alert-error">⚠️ Estado inválido.</div>
        <?php elseif ($error === 'accion_invalida'): ?>
            <div class="alert-error">⚠️ Acción no válida.</div>
        <?php elseif ($error === 'error_base_datos'): ?>
            <div class="alert-error">⚠️ Error en la base de datos. Contacta al administrador.</div>
        <?php endif; ?>

        <!-- BUSCADOR -->
        <form method="GET" action="" class="search-form">
            <div class="search-input-wrapper">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Buscar por nombre, usuario o email..." 
                       class="search-input">
                <?php if ($search !== ''): ?>
                    <a href="admin_usuarios.php" class="search-clear" title="Limpiar">❌</a>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn-primary">🔍 Buscar</button>
        </form>

        <!-- TABLA DE USUARIOS -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="6" class="no-results">
                                <span class="empty-icon">👤</span>
                                <p>No se encontraron usuarios.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $user): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($user['name'] . ' ' . $user['lastname']) ?>
                                </td>
                                <td>
                                    <span class="text-small">
                                        <?= htmlspecialchars($user['email'] ?? 'Sin email') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= 
                                        $user['role'] === 'admin' ? 'danger' : 
                                        ($user['role'] === 'vendedor' ? 'info' : 'success') 
                                    ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= ($user['estado'] ?? 'activo') === 'activo' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($user['estado'] ?? 'activo') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="actions-cell">
                                        <a href="editar_usuario.php?id=<?= $user['id'] ?>" 
                                           class="btn-action" title="Editar">✏️</a>
                                        
                                        <?php if (($user['estado'] ?? 'activo') === 'activo'): ?>
                                            <a href="../controllers/usuarios.php?action=toggle_estado&id=<?= $user['id'] ?>&estado=inactivo" 
                                               class="btn-action" 
                                               title="Desactivar"
                                               onclick="return confirm('¿Desactivar este usuario?');">⏸️</a>
                                        <?php else: ?>
                                            <a href="../controllers/usuarios.php?action=toggle_estado&id=<?= $user['id'] ?>&estado=activo" 
                                               class="btn-action" 
                                               title="Activar">▶️</a>
                                        <?php endif; ?>
                                        
                                        <a href="../controllers/usuarios.php?action=eliminar&id=<?= $user['id'] ?>" 
                                           class="btn-action btn-action-danger" 
                                           title="Eliminar"
                                           onclick="return confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.');">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<!-- MODAL CREAR USUARIO -->
<div id="modalCrear" class="modal-overlay">
    <div class="modal-content">
        <h3 class="modal-title">➕ Crear Nuevo Usuario</h3>
        <form action="../controllers/insert_user.php" method="POST">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Nombre *</label>
                    <input type="text" name="name" id="name" required class="form-input" placeholder="Ej: Juan">
                </div>
                
                <div class="form-group">
                    <label for="lastname">Apellido *</label>
                    <input type="text" name="lastname" id="lastname" required class="form-input" placeholder="Ej: Pérez">
                </div>
                
                <div class="form-group">
                    <label for="username">Usuario *</label>
                    <input type="text" name="username" id="username" required class="form-input" placeholder="Ej: jperez">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" name="email" id="email" required class="form-input" placeholder="Ej: jperez@unideportes.com">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña *</label>
                    <input type="password" name="password" id="password" required class="form-input" placeholder="Mínimo 6 caracteres" minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="role">Rol *</label>
                    <select name="role" id="role" class="form-input" required>
                        <option value="vendedor">Vendedor</option>
                        <option value="colaborador">Colaborador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="document.getElementById('modalCrear').style.display='none'" class="btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary">💾 Crear Usuario</button>
            </div>
        </form>
    </div>
</div>

<style>
/* ============================================
   ADMIN USUARIOS - ESTILOS SIMPLIFICADOS
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

/* Alertas */
.alert-success {
    padding: 12px 16px;
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-error {
    padding: 12px 16px;
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}

/* Buscador */
.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.search-input-wrapper {
    position: relative;
    flex-grow: 1;
}

.search-input {
    width: 100%;
    padding: 12px 40px 12px 15px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 1rem;
    box-sizing: border-box;
}

.search-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-clear {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    text-decoration: none;
    color: #94a3b8;
    font-size: 1.1rem;
}

/* Tabla */
.table-container {
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: #f1f5f9;
    border-bottom: 2px solid #e2e8f0;
}

.data-table th {
    padding: 14px;
    text-align: left;
    color: #475569;
    font-weight: 600;
    font-size: 0.9rem;
}

.data-table td {
    padding: 14px;
    border-bottom: 1px solid #e2e8f0;
    color: #334155;
}

.data-table tbody tr:hover {
    background: #f8fafc;
}

.text-center {
    text-align: center;
}

.text-small {
    font-size: 0.85rem;
    color: #64748b;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.badge-danger {
    background: #fee2e2;
    color: #991b1b;
}

/* Acciones */
.actions-cell {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: center;
}

.btn-action {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    padding: 4px 6px;
    border-radius: 4px;
    transition: background 0.2s;
    text-decoration: none;
}

.btn-action:hover {
    background: #f1f5f9;
}

.btn-action-danger:hover {
    background: #fee2e2;
}

/* Sin resultados */
.no-results {
    text-align: center;
    padding: 40px !important;
    color: #94a3b8;
}

.no-results .empty-icon {
    font-size: 2rem;
    margin-bottom: 10px;
    display: block;
}

.no-results p {
    margin: 10px 0;
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

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    padding: 25px;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal-title {
    margin: 0 0 20px 0;
    color: #1e293b;
    font-size: 1.3rem;
    font-weight: 700;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    display: block;
    font-size: 0.9rem;
    font-weight: 500;
    color: #334155;
    margin-bottom: 5px;
}

.form-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.95rem;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        text-align: center;
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .data-table {
        font-size: 0.85rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 10px 8px;
    }
    
    .modal-actions {
        flex-direction: column-reverse;
    }
    
    .modal-actions button {
        width: 100%;
    }
}
</style>

<script>
// Cerrar modal al hacer clic fuera
document.getElementById('modalCrear').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});

// Cerrar con tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('modalCrear').style.display = 'none';
    }
});
</script>

<?php include(__DIR__ . "/footer.php"); ?>