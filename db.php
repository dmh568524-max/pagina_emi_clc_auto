<?php
// Configuración de la Base de Datos para EMILIANO CLC AUTO IMPORT

$db_host = 'localhost';
$db_name = 'clc_auto_import';
$db_user = 'root';
$db_pass = ''; // Por defecto en XAMPP suele estar vacío

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Si la base de datos no existe o hay error de conexión
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>
