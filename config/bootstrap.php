<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/connection.php';

function app(): PDO {
    static $conn;
    if (!$conn) {
        $conn = connection();
    }
    return $conn;
}

function require_login(array $roles = ['vendedor', 'colaborador', 'admin']): void {
    if (empty($_SESSION['username']) || !in_array($_SESSION['role'] ?? '', $roles, true)) {
        header('Location: /unideportes-system/public/index.php?error=acceso_denegado');
        exit();
    }
}

function request(string $key, $default = '') {
    return $_REQUEST[$key] ?? $default;
}

function clean($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: $url");
    exit();
}
