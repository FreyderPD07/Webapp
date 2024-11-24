<?php

// Incluir archivo de conexión
include_once __DIR__ . "/../db/config.php";

// Iniciar sesión
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'Administrador') {
    header("Location: ../views/login.php"); // Redirigir al login si no es administrador
    exit;
}

// Configuración de paginación
$productos_por_pagina = 5; // Número de productos por página
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1; // Página actual
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// Obtener el número total de productos
try {
    $query_total = $conn->query("SELECT COUNT(*) as total FROM productos");
    $total_productos = $query_total->fetch(PDO::FETCH_ASSOC)['total'];
} catch (Exception $e) {
    die("Error al obtener el número total de productos: " . $e->getMessage());
}

// Calcular el número total de páginas
$total_paginas = ceil($total_productos / $productos_por_pagina);

try {
    $query_categorias = $conn->query("SELECT CategoriaID, Nombre_Categoria FROM categorias WHERE Activo = 1");
    $categorias = $query_categorias->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error al cargar categorías: " . $e->getMessage();
}


// Obtener productos para la página actual
try {
    $query = $conn->prepare("
        SELECT p.ProductoID, p.Nombre, p.Precio, c.Nombre_Categoria, p.StockMinimo, p.StockMaximo, p.Activo
        FROM productos p
        JOIN categorias c ON p.CategoriaID = c.CategoriaID
        LIMIT :limit OFFSET :offset
    ");
    $nombre = $_GET['nombre'] ?? '';
    $categoria = $_GET['categoria'] ?? '';
    
    $sql = "SELECT p.ProductoID, p.Nombre, p.Precio, c.Nombre_Categoria, p.StockMinimo, p.StockMaximo, p.Activo
            FROM productos p
            JOIN categorias c ON p.CategoriaID = c.CategoriaID
            WHERE 1 = 1"; // Base de la consulta
    
    if (!empty($nombre)) {
        $sql .= " AND p.Nombre LIKE :nombre"; // Agregar condición para el nombre
    }
    
    if (!empty($categoria)) {
        $sql .= " AND p.CategoriaID = :categoria"; // Agregar condición para la categoría
    }
    
    // Preparamos la consulta SQL
    $stmt = $conn->prepare($sql);
    
    // Bind de los valores
    if (!empty($nombre)) {
        $stmt->bindValue(':nombre', "%$nombre%", PDO::PARAM_STR);
    }
    
    if (!empty($categoria)) {
        $stmt->bindValue(':categoria', $categoria, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

    
    $query->bindValue(':limit', $productos_por_pagina, PDO::PARAM_INT);
    $query->bindValue(':offset', $offset, PDO::PARAM_INT);
    $query->execute();
    $productos = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al cargar productos: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Productos</title>
    <link rel="stylesheet" href="../assets/css/admin_productos.css">
</head>
<body>
    <header class="header-admin">
        <h1>Panel de Administración</h1>
        <nav>
            
            <a href="dashboard.php" class="btn">Dashboard</></a>
            <button class="btn" onclick="window.location.href='add_producto.php'">Agregar Productos</button>
            <button onclick="window.location.href='../views/logout.php'">Cerrar Sesión</button>
            
        </nav>


    </header>
    <main>
    <main>
    <h2>Gestión de Productos</h2>
    <form method="GET" action="" class="filter-form">
        <div>
            <label for="nombre">Buscar por Nombre:</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($_GET['nombre'] ?? '') ?>">
        </div>
        <div>
            <label for="categoria">Filtrar por Categoría:</label>
            <select id="categoria" name="categoria">
                <option value="">Todas las Categorías</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= htmlspecialchars($categoria['CategoriaID']) ?>"
                        <?= (isset($_GET['categoria']) && $_GET['categoria'] == $categoria['CategoriaID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($categoria['Nombre_Categoria']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Filtrar</button>
    </form>

        <h2>Gestión de Productos</h2>
        <a href="add_producto.php" class="btn">Agregar Producto</a>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?= htmlspecialchars($producto['ProductoID']) ?></td>
                            <td><?= htmlspecialchars($producto['Nombre']) ?></td>
                            <td>$<?= number_format($producto['Precio'], 2) ?></td>
                            <td><?= htmlspecialchars($producto['Nombre_Categoria']) ?></td>
                            <td><?= htmlspecialchars($producto['StockMinimo']) ?> - <?= htmlspecialchars($producto['StockMaximo']) ?></td>
                            <td><?= $producto['Activo'] ? 'Activo' : 'Inactivo' ?></td>
                            <td>
                                <a href="edit_producto.php?id=<?= htmlspecialchars($producto['ProductoID']) ?>" class="btn">Editar</a>
                                <a href="delete_producto.php?id=<?= htmlspecialchars($producto['ProductoID']) ?>" class="btn danger">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Paginación -->
            <div class="pagination">
                <?php if ($pagina_actual > 1): ?>
                    <a href="?pagina=<?= $pagina_actual - 1 ?>" class="btn">Anterior</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pagina=<?= $i ?>" class="btn <?= $i === $pagina_actual ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="?pagina=<?= $pagina_actual + 1 ?>" class="btn">Siguiente</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
