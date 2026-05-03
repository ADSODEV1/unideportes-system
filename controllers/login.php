<?php
session_start(); // INICIA EL SISTEMA
require_once __DIR__ . '/../config/connection.php';

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
        // 4. VERIFICAR LA CONTRASEÑA (usando password_verify para hashes)
        if (password_verify($pass_input, $row['password'])) {
            
            // 5. CREAR LA SESIÓN (Guardamos sus datos en la "memoria" del servidor)
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['nombre']  = $row['username'];
            $_SESSION['rol']     = $row['role'];

            header("Location: ../views/panel_admin.php"); // Redirigir al panel
            exit();
        } else {
            header("Location: ../public/index.php?error=clave_incorrecta");
        }
    } else {
        header("Location: ../public/index.php?error=usuario_no_existe");
    }

} else {
    header("Location: ../public/index.php?msj=vacio");
}
?>
