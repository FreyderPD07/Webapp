<?php
// Incluir archivo de conexión
include_once __DIR__. "/../db/config.php";

// Iniciar sesión
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'Administrador') {
    header("Location: login.php"); // Redirigir al login si no es administrador
    exit;
}

// Obtener categorías para el desplegable
try {
    $query_categorias = $conn->query("SELECT CategoriaID, Nombre_Categoria FROM categorias WHERE Activo = 1");
    $categorias = $query_categorias->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error al cargar categorías: " . $e->getMessage();
}

// Obtener proveedores para el desplegable
try {
    $query_proveedores = $conn->query("SELECT ProveedorID, Nombre FROM proveedores");
    $proveedores = $query_proveedores->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error al cargar proveedores: " . $e->getMessage();
}

// Procesar formulario al enviar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $precio = $_POST['precio'] ?? 0;
    $categoria_id = $_POST['categoria_id'] ?? 0;
    $stock_minimo = $_POST['stock_minimo'] ?? 10;
    $stock_maximo = $_POST['stock_maximo'] ?? 100;
    $activo = isset($_POST['activo']) ? 1 : 0;
    $proveedor_id = $_POST['proveedor_id'] ?? null; // Capturar proveedor

    // Validar imagen subida
    $imagen = $_FILES['imagen'] ?? null;
    $imagen_url = null;
    if ($imagen && $imagen['tmp_name']) {
        $nombre_imagen = uniqid() . "-" . $imagen['name'];
        move_uploaded_file($imagen['tmp_name'], "../assets/images/$nombre_imagen");
        $imagen_url = "assets/images/$nombre_imagen";
    }

    // Insertar producto en la base de datos
    try {
        $stmt = $conn->prepare("
            INSERT INTO productos (Nombre, CategoriaID, Precio, StockMinimo, StockMaximo, Activo, ImagenURL, ProveedorID)
            VALUES (:nombre, :categoria_id, :precio, :stock_minimo, :stock_maximo, :activo, :imagen_url, :proveedor_id)
        ");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':categoria_id', $categoria_id);
        $stmt->bindParam(':precio', $precio);
        $stmt->bindParam(':stock_minimo', $stock_minimo);
        $stmt->bindParam(':stock_maximo', $stock_maximo);
        $stmt->bindParam(':activo', $activo);
        $stmt->bindParam(':imagen_url', $imagen_url);
        $stmt->bindParam(':proveedor_id', $proveedor_id, PDO::PARAM_INT); // Enlazar proveedor
        $stmt->execute();

        // Redirigir al panel de productos
        header("Location: admin_productos.php?success=1");
        exit;
    } catch (Exception $e) {
        $error = "Error al guardar el producto: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto - Panel de Administración</title>
    <link rel="stylesheet" href="../assets/css/add_producto.css">
</head>
<body>
    <header class="header-admin">
        <h1>Agregar Producto</h1>
        <nav>
            <button onclick="window.location.href='admin_productos.php'">Volver al Panel</button>
        </nav>
    </header>
    <main>
        <h2>Crear Nuevo Producto</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div>
                <label for="nombre">Nombre del Producto:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div>
                <label for="precio">Precio:</label>
                <input type="number" id="precio" name="precio" step="0.01" required>
            </div>
            <div>
                <label for="categoria_id">Categoría:</label>
                <select id="categoria_id" name="categoria_id" required>
                    <option value="">Seleccione una categoría</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= htmlspecialchars($categoria['CategoriaID']) ?>">
                            <?= htmlspecialchars($categoria['Nombre_Categoria']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="proveedor_id">Proveedor:</label>
                <select id="proveedor_id" name="proveedor_id" required>
                    <option value="">Seleccione un proveedor</option>
                    <?php foreach ($proveedores as $proveedor): ?>
                        <option value="<?= htmlspecialchars($proveedor['ProveedorID']) ?>">
                            <?= htmlspecialchars($proveedor['Nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="stock_minimo">Stock Mínimo:</label>
                <input type="number" id="stock_minimo" name="stock_minimo" required>
            </div>
            <div>
                <label for="stock_maximo">Stock Máximo:</label>
                <input type="number" id="stock_maximo" name="stock_maximo" required>
            </div>
            <div>
                <label for="activo">Estado:</label>
                <input type="checkbox" id="activo" name="activo" checked> Activo
            </div>
            <div>
                <label for="imagen">Imagen:</label>
                <input type="file" id="imagen" name="imagen" accept="image/*">
            </div>
            <button type="submit">Guardar Producto</button>
        </form>
    </main>
</body>
</html>
