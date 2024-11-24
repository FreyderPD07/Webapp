<?php
include_once "../db/config.php";
session_start();

$usuario_id = $_SESSION['usuario_id'] ?? null;

// Obtener las categorías
try {
    $query_categorias = $conn->query("SELECT CategoriaID, Nombre_Categoria, ImagenURL FROM categorias WHERE Activo = 1");
    $categorias = $query_categorias->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al obtener categorías: " . $e->getMessage());
}

// Verificar si hay una categoría seleccionada para filtrar productos
$categoria_id = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : null;

// Obtener productos (destacados o por categoría)
try {
    if ($categoria_id) {
        $query_productos = $conn->prepare("SELECT ProductoID, Nombre, Precio, ImagenURL FROM productos WHERE CategoriaID = :categoria_id AND Activo = 1");
        $query_productos->bindParam(':categoria_id', $categoria_id, PDO::PARAM_INT);
        $query_productos->execute();
    } else {
        $query_productos = $conn->query("SELECT ProductoID, Nombre, Precio, ImagenURL FROM productos WHERE Destacado = 1 AND Activo = 1 LIMIT 10");
    }
    $productos = $query_productos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al obtener productos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Tienda Online</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <header class="header">
        <div class="logo">Mi Tienda</div>
        <nav class="navbar">
            <?php if ($usuario_id): ?>
                <button class="btn" onclick="window.location.href='perfil.php'">Perfil</button>
               <!-- <button class="btn" onclick="window.location.href='mis_pedidos.php'">Mis Pedidos</button>-->
                <button class="btn" onclick="window.location.href='logout.php'">Cerrar Sesión</button>
            <?php else: ?>
                <button class="btn" onclick="window.location.href='login.php'">Iniciar Sesión</button>
            <?php endif; ?>
            <button class="btn" onclick="window.location.href='carrito.php'">Carrito</button>
        </nav>
    </header>

    <!-- Categorías -->
    <section class="categories">
        <h2>Categorías Populares</h2>
        <div class="category-container">
            <?php foreach ($categorias as $categoria): ?>
                <a href="home.php?categoria_id=<?= $categoria['CategoriaID'] ?>" class="category">
                    <img src="../<?= htmlspecialchars($categoria['ImagenURL']) ?>" alt="<?= htmlspecialchars($categoria['Nombre_Categoria']) ?>">
                    <h3><?= htmlspecialchars($categoria['Nombre_Categoria']) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Productos -->
    <section class="products">
        <h2><?= $categoria_id ? "Productos de la Categoría" : "Productos Destacados" ?></h2>
        <div class="product-grid">
            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $producto): ?>
                    <div class="product">
                        <img src="../<?= htmlspecialchars($producto['ImagenURL']) ?>" alt="<?= htmlspecialchars($producto['Nombre']) ?>">
                        <h3><?= htmlspecialchars($producto['Nombre']) ?></h3>
                        <p class="price">$<?= number_format($producto['Precio'], 2) ?></p>
                        <form action="add_to_cart.php" method="POST">
                            <input type="hidden" name="producto_id" value="<?= htmlspecialchars($producto['ProductoID']) ?>">
                            <input type="hidden" name="cantidad" value="1">
                            <button type="submit" class="btn">Añadir al Carrito</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay productos disponibles en esta categoría.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($usuario_id): ?>
        <!-- Perfil (Cargado dinámicamente) -->
        <section id="perfil" style="display: none;">
            <?php
            // Obtener datos del usuario
            try {
                $query_usuario = $conn->prepare("SELECT Nombre, Email, DireccionCompleta FROM usuarios WHERE UsuarioID = :usuario_id");
                $query_usuario->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                $query_usuario->execute();
                $usuario = $query_usuario->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                die("Error al obtener datos del usuario: " . $e->getMessage());
            }
            ?>
            <h1>Perfil de <?= htmlspecialchars($usuario['Nombre']) ?></h1>
            <form action="update_perfil.php" method="POST">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario['Nombre']) ?>" required>

                <label for="email">Correo:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['Email']) ?>" required>

                <label for="direccion">Dirección:</label>
                <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($usuario['DireccionCompleta'] ?? '') ?>">

                <button type="submit" class="btn">Actualizar</button>
            </form>
        </section>
    <?php endif; ?>

    <footer class="footer">
        <p>&copy; 2024 Mi Tienda. Todos los derechos reservados.</p>
    </footer>

    <script>
        // Mostrar la sección de perfil dinámicamente
        document.querySelector('button[onclick="window.location.href=\'perfil.php\'"]')?.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector('.products').style.display = 'none';
            document.getElementById('perfil').style.display = 'block';
        });
    </script>
</body>
</html>
