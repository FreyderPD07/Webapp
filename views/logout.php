<?php
session_start();

// Destruir todas las variables de sesión
$_SESSION = [];

// Destruir la sesión
session_destroy();

// Redirigir al usuario a la página de inicio de sesión o página principal
header("Location: home.php");
exit;
?>
