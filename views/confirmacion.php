<?php
include_once "../db/config.php";
session_start();

$venta_id = $_GET['venta_id'] ?? null;

if (!$venta_id) {
    echo "<p>ID de venta no especificado.</p><a href='home.php'>Volver a la tienda</a>";
    exit;
}

try {
    // Obtener detalles de la venta
    $stmt_venta = $conn->prepare("
        SELECT v.VentaID, v.FechaVenta, v.Total, m.Nombre AS MetodoPago, e.Nombre AS MetodoEnvio
        FROM ventas v
        LEFT JOIN pagos p ON v.VentaID = p.VentaID
        LEFT JOIN metodos_pago m ON p.MetodoID = m.MetodoID
        LEFT JOIN metodos_envio e ON v.MetodoEnvioID = e.MetodoEnvioID
        WHERE v.VentaID = :venta_id
    ");
    $stmt_venta->bindParam(':venta_id', $venta_id, PDO::PARAM_INT);
    $stmt_venta->execute();
    $venta = $stmt_venta->fetch(PDO::FETCH_ASSOC);

    if (!$venta || empty($venta['VentaID'])) {
        throw new Exception("No se encontró información de la venta con el ID especificado.");
    }

    // Obtener detalles de los productos vendidos
    $stmt_detalles = $conn->prepare("
        SELECT p.Nombre, dv.Cantidad, dv.Precio 
        FROM detalles_ventas dv
        JOIN productos p ON dv.ProductoID = p.ProductoID
        WHERE dv.VentaID = :venta_id
    ");
    $stmt_detalles->bindParam(':venta_id', $venta_id, PDO::PARAM_INT);
    $stmt_detalles->execute();
    $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

    if (!$detalles) {
        throw new Exception("No se encontraron productos asociados a esta venta.");
    }
} catch (Exception $e) {
    echo "<p>Error al obtener los detalles de la venta: " . htmlspecialchars($e->getMessage()) . "</p><a href='home.php'>Volver a la tienda</a>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Compra</title>
    <link rel="stylesheet" href="../assets/css/confirmacion.css">
</head>
<body>
    <header class="header">
        <div class="logo">Mi Tienda</div>
    </header>

    <section class="confirmation-section">
        <h2>¡Gracias por tu compra!</h2>
        <p><strong>Número de Venta:</strong> <?= htmlspecialchars($venta['VentaID']) ?></p>
        <p><strong>Fecha:</strong> <?= htmlspecialchars($venta['FechaVenta']) ?></p>
        <p><strong>Método de Pago:</strong> <?= htmlspecialchars($venta['MetodoPago'] ?? 'No especificado') ?></p>
        <p><strong>Método de Envío:</strong> <?= htmlspecialchars($venta['MetodoEnvio'] ?? 'No especificado') ?></p>
        <p><strong>Total Pagado:</strong> $<?= number_format($venta['Total'], 2) ?></p>

        <h3>Productos:</h3>
        <ul>
            <?php foreach ($detalles as $detalle): ?>
                <li><?= htmlspecialchars($detalle['Nombre']) ?> (x<?= htmlspecialchars($detalle['Cantidad']) ?>) - $<?= number_format($detalle['Precio'] * $detalle['Cantidad'], 2) ?></li>
            <?php endforeach; ?>
        </ul>

        <div class="buttons">
            <a href="factura.php?venta_id=<?= urlencode($venta['VentaID']) ?>" class="btn">Descargar Factura</a>
            <a href="home.php" class="btn">Volver a la Tienda</a>
        </div>
    </section>
</body>
</html>
