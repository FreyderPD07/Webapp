<?php
include_once "../db/config.php";
session_start();

$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    header("Location: login.php");
    exit;
}

// Obtener carrito del usuario
try {
    $stmt_carrito = $conn->prepare("
        SELECT c.CarritoID, p.ProductoID, p.Nombre, p.Precio, c.Cantidad 
        FROM carrito_compras c
        JOIN productos p ON c.ProductoID = p.ProductoID
        WHERE c.UsuarioID = :usuario_id
    ");
    $stmt_carrito->bindParam(':usuario_id', $usuario_id);
    $stmt_carrito->execute();
    $carrito = $stmt_carrito->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al obtener el carrito: " . $e->getMessage());
}

// Validar que el carrito no esté vacío
if (empty($carrito)) {
    header("Location: carrito.php");
    exit;
}

// Obtener métodos de pago
try {
    $stmt_pago = $conn->query("SELECT MetodoID, Nombre FROM metodos_pago");
    $metodos_pago = $stmt_pago->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al obtener métodos de pago: " . $e->getMessage());
}

// Obtener métodos de envío
try {
    $stmt_envio = $conn->query("SELECT MetodoEnvioID, Nombre FROM metodos_envio");
    $metodos_envio = $stmt_envio->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al obtener métodos de envío: " . $e->getMessage());
}

// Obtener departamentos
try {
    $stmt_departamentos = $conn->query("SELECT DepartamentoID, Nombre_Departamento FROM departamentos");
    $departamentos = $stmt_departamentos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al obtener departamentos: " . $e->getMessage());
}

// Procesar formulario de pago y envío
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ciudad_id = intval($_POST['ciudad_id'] ?? 0);
    $municipio = trim($_POST['municipio'] ?? '');
    $departamento_id = intval($_POST['departamento'] ?? 0);
    $direccion_exacta = trim($_POST['direccion'] ?? '');
    $metodo_envio_id = intval($_POST['metodo_envio'] ?? 0);
    $metodo_pago_id = intval($_POST['metodo_pago'] ?? 0);

    if (!$municipio || !$departamento_id || !$direccion_exacta || !$metodo_envio_id || !$metodo_pago_id) {
        $error = "Por favor completa todos los campos requeridos.";
    } else {
        try {
            // Si la ciudad no existe, agregarla
            if (!$ciudad_id) {
                $stmt_ciudad = $conn->prepare("
                    INSERT INTO ciudades (Nombre_Ciudad, DepartamentoID) 
                    VALUES (:municipio, :departamento_id)
                ");
                $stmt_ciudad->bindParam(':municipio', $municipio);
                $stmt_ciudad->bindParam(':departamento_id', $departamento_id);
                $stmt_ciudad->execute();
                $ciudad_id = $conn->lastInsertId();
            }

            // Comenzar la transacción para registrar la venta
            $conn->beginTransaction();
            $stmt_venta = $conn->prepare("
                INSERT INTO ventas (UsuarioID, FechaVenta, Total, MetodoEnvioID, CiudadID, DireccionCompleta, EstadoPago)
                VALUES (:usuario_id, NOW(), :total, :metodo_envio_id, :ciudad_id, :direccion, 'Pendiente')
            ");
            $total = array_sum(array_map(fn($item) => $item['Precio'] * $item['Cantidad'], $carrito));
            $stmt_venta->bindParam(':usuario_id', $usuario_id);
            $stmt_venta->bindParam(':total', $total);
            $stmt_venta->bindParam(':metodo_envio_id', $metodo_envio_id);
            $stmt_venta->bindParam(':ciudad_id', $ciudad_id);
            $stmt_venta->bindParam(':direccion', $direccion_exacta);
            $stmt_venta->execute();

            $venta_id = $conn->lastInsertId();

            // Insertar los productos vendidos en la tabla detalles_ventas
            foreach ($carrito as $item) {
                $stmt_detalle = $conn->prepare("
                    INSERT INTO detalles_ventas (VentaID, ProductoID, Cantidad, Precio)
                    VALUES (:venta_id, :producto_id, :cantidad, :precio)
                ");
                $stmt_detalle->bindParam(':venta_id', $venta_id);
                $stmt_detalle->bindParam(':producto_id', $item['ProductoID']);
                $stmt_detalle->bindParam(':cantidad', $item['Cantidad']);
                $stmt_detalle->bindParam(':precio', $item['Precio']);
                $stmt_detalle->execute();
            }

            // Eliminar productos del carrito
            $stmt_eliminar_carrito = $conn->prepare("DELETE FROM carrito_compras WHERE UsuarioID = :usuario_id");
            $stmt_eliminar_carrito->bindParam(':usuario_id', $usuario_id);
            $stmt_eliminar_carrito->execute();

            // Confirmar la transacción
            $conn->commit();

            // Redirigir a la página de confirmación
            header("Location: confirmacion.php?venta_id=$venta_id");
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error al procesar la compra: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="../assets/css/checkout.css">
</head>
<body>
    <header class="header">
        <div class="logo">Mi Tienda</div>
    </header>

    <section class="checkout-section">
        <h2>Finalizar Compra</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <h3>Tu Carrito</h3>
            <ul>
                <?php foreach ($carrito as $item): ?>
                    <li><?= htmlspecialchars($item['Nombre']) ?> (x<?= htmlspecialchars($item['Cantidad']) ?>) - $<?= number_format($item['Precio'] * $item['Cantidad'], 2) ?></li>
                <?php endforeach; ?>
            </ul>

            <h3>Dirección</h3>
            <label for="departamento">Departamento:</label>
            <select name="departamento" id="departamento" required>
                <option value="">Seleccione un departamento</option>
                <?php foreach ($departamentos as $departamento): ?>
                    <option value="<?= htmlspecialchars($departamento['DepartamentoID']) ?>"><?= htmlspecialchars($departamento['Nombre_Departamento']) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="municipio">Municipio:</label>
            <input type="text" name="municipio" id="municipio" placeholder="Escriba su municipio" required autocomplete="off">
            <input type="hidden" name="ciudad_id" id="ciudad_id">
            <div id="municipio-suggestions" class="suggestions"></div>

            <label for="direccion">Dirección Exacta:</label>
            <input type="text" name="direccion" id="direccion" required>

            <h3>Método de Pago</h3>
            <select name="metodo_pago" required>
                <option value="">Seleccione un método</option>
                <?php foreach ($metodos_pago as $pago): ?>
                    <option value="<?= htmlspecialchars($pago['MetodoID']) ?>"><?= htmlspecialchars($pago['Nombre']) ?></option>
                <?php endforeach; ?>
            </select>

            <h3>Método de Envío</h3>
            <select name="metodo_envio" required>
                <option value="">Seleccione un método</option>
                <?php foreach ($metodos_envio as $envio): ?>
                    <option value="<?= htmlspecialchars($envio['MetodoEnvioID']) ?>"><?= htmlspecialchars($envio['Nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">Finalizar Compra</button>
            <a href="carrito.php" class="btn btn-secondary">Volver al Carrito</a>
        </form>
    </section>

    <script src="../assets/js/checkout.js"></script>
</body>
</html>
