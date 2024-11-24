<?php
// Incluir archivo de conexión
include_once "../db/config.php";

// Inicializar sesión
session_start();

// Configurar encabezado para respuestas JSON
header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Debes iniciar sesión para añadir productos al carrito.'
    ]);
    exit;
}

// Decodificar datos enviados por la solicitud (si es JSON)
$input = json_decode(file_get_contents('php://input'), true);

// Obtener datos del usuario y producto
$usuario_id = $_SESSION['usuario_id'];
$producto_id = $input['producto_id'] ?? null;
$cantidad = $input['cantidad'] ?? 1;

// Validar que se haya enviado un producto
if (!$producto_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Producto inválido.'
    ]);
    exit;
}

try {
    // Verificar si el producto ya está en el carrito
    $stmt = $conn->prepare("
        SELECT * 
        FROM carrito_compras 
        WHERE UsuarioID = :usuario_id AND ProductoID = :producto_id
    ");
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':producto_id', $producto_id, PDO::PARAM_INT);
    $stmt->execute();
    $carrito_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($carrito_item) {
        // Actualizar cantidad si ya existe
        $nueva_cantidad = $carrito_item['Cantidad'] + $cantidad;
        $update_stmt = $conn->prepare("
            UPDATE carrito_compras 
            SET Cantidad = :cantidad 
            WHERE CarritoID = :carrito_id
        ");
        $update_stmt->bindParam(':cantidad', $nueva_cantidad, PDO::PARAM_INT);
        $update_stmt->bindParam(':carrito_id', $carrito_item['CarritoID'], PDO::PARAM_INT);
        $update_stmt->execute();
    } else {
        // Insertar nuevo producto al carrito
        $insert_stmt = $conn->prepare("
            INSERT INTO carrito_compras (UsuarioID, ProductoID, Cantidad) 
            VALUES (:usuario_id, :producto_id, :cantidad)
        ");
        $insert_stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $insert_stmt->bindParam(':producto_id', $producto_id, PDO::PARAM_INT);
        $insert_stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
        $insert_stmt->execute();
    }

    // Respuesta de éxito
    echo json_encode([
        'success' => true,
        'message' => 'Producto agregado al carrito con éxito.'
    ]);
} catch (Exception $e) {
    // Manejo de errores en la base de datos
    echo json_encode([
        'success' => false,
        'message' => 'Error al agregar producto al carrito: ' . $e->getMessage()
    ]);
}
?>
