<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Usuario por defecto
define('DB_PASS', 'root123');          // Contraseña (vacía por defecto en XAMPP)
define('DB_NAME', 'sistema_pedidos');
// Crear conexión
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Configurar charset
$conn->set_charset("utf8");
?>