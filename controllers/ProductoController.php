<?php
// \controllers\ProductoController.php

// Usar la configuración existente del proyecto en lugar de credenciales hardcodeadas
require_once __DIR__ . '/../config/bootstrap.php';

// CORS: restringir a orígenes específicos (más seguro que "*")
$allowedOrigins = [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:8080',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: " . $origin);
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obtener conexión PDO desde la configuración centralizada del proyecto
try {
    $conn = app();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error de conexión: " . $e->getMessage()]);
    exit();
}

// Procesar la petición POST (Guardar Producto)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    // VALIDACIÓN: espera los campos correctos
    if (!empty($data->nombre) && !empty($data->precio) && !empty($data->referencia)) {
        try {
            $query = "INSERT INTO productos 
                      (nombre, referencia, categoria, color, material, genero, estado, descripcion, talla, stock, unidad, precio) 
                      VALUES 
                      (:nombre, :referencia, :categoria, :color, :material, :genero, :estado, :descripcion, :talla, :stock, :unidad, :precio)";
            
            $stmt = $conn->prepare($query);

            $nombre = $data->nombre;
            $referencia = $data->referencia;
            $categoria = $data->categoria ?? null;
            $color = $data->color ?? null;
            $material = $data->material ?? null;
            $genero = $data->genero ?? null;
            $estado = $data->estado ?? 'activo';
            $descripcion = $data->descripcion ?? null;
            $talla = $data->talla ?? 'Única';
            $stock = (int) ($data->stock ?? 0);
            $unidad = $data->unidad ?? 'Unidad';
            $precio = (float) $data->precio;

            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':referencia', $referencia);
            $stmt->bindParam(':categoria', $categoria);
            $stmt->bindParam(':color', $color);
            $stmt->bindParam(':material', $material);
            $stmt->bindParam(':genero', $genero);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':talla', $talla);
            $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
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
        } catch (Exception $e) {
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
// Procesar la petición GET (Listar Productos)
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $query = "SELECT * FROM productos ORDER BY id DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode($productos);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Error al consultar: " . $e->getMessage()]);
    }
}