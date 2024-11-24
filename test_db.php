<?php
include_once "db/config.php";

if (isset($conn)) {
    echo "Conexión a la base de datos establecida correctamente.";
} else {
    echo "Error al conectar a la base de datos.";
}
?>
