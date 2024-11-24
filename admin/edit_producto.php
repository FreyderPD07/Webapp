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

// Obtener el ID del producto por GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $producto_id = intval($_GET['id']);

    // Obtener los datos del producto
    try {
        $stmt = $conn->prepare("SELECT * FROM productos WHERE ProductoID = :producto_id");
        $stmt->bindParam(':producto_id', $producto_id);
        $stmt->execute();
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            header("Location: admin_productos.php?edit=notfound");
            exit;
        }
    } catch (Exception $e) {
        die("Error al obtener el producto: " . $e->getMessage());
    }

    // Procesar formulario al enviar
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = $_POST['nombre'] ?? $producto['Nombre'];
        $precio = $_POST['precio'] ?? $producto['Precio'];
        $categoria_id = $_POST['categoria_id'] ?? $producto['CategoriaID'];
        $stock_minimo = $_POST['stock_minimo'] ?? $producto['StockMinimo'];
        $stock_maximo = $_POST['stock_maximo'] ?? $producto['StockMaximo'];
        $activo = isset($_POST['activo']) ? 1 : 0;

        try {
            // Actualizar datos del producto
            $stmt = $conn->prepare("
                UPDATE productos 
                SET Nombre = :nombre, CategoriaID = :categoria_id, Precio = :precio, StockMinimo = :stock_minimo, StockMaximo = :stock_maximo, Activo = :activo
                WHERE ProductoID = :producto_id
            ");
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':categoria_id', $categoria_id);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':stock_minimo', $stock_minimo);
            $stmt->bindParam(':stock_maximo', $stock_maximo);
            $stmt->bindParam(':activo', $activo);
            $stmt->bindParam(':producto_id', $producto_id);
            $stmt->execute();

            // Redirigir al panel de productos
            header("Location: admin_productos.php?edit=success");
            exit;
        } catch (Exception $e) {
            $error = "Error al actualizar el producto: " . $e->getMessage();
        }
    }
} else {
    // Si no se recibe un ID válido
    header("Location: admin_productos.php?edit=error");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto</title>
    <link rel="stylesheet" href="../assets/css/edit_producto.css">
</head>
<body>
    <header class="header-admin">
        <h1>Editar Producto</h1>
        <nav>
            <button onclick="window.location.href='admin_productos.php'">Volver al Panel</button>
        </nav>
    </header>
    <main>
        <h2>Modificar Producto</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <div>
                <label for="nombre">Nombre del Producto:</label>
                <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($producto['Nombre']) ?>" required>
            </div>
            <div>
                <label for="precio">Precio:</label>
                <input type="number" id="precio" name="precio" step="0.01" value="<?= htmlspecialchars($producto['Precio']) ?>" required>
            </div>
            <div>
                <label for="categoria_id">Categoría:</label>
                <select id="categoria_id" name="categoria_id" required>
                    <?php
                    $query_categorias = $conn->query("SELECT CategoriaID, Nombre_Categoria FROM categorias WHERE Activo = 1");
                    $categorias = $query_categorias->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($categorias as $categoria):
                    ?>
                        <option value="<?= htmlspecialchars($categoria['CategoriaID']) ?>" <?= $categoria['CategoriaID'] == $producto['CategoriaID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($categoria['Nombre_Categoria']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="stock_minimo">Stock Mínimo:</label>
                <input type="number" id="stock_minimo" name="stock_minimo" value="<?= htmlspecialchars($producto['StockMinimo']) ?>" required>
            </div>
            <div>
                <label for="stock_maximo">Stock Máximo:</label>
                <input type="number" id="stock_maximo" name="stock_maximo" value="<?= htmlspecialchars($producto['StockMaximo']) ?>" required>
            </div>
            <div>
                <label for="activo">Estado:</label>
                <input type="checkbox" id="activo" name="activo" <?= $producto['Activo'] ? 'checked' : '' ?>> Activo
            </div>
            <button type="submit">Guardar Cambios</button>
        </form>
    </main>
</body>
</html>
