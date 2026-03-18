<?php
session_start();
include("connection.php");

// 1. SEGURIDAD
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
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
$query = mysqli_query($conn, "SELECT * FROM clientes $where ORDER BY id DESC");

include("header.php");
?>

<div class="clientes-layout">
    <h1>Gestión de Clientes - Unideportes</h1>

    <section class="buscador-section">
        <form action="clientes.php" method="GET">
            <input type="text" name="search" placeholder="Buscar por nombre o NIT..." value="<?= $search ?>">
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
            <table>
                <thead>
                    <tr>
                        <th>Cliente / Contacto</th>
                        <th>Documento</th>
                        <th>Categoría</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_array($query)): ?>
                    <tr>
                        <td>
                            <strong><?= $row['nombre_completo'] ?></strong><br>
                            <small>Tel: <?= $row['telefono'] ?></small>
                        </td>
                        <td><?= $row['nit_cedula'] ?></td>
                        <td><?= $row['tipo_cliente'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </div>
</div>

<?php include("footer.php"); ?>