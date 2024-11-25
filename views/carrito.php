<?php
include_once "../db/config.php";
session_start();

$usuario_id = $_SESSION['usuario_id'] ?? null;

// Procesar eliminación o reducción de productos del carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'], $_POST['cantidad'])) {
    $carrito_id = $_POST['delete_id'];
    $cantidad_a_eliminar = intval($_POST['cantidad']);

    try {
        // Verificar la cantidad actual en el carrito
        $stmt = $conn->prepare("SELECT Cantidad FROM carrito_compras WHERE CarritoID = :carrito_id AND UsuarioID = :usuario_id");
        $stmt->bindParam(':carrito_id', $carrito_id, PDO::PARAM_INT);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        $carrito_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($carrito_item) {
            $cantidad_actual = intval($carrito_item['Cantidad']);

            if ($cantidad_a_eliminar >= $cantidad_actual) {
                // Eliminar el producto si la cantidad a eliminar es igual o mayor a la actual
                $stmt = $conn->prepare("DELETE FROM carrito_compras WHERE CarritoID = :carrito_id AND UsuarioID = :usuario_id");
                $stmt->bindParam(':carrito_id', $carrito_id, PDO::PARAM_INT);
                $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                // Reducir la cantidad si es menor
                $nueva_cantidad = $cantidad_actual - $cantidad_a_eliminar;
                $stmt = $conn->prepare("UPDATE carrito_compras SET Cantidad = :nueva_cantidad WHERE CarritoID = :carrito_id AND UsuarioID = :usuario_id");
                $stmt->bindParam(':nueva_cantidad', $nueva_cantidad, PDO::PARAM_INT);
                $stmt->bindParam(':carrito_id', $carrito_id, PDO::PARAM_INT);
                $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }

        header("Location: carrito.php");
        exit;
    } catch (Exception $e) {
        $error = "Error al procesar la solicitud: " . $e->getMessage();
    }
}

// Obtener los productos del carrito
try {
    $stmt = $conn->prepare("
        SELECT c.CarritoID, p.Nombre, p.Precio, c.Cantidad 
        FROM carrito_compras c
        JOIN productos p ON c.ProductoID = p.ProductoID
        WHERE c.UsuarioID = :usuario_id
    ");
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $carrito = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras</title>
    <link rel="stylesheet" href="../assets/css/carrito.css">
</head>
<body>
    <header class="header">
        <div class="logo">Mi Tienda</div>
        <nav class="navbar">
            <button class="btn cart" onclick="window.location.href='home.php'">Home</button>
        </nav>
    </header>

    <section class="cart-section">
        <h2>Tu Carrito de Compras</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php elseif (empty($carrito)): ?>
            <p>No hay productos en el carrito.</p>
        <?php else: ?>
            <div id="cart-items">
                <?php foreach ($carrito as $item): ?>
                    <div class="cart-item">
                        <p><strong><?= htmlspecialchars($item['Nombre']) ?></strong></p>
                        <p>Precio: $<?= number_format($item['Precio'], 2) ?></p>
                        <p>Cantidad: <?= htmlspecialchars($item['Cantidad']) ?></p>
                        <p>Total: $<?= number_format($item['Precio'] * $item['Cantidad'], 2) ?></p>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?= htmlspecialchars($item['CarritoID']) ?>">
                            <label for="cantidad-<?= $item['CarritoID'] ?>">Cantidad a eliminar:</label>
                            <input type="number" name="cantidad" id="cantidad-<?= $item['CarritoID'] ?>" min="1" max="<?= htmlspecialchars($item['Cantidad']) ?>" value="1" required>
                            <button type="submit" class="btn delete">Eliminar</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="cart-summary">
                <?php
                $total = array_sum(array_map(function ($item) {
                    return $item['Precio'] * $item['Cantidad'];
                }, $carrito));
                ?>
                <p><strong>Total: $<?= number_format($total, 2) ?></strong></p>
                <button class="btn checkout" onclick="window.location.href='checkout.php'">Ir a Pagar</button>
            </div>
        <?php endif; ?>
    </section>
</body>
</html>
