<?php
// Incluir archivo de conexión
include_once "../db/config.php";

// Inicializar sesión
session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    echo "Debes iniciar sesión para añadir productos al carrito.";
    exit;
}

// Obtener datos del producto
$usuario_id = $_SESSION['usuario_id'];
$producto_id = $_POST['producto_id'] ?? null;
$cantidad = $_POST['cantidad'] ?? 1;

if ($producto_id) {
    try {
        // Verificar si el producto ya está en el carrito
        $stmt = $conn->prepare("SELECT * FROM carrito_compras WHERE UsuarioID = :usuario_id AND ProductoID = :producto_id");
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':producto_id', $producto_id);
        $stmt->execute();
        $carrito_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($carrito_item) {
            // Actualizar cantidad si ya existe
            $nueva_cantidad = $carrito_item['Cantidad'] + $cantidad;
            $update_stmt = $conn->prepare("UPDATE carrito_compras SET Cantidad = :cantidad WHERE CarritoID = :carrito_id");
            $update_stmt->bindParam(':cantidad', $nueva_cantidad);
            $update_stmt->bindParam(':carrito_id', $carrito_item['CarritoID']);
            $update_stmt->execute();
        } else {
            // Insertar nuevo producto al carrito
            $insert_stmt = $conn->prepare("INSERT INTO carrito_compras (UsuarioID, ProductoID, Cantidad) VALUES (:usuario_id, :producto_id, :cantidad)");
            $insert_stmt->bindParam(':usuario_id', $usuario_id);
            $insert_stmt->bindParam(':producto_id', $producto_id);
            $insert_stmt->bindParam(':cantidad', $cantidad);
            $insert_stmt->execute();
        }

        echo "Producto agregado al carrito con éxito.";
    } catch (Exception $e) {
        echo "Error al agregar producto al carrito: " . $e->getMessage();
    }
} else {
    echo "Producto inválido.";
}
?>
