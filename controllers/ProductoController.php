<?php
// Ruta: C:\xampp\htdocs\unideportes-system\controllers\ProductoController.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

// 1. Configuración de la Base de Datos
$host = "localhost";
$db_name = "unideportes"; 
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

// 2. Procesar la petición POST (Guardar Producto)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    // ✅ VALIDACIÓN CORREGIDA: ahora espera los campos correctos
    if (!empty($data->nombre) && !empty($data->precio) && !empty($data->referencia)) {
        try {
            // ✅ INSERT CORREGIDO: usa los nombres reales de las columnas
            $query = "INSERT INTO productos 
                      (nombre, referencia, categoria, color, material, genero, estado, descripcion, talla, stock, unidad, precio) 
                      VALUES 
                      (:nombre, :referencia, :categoria, :color, :material, :genero, :estado, :descripcion, :talla, :stock, :unidad, :precio)";
            
            $stmt = $conn->prepare($query);

            // ✅ BIND PARAM CORREGIDO: mapea cada campo correctamente
            $nombre = $data->nombre;
            $referencia = $data->referencia;
            $categoria = $data->categoria ?? null;
            $color = $data->color ?? null;
            $material = $data->material ?? null;
            $genero = $data->genero ?? null;
            $estado = $data->estado ?? 'activo';
            $descripcion = $data->descripcion ?? null;
            $talla = $data->talla ?? 'Única';
            $stock = $data->stock ?? 0;
            $unidad = $data->unidad ?? 'Unidad';
            $precio = $data->precio;

            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':referencia', $referencia);
            $stmt->bindParam(':categoria', $categoria);
            $stmt->bindParam(':color', $color);
            $stmt->bindParam(':material', $material);
            $stmt->bindParam(':genero', $genero);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':talla', $talla);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':unidad', $unidad);
            $stmt->bindParam(':precio', $precio);

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode([
                    "status" => "success", 
                    "message" => "Producto registrado con éxito.",
                    "id" => $conn->lastInsertId()
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "No se pudo ejecutar la consulta."]);
            }
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "No se pudo guardar: " . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "Datos incompletos. Se requiere: nombre, referencia y precio."
        ]);
    }

// 3. Procesar la petición GET (Listar Productos)
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $query = "SELECT * FROM productos ORDER BY id DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($productos);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Error al consultar: " . $e->getMessage()]);
    }
}
?>