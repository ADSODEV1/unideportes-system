<?php
// Ruta: C:\xampp\htdocs\unideportes-system\controllers\ProductoController.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// 1. Configuración rápida de la Base de Datos
$host = "localhost";
$db_name = "unideportes"; // <- Asegúrate de que coincida con el nombre de tu BD
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error de conexión: " . $e->getMessage()]);
    exit();
}

// 2. Procesar la petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    // Validación mínima elemental
    if (!empty($data->nombre_producto) && !empty($data->precio)) {
        try {
            $query = "INSERT INTO productos (nombre_producto, precio, stock) VALUES (:nombre, :precio, :stock)";
            $stmt = $conn->prepare($query);

            $stmt->bindParam(':nombre', $data->nombre_producto);
            $stmt->bindParam(':precio', $data->precio);
            $stmt->bindParam(':stock', $data->stock);

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "message" => "Producto integrado con éxito."]);
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "No se pudo guardar: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Datos incompletos en el controlador."]);
    }
}
?>