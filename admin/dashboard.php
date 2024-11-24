<?php
include_once __DIR__ . "/../db/config.php";
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'Administrador') {
    header("Location: ../views/login.php");
    exit;
}

// Obtener estadísticas
try {
    // Total de productos
    $total_productos = $conn->query("SELECT COUNT(*) AS total FROM productos")->fetch(PDO::FETCH_ASSOC)['total'];

    // Productos por categoría
    $productos_por_categoria = $conn->query("
        SELECT c.Nombre_Categoria, COUNT(p.ProductoID) AS total
        FROM categorias c
        LEFT JOIN productos p ON c.CategoriaID = p.CategoriaID
        GROUP BY c.Nombre_Categoria
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Productos activos e inactivos
    $productos_activos = $conn->query("SELECT COUNT(*) AS total FROM productos WHERE Activo = 1")->fetch(PDO::FETCH_ASSOC)['total'];
    $productos_inactivos = $conn->query("SELECT COUNT(*) AS total FROM productos WHERE Activo = 0")->fetch(PDO::FETCH_ASSOC)['total'];
} catch (Exception $e) {
    die("Error al obtener estadísticas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Administración</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <header class="header-admin">
        <h1>Dashboard</h1>
        <nav>
        <button onclick="window.location.href='admin_usuarios.php'">Gestion de Usuarios</button>
            <button onclick="window.location.href='admin_productos.php'">Gestión de Productos</button>
            <button onclick="window.location.href='logout.php'">Cerrar Sesión</button>
        </nav>
    </header>
    <main>
        <h2>Estadísticas</h2>
        <div class="stats">
            <div class="stat">
                <h3>Total de Productos</h3>
                <p><?= $total_productos ?></p>
            </div>
            <div class="stat">
                <h3>Productos Activos</h3>
                <p><?= $productos_activos ?></p>
            </div>
            <div class="stat">
                <h3>Productos Inactivos</h3>
                <p><?= $productos_inactivos ?></p>
            </div>
        </div>
        <h3>Productos por Categoría</h3>
        <ul>
            <?php foreach ($productos_por_categoria as $categoria): ?>
                <li><?= htmlspecialchars($categoria['Nombre_Categoria']) ?>: <?= $categoria['total'] ?></li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>
</html>
