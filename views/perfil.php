<?php
include_once "../db/config.php";
session_start();

// Verificar si el usuario está autenticado
$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    die("<p>Error: Usuario no autenticado. Por favor, inicia sesión.</p>");
}

try {
    // Consultar información del usuario
    $stmt_usuario = $conn->prepare("
        SELECT Nombre, Email 
        FROM usuarios 
        WHERE UsuarioID = :usuario_id
    ");
    $stmt_usuario->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt_usuario->execute();
    $usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

    // Consultar dirección del usuario (última dirección registrada en ventas)
    $stmt_direccion = $conn->prepare("
        SELECT DireccionCompleta 
        FROM ventas 
        WHERE UsuarioID = :usuario_id 
        ORDER BY FechaVenta DESC 
        LIMIT 1
    ");
    $stmt_direccion->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt_direccion->execute();
    $direccion = $stmt_direccion->fetch(PDO::FETCH_ASSOC)['DireccionCompleta'] ?? 'No registrada';

    // Consultar pedidos del usuario
    $stmt_pedidos = $conn->prepare("
        SELECT VentaID, FechaVenta, Total 
        FROM ventas 
        WHERE UsuarioID = :usuario_id 
        ORDER BY FechaVenta DESC
    ");
    $stmt_pedidos->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt_pedidos->execute();
    $pedidos = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);

    // Consultar facturas del usuario
    $stmt_facturas = $conn->prepare("
        SELECT f.FacturaID, f.FechaFactura, f.MontoTotal, f.Estado, v.VentaID 
        FROM facturas f
        INNER JOIN ventas v ON f.VentaID = v.VentaID
        WHERE v.UsuarioID = :usuario_id
        ORDER BY f.FechaFactura DESC
    ");
    $stmt_facturas->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt_facturas->execute();
    $facturas = $stmt_facturas->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("<p>Error al cargar los datos: " . htmlspecialchars($e->getMessage()) . "</p>");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario</title>
    <link rel="stylesheet" href="../assets/css/perfil.css">
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #555;
            text-align: center;
        }
        .tabs {
            display: flex;
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
        }
        .tab {
            flex: 1;
            text-align: center;
            padding: 10px;
            cursor: pointer;
            background-color: #f4f4f9;
            border: 1px solid #ddd;
        }
        .tab.active {
            background-color: #007bff;
            color: #fff;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .table th, .table td {
            text-align: left;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .table th {
            background-color: #f4f4f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="btn cart" onclick="window.location.href='home.php'">Home</button>
        <h1>Mi Perfil</h1>

        <div class="tabs">
            <div class="tab active" data-tab="perfil">Perfil</div>
            <div class="tab" data-tab="pedidos">Pedidos</div>
            <div class="tab" data-tab="facturas">Facturas</div>
        </div>

        <!-- Perfil -->
        <div class="tab-content active" id="perfil">
            <h2>Información Personal</h2>
            <form action="update_perfil.php" method="POST">
                <p><strong>Nombre:</strong> <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['Nombre']) ?>" required></p>
                <p><strong>Email:</strong> <input type="email" name="email" value="<?= htmlspecialchars($usuario['Email']) ?>" required></p>
                <p><strong>Dirección:</strong> <input type="text" name="direccion" value="<?= htmlspecialchars($direccion) ?>"></p>
                <button type="submit" class="btn">Guardar Cambios</button>
            </form>
        </div>

        <!-- Pedidos -->
        <div class="tab-content" id="pedidos">
            <h2>Mis Pedidos</h2>
            <?php if (!empty($pedidos)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID Pedido</th>
                            <th>Fecha</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><?= htmlspecialchars($pedido['VentaID']) ?></td>
                                <td><?= htmlspecialchars($pedido['FechaVenta']) ?></td>
                                <td>$<?= number_format($pedido['Total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No tienes pedidos registrados.</p>
            <?php endif; ?>
        </div>

        <!-- Facturas -->
        <div class="tab-content" id="facturas">
            <h2>Mis Facturas</h2>
            <?php if (!empty($facturas)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID Factura</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facturas as $factura): ?>
                            <tr>
                                <td><?= htmlspecialchars($factura['FacturaID']) ?></td>
                                <td><?= htmlspecialchars($factura['FechaFactura']) ?></td>
                                <td>$<?= number_format($factura['MontoTotal'], 2) ?></td>
                                <td><?= htmlspecialchars($factura['Estado']) ?></td>
                                <td>
                                    <a href="factura.php?venta_id=<?= htmlspecialchars($factura['VentaID']) ?>" class="btn">Ver Factura</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No tienes facturas registradas.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Tabs functionality
        const tabs = document.querySelectorAll('.tab');
        const contents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));

                tab.classList.add('active');
                document.getElementById(tab.dataset.tab).classList.add('active');
            });
        });
    </script>
</body>
</html>
