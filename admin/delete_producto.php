<?php
// Incluir archivo de conexión
include_once __DIR__ . "/../db/config.php";

// Iniciar sesión
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'Administrador') {
    header("Location: ../views/login.php");
    exit;
}

// Verificar si se recibe el ID del producto por GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $producto_id = intval($_GET['id']);

    try {
        // Preparar la consulta para eliminar el producto
        $stmt = $conn->prepare("DELETE FROM productos WHERE ProductoID = :producto_id");
        $stmt->bindParam(':producto_id', $producto_id);
        $stmt->execute();

        // Redirigir al panel de productos con mensaje de éxito
        header("Location: admin_productos.php?delete=success");
        exit;
    } catch (Exception $e) {
        $error = "Error al eliminar el producto: " . $e->getMessage();
    }
} else {
    // Si no se recibe un ID válido, redirigir con un mensaje de error
    header("Location: admin_productos.php?delete=error");
    exit;
}
