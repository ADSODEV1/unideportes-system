<?php
session_start();
include("connection.php");

// 1. SEGURIDAD
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$user_actual = $_SESSION['username'];

// 2. LÓGICA DE GUARDADO: Procesar el formulario
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
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// 3. CONSULTA CON BUSCADOR (Agregado para que sea funcional)
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = $search ? "WHERE nombre_completo LIKE '%$search%' OR nit_cedula LIKE '%$search%'" : "";
$query = mysqli_query($conn, "SELECT * FROM clientes $where ORDER BY id DESC");

include("header.php");
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8 offset-md-4">
            <form action="clientes.php" method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2 shadow-sm" placeholder="Buscar por nombre o NIT..." value="<?= $search ?>">
                <button type="submit" class="btn btn-dark shadow-sm">Buscar</button>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius: 20px; border-top: 5px solid #E61E2A;">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4" style="color: #1A2B4C;">Registrar Cliente</h4>
                    <form action="clientes.php" method="POST">
                        <div class="mb-2">
                            <label class="small fw-bold">Nombre o Razón Social</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="small fw-bold">NIT o Cédula</label>
                            <input type="text" name="documento" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="small fw-bold">Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                        <div class="mb-4">
                            <label class="small fw-bold">Tipo</label>
                            <select name="tipo" class="form-select">
                                <option value="Individual">Persona Individual</option>
                                <option value="Equipo">Equipo Deportivo</option>
                                <option value="Colegio">Institución Educativa</option>
                                <option value="Empresa">Empresa</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-lg w-100 text-white fw-bold" style="background-color: #E61E2A; border-radius: 12px;">
                            GUARDAR
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark small">
                            <tr>
                                <th class="p-3">Cliente</th>
                                <th class="p-3">Documento</th>
                                <th class="p-3">Categoría</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_array($query)): ?>
                            <tr>
                                <td class="p-3">
                                    <div class="fw-bold text-uppercase"><?= $row['nombre_completo'] ?></div>
                                    <small class="text-muted">📞 <?= $row['telefono'] ?></small>
                                </td>
                                <td class="p-3">ID: <?= $row['nit_cedula'] ?></td>
                                <td class="p-3">
                                    <span class="badge rounded-pill bg-info text-dark"><?= $row['tipo_cliente'] ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>