<?php
include_once "../db/config.php";
session_start();

$usuario_id = $_SESSION['usuario_id'] ?? null;

// Eliminar producto del carrito si se envía una solicitud POST con `delete_id`
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $carrito_id = $_POST['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM carrito_compras WHERE CarritoID = :carrito_id AND UsuarioID = :usuario_id");
        $stmt->bindParam(':carrito_id', $carrito_id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        header("Location: carrito.php");
        exit;
    } catch (Exception $e) {
        $error = "Error al eliminar el producto: " . $e->getMessage();
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
    $stmt->bindParam(':usuario_id', $usuario_id);
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
            <button class="btn login" onclick="window.location.href='login.php'">Iniciar Sesión</button>
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
