<?php
session_start(); // INICIA EL SISTEMA
include("connection.php");

if (!empty($_POST['username']) && !empty($_POST['password'])) {
    
    $user_input = $_POST['username'];
    $pass_input = $_POST['password'];

    //BUSCAR AL USUARIO (Sentencia Preparada para seguridad)
    $sql = "SELECT id, username, password, role FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_input);
    $stmt->execute();
    
    //OBTENER EL RESULTADO
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // 4. VERIFICAR LA CONTRASEÑA
        // Por ahora comparamos texto plano, pero luego usaremos password_verify
        if ($pass_input == $row['password']) {
            
            // 5. CREAR LA SESIÓN (Guardamos sus datos en la "memoria" del servidor)
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['nombre']  = $row['username'];
            $_SESSION['rol']     = $row['role'];

            header("Location: dashboard.php"); // Pa' dentro
            exit();
        } else {
            header("Location: index.php?error=clave_incorrecta");
        }
    } else {
        header("Location: index.php?error=usuario_no_existe");
    }

} else {
    header("Location: index.php?msj=vacio");
}
?>
