<?php
session_start();
require_once __DIR__ . '/../config/connection.php';

// 1. SEGURIDAD
if (!isset($_SESSION['username'])) {
    header("Location: /unideportes-system/public/index.php");
    exit();
}

// 2. LÓGICA DE GUARDADO (Procesar el formulario)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre'])) {
    $nombre    = mysqli_real_escape_string($conn, $_POST['nombre']);
    $documento = mysqli_real_escape_string($conn, $_POST['documento']);
    $telefono  = mysqli_real_escape_string($conn, $_POST['telefono']);
    $tipo      = mysqli_real_escape_string($conn, $_POST['tipo']);

    $insertSql = "INSERT INTO clientes (nombre_completo, nit_cedula, telefono, tipo_cliente) 
                  VALUES ('$nombre', '$documento', '$telefono', '$tipo')";
    
    if(mysqli_query($conn, $insertSql)){
        header("Location: clientes.php?msj=ok");
        exit();
    }
}

// 3. CONSULTA CON BUSCADOR
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = $search ? "WHERE nombre_completo LIKE '%$search%' OR nit_cedula LIKE '%$search%'" : "";
$result = mysqli_query($conn, "SELECT * FROM clientes $where ORDER BY id DESC");
$clientes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $clientes[] = $row;
}

include(__DIR__ . "/../views/header.php");
?>

<div class="clientes-layout">
    <h1>Gestión de Clientes - Unideportes</h1>

    <section class="buscador-section">
        <form action="clientes.php" method="GET">
            <input type="text" name="search" placeholder="Buscar por nombre o NIT..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Buscar</button>
        </form>
    </section>

    <div class="flex-container">
        <aside class="users-form">
            <h3>Registrar Nuevo Cliente</h3>
            <form action="clientes.php" method="POST">
                <label>Nombre o Razón Social:</label>
                <input type="text" name="nombre" required>

                <label>NIT o Cédula:</label>
                <input type="text" name="documento" required>

                <label>Teléfono:</label>
                <input type="text" name="telefono">

                <label>Tipo de Cliente:</label>
                <select name="tipo">
                    <option value="Individual">Persona Individual</option>
                    <option value="Equipo">Equipo Deportivo</option>
                    <option value="Colegio">Institución Educativa</option>
                    <option value="Empresa">Empresa</option>
                </select>

                <button type="submit" class="users-table--edit">GUARDAR CLIENTE</button>
            </form>
        </aside>

        <section class="users-table">
            <div class="clientes-tabs">
                <button type="button" class="tab-btn active" data-panel="panel-clientes">Clientes</button>
                <button type="button" class="tab-btn" data-panel="panel-contactos">Contactos</button>
            </div>

            <div id="panel-clientes" class="tab-panel active">
                <table>
                    <thead>
                        <tr>
                            <th>Cliente / Contacto</th>
                            <th>Documento</th>
                            <th>Categoría</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($clientes as $row): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['nombre_completo']) ?></strong><br>
                                <small>Tel: <?= htmlspecialchars($row['telefono']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($row['nit_cedula']) ?></td>
                            <td><?= htmlspecialchars($row['tipo_cliente']) ?></td>
                            <td>
                                <button type="button" class="toggle-detalle" data-target="#detalle-<?= $row['id'] ?>">Ver</button>
                            </td>
                        </tr>
                        <tr id="detalle-<?= $row['id'] ?>" class="detalle-row">
                            <td colspan="4">
                                <div class="detalle-content">
                                    <p><strong>Cliente:</strong> <?= htmlspecialchars($row['nombre_completo']) ?></p>
                                    <p><strong>NIT / Cédula:</strong> <?= htmlspecialchars($row['nit_cedula']) ?></p>
                                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($row['telefono']) ?></p>
                                    <p><strong>Tipo:</strong> <?= htmlspecialchars($row['tipo_cliente']) ?></p>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="panel-contactos" class="tab-panel">
                <div class="contactos-grid">
                    <?php if (count($clientes) === 0): ?>
                        <p>No hay contactos para mostrar.</p>
                    <?php endif; ?>

                    <?php foreach($clientes as $row): ?>
                        <div class="contacto-card">
                            <h4><?= htmlspecialchars($row['nombre_completo']) ?></h4>
                            <p><strong>Teléfono:</strong> <?= htmlspecialchars($row['telefono']) ?></p>
                            <p><strong>NIT / Cédula:</strong> <?= htmlspecialchars($row['nit_cedula']) ?></p>
                            <p><strong>Tipo:</strong> <?= htmlspecialchars($row['tipo_cliente']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toggle-detalle').forEach(function(button) {
            button.addEventListener('click', function() {
                var target = document.querySelector(this.dataset.target);
                if (!target) return;
                target.classList.toggle('detalle-abierto');
                this.textContent = target.classList.contains('detalle-abierto') ? 'Cerrar' : 'Ver';
            });
        });

        document.querySelectorAll('.tab-btn').forEach(function(tab) {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(function(btn) {
                    btn.classList.remove('active');
                });
                document.querySelectorAll('.tab-panel').forEach(function(panel) {
                    panel.classList.remove('active');
                });

                this.classList.add('active');
                var panel = document.getElementById(this.dataset.panel);
                if (panel) {
                    panel.classList.add('active');
                }
            });
        });
    });
</script>

<?php include(__DIR__ . "/../views/footer.php"); ?>