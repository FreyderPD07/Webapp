<?php
// Configuración de conexión a la base de datos
$host = "localhost"; // Cambiar si es necesario
$dbname = "tiendaonline"; // Nombre de tu base de datos
$username = "root"; // Usuario de la base de datos
$password = ""; // Contraseña del usuario

try {
    // Crear una nueva conexión PDO
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Configurar PDO para manejar errores
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo ""; // Mensaje para depuración (opcional)
} catch (PDOException $e) {
    // Capturar errores y mostrarlos
    die("Error al conectar a la base de datos: " . $e->getMessage());
}
?>
