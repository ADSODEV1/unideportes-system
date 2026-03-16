<?php
include("connection.php");

// 1. Verificamos que el ID exista en la URL (ej: delete_user.php?id=5)
if (isset($_GET["id"])) {
    $id = $_GET["id"];

    // 2. Ejecutamos la eliminación en la tabla correcta 'usuarios'
    $sql = "DELETE FROM usuarios WHERE id = '$id'";
    $query = mysqli_query($conn, $sql);

    // 3. Redirección con mensaje de confirmación
    if($query){
        header("Location: admin_user.php?msj=eliminado");
    } else {
        echo "Error al eliminar: " . mysqli_error($conn);
    }
} else {
    // Si alguien entra al archivo sin un ID, lo devolvemos al panel
    header("Location: admin_user.php");
}
?>