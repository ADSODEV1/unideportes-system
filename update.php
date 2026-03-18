<?php
include("connection.php");

// 1. ¿A quién vamos a editar? (Viene del enlace de admin_user.php)
$id = $_GET['id'];

// 2. Buscamos sus datos actuales
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc(); // Aquí ya tenemos los datos en $row
?>

<h3>Editar Usuario: <?php echo $row['username']; ?></h3>

<form action="edit_user.php" method="POST">
    
    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

    <label>Nombre:</label>
    <input type="text" name="name" value="<?php echo $row['name']; ?>">
    
    <label>Apellido:</label>
    <input type="text" name="lastname" value="<?php echo $row['lastname']; ?>">
    
    <label>Usuario:</label>
    <input type="text" name="username" value="<?php echo $row['username']; ?>">

    <label>Email:</label>
    <input type="email" name="email" value="<?php echo $row['email']; ?>">

    <button type="submit">ACTUALIZAR DATOS</button>
</form>