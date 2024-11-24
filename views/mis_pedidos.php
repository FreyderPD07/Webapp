<?php
include_once "../db/config.php";
session_start();

// Validar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

try {
    // Consultar los pedidos del usuario
    $stmt = $conn->prepare("
        SELECT v.VentaID, v.FechaVenta, v.Total, v.Estado
        FROM ventas v
        WHERE v.UsuarioID = :usuario_id
        ORDER BY v.FechaVenta DESC
    ");
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error al obtener los pedidos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos</title>
    <link rel="stylesheet" href="../assets/css/mis_pedidos.css">
</head>
<body>
    <header class="header">
        <div class="logo">Mi Tienda</div>
        <nav class="navbar">
            <button class="btn" onclick="window.location.href='home.php'">Inicio</button>
            <button class="btn" onclick="window.location.href='carrito.php'">Carrito</button>
            <button class="btn logout" onclick="window.location.href='logout.php'">Cerrar Sesión</button>
        </nav>
    </header>

    <section class="orders-section">
        <h2>Mis Pedidos</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php elseif (empty($pedidos)): ?>
            <p>No has realizado ningún pedido.</p>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($pedidos as $pedido): ?>
                    <div class="order-card">
                        <h3>Pedido #<?= htmlspecialchars($pedido['VentaID']) ?></h3>
                        <p><strong>Fecha:</strong> <?= htmlspecialchars($pedido['FechaVenta']) ?></p>
                        <p><strong>Total:</strong> $<?= number_format($pedido['Total'], 2) ?></p>
                        <p><strong>Estado:</strong> <?= htmlspecialchars($pedido['Estado']) ?></p>
                        <button class="btn details" onclick="window.location.href='detalle_pedido.php?venta_id=<?= $pedido['VentaID'] ?>'">Ver Detalles</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</body>
</html>
