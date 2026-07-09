<?php
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();
require_login(['admin']);

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Lógica de mensajes - UNIFICADA
$msj = $_GET['msj'] ?? '';

$mapaAlertas = [
    'ok'                         => ['Usuario creado exitosamente.',                      'success'],
    'eliminado'                  => ['Usuario eliminado correctamente.',                   'success'],
    'usuario_actualizado'        => ['Usuario actualizado correctamente.',                 'success'],
    'datos_invalidos'            => ['Datos inválidos o incompletos.',                     'danger'],
    'caracteres_no_permitidos'   => ['Caracteres no permitidos en los datos.',             'danger'],
    'password_invalida'          => ['La contraseña debe tener entre 8 y 72 caracteres.',  'danger'],
    'email_invalido'             => ['El correo electrónico no es válido.',                'danger'],
    'rol_invalido'               => ['El rol seleccionado no es válido.',                  'danger'],
    'usuario_o_correo_duplicado' => ['El usuario o correo ya existe.',                     'danger'],
    'id_no_encontrado'           => ['ID de usuario no válido.',                           'danger'],
    'usuario_no_existe'          => ['El usuario no existe en el sistema.',                'danger'],
    'no_puede_eliminarse'        => ['No puedes eliminar tu propio usuario.',              'warning'],
];

$alertaTexto = '';
$alertaTipo  = '';

if ($msj !== '' && array_key_exists($msj, $mapaAlertas)) {
    $alertaTexto = $mapaAlertas[$msj][0];
    $alertaTipo  = $mapaAlertas[$msj][1];
}

include 'header.php';
?>

<div class="container admin-layout">
    <?php include 'sidebar_control.php'; ?>
    
    <main class="main-content-panel">
        
        <div class="page-header">
            <div>
                <h1 class="page-header__title">Gestión de Usuarios</h1>
                <p class="page-header__subtitle">Administra los usuarios del sistema, sus roles y permisos.</p>
            </div>
        </div>

        <!-- ALERTA - Ahora usa las variables correctas -->
        <?php if ($alertaTexto !== '' && $alertaTipo !== ''): ?>
            <div class="alert alert-<?= $alertaTipo ?>">
                <span><?= htmlspecialchars($alertaTexto) ?></span>
                <button class="btn-close" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endif; ?>

        <section class="users-form">
            <h2>Crear Nuevo Usuario</h2>
            <form action="../controllers/insert_user.php" method="POST" class="form-grid">
                
                <div class="form-group">
                    <label for="name">Nombre</label>
                    <input type="text" id="name" name="name" placeholder="Ej: Juan" required minlength="2" maxlength="60">
                </div>

                <div class="form-group">
                    <label for="lastname">Apellido</label>
                    <input type="text" id="lastname" name="lastname" placeholder="Ej: Pérez" required minlength="2" maxlength="60">
                </div>

                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" placeholder="Ej: jperez" required minlength="4" maxlength="30" pattern="[a-zA-Z0-9._-]+">
                </div>

                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" placeholder="Ej: juan@empresa.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required minlength="8" maxlength="72">
                    <!-- ⬆️ CAMBIO CLAVE: minlength="8" en vez de "6" -->
                </div>

                <div class="form-group">
                    <label for="role">Rol</label>
                    <select id="role" name="role" required>
                        <option value="">Seleccionar rol</option>
                        <option value="vendedor">Vendedor</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary">💾 Guardar Usuario</button>
            </form>
        </section>

        <section class="users-table">
            <h2>Usuarios Registrados (<?= count($usuarios) ?>)</h2>
            
            <?php if (empty($usuarios)): ?>
                <p class="empty-state">No hay usuarios registrados todavía.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="tabla-maestra">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['id']) ?></td>
                                    <td><?= htmlspecialchars($u['name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($u['lastname'] ?? '') ?></td>
                                    <td><code><?= htmlspecialchars($u['username'] ?? '') ?></code></td>
                                    <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                                    <td class="actions">
                                        <a href="update.php?id=<?= urlencode($u['id']) ?>" 
                                           class="btn-action btn-edit" 
                                           title="Editar usuario">✏️</a>
                                        <a href="../controllers/delete_user.php?id=<?= urlencode($u['id']) ?>" 
                                           class="btn-action btn-delete" 
                                           title="Eliminar usuario"
                                           onclick="return confirm('¿Estás seguro de eliminar este usuario?');">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </main>
</div>

<footer class="main-footer">
    <p>&copy; <?= date("Y") ?> Unideportes - Sistema de Gestión Interno</p>
</footer>