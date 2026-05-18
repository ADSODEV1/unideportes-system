<?php
// Load the connection function
require_once 'config/connection.php';

try {
    // Get the connection
    $conn = connection();
    
    // Check if column exists
    $checkResult = $conn->query("
        SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME='clientes' AND COLUMN_NAME='estado' AND TABLE_SCHEMA='unideportes'
    ");
    
    if ($checkResult && $checkResult->num_rows > 0) {
        // Column exists, verify it
        $verifyResult = $conn->query("
            SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME='clientes' AND COLUMN_NAME='estado' AND TABLE_SCHEMA='unideportes'
        ");
        
        if ($verifyResult && $verifyResult->num_rows > 0) {
            $row = $verifyResult->fetch_assoc();
            echo "SUCCESS: Column 'estado' already exists\n";
            echo "Column Details: ";
            echo "Name=" . $row['COLUMN_NAME'] . ", Type=" . $row['COLUMN_TYPE'] . ", Default=" . $row['COLUMN_DEFAULT'] . "\n";
        } else {
            echo "ERROR: Could not verify column details\n";
        }
    } else {
        // Column doesn't exist, add it
        $alterResult = $conn->query("ALTER TABLE clientes ADD COLUMN estado ENUM('activo', 'inactivo') DEFAULT 'activo'");
        
        if ($alterResult) {
            // Verify it was added
            $verifyResult = $conn->query("
                SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME='clientes' AND COLUMN_NAME='estado' AND TABLE_SCHEMA='unideportes'
            ");
            
            if ($verifyResult && $verifyResult->num_rows > 0) {
                $row = $verifyResult->fetch_assoc();
                echo "SUCCESS: Column 'estado' added successfully\n";
                echo "Column Details: ";
                echo "Name=" . $row['COLUMN_NAME'] . ", Type=" . $row['COLUMN_TYPE'] . ", Default=" . $row['COLUMN_DEFAULT'] . "\n";
            } else {
                echo "ERROR: Could not verify column after adding\n";
            }
        } else {
            echo "ERROR: " . $conn->error . "\n";
        }
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
