<?php
include_once "db/config.php";

try {
    echo "Conexión exitosa a la base de datos.";
} catch (Exception $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>
