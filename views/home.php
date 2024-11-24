<?php
// Incluir archivo de conexión
include_once "../db/config.php";
session_start();

// Obtener ID del usuario, si está autenticado
$usuario_id = $_SESSION['usuario_id'] ?? null;

// Obtener las categorías
try {
    $query_categorias = $conn->query("
        SELECT CategoriaID, Nombre_Categoria, ImagenURL 
        FROM categorias 
        WHERE Activo = 1
    ");
    $categorias = $query_categorias->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al obtener categorías: " . $e->getMessage());
}

// Verificar si hay una categoría seleccionada
$categoria_id = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : null;

// Obtener productos (destacados o por categoría)
try {
    if ($categoria_id) {
        $query_productos = $conn->prepare("
            SELECT ProductoID, Nombre, Precio, ImagenURL 
            FROM productos 
            WHERE CategoriaID = :categoria_id AND Activo = 1
        ");
        $query_productos->bindParam(':categoria_id', $categoria_id, PDO::PARAM_INT);
        $query_productos->execute();
    } else {
        $query_productos = $conn->query("
            SELECT ProductoID, Nombre, Precio, ImagenURL 
            FROM productos 
            WHERE Destacado = 1 AND Activo = 1 
            LIMIT 10
        ");
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
    <style>
        /* CSS para mantener el footer abajo */
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        body > * {
            flex: 1; /* Asegura que el contenido principal ocupe todo el espacio disponible */
        }

        .footer {
            text-align: center;
            background-color: #f4f4f4;
            padding: 10px 0;
            position: relative;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <header class="header">
        <div class="logo">Mi Tienda</div>
        <nav class="navbar">
            <?php if ($usuario_id): ?>
                <button class="btn" onclick="window.location.href='perfil.php'">Perfil</button>
                <button class="btn" onclick="window.location.href='logout.php'">Cerrar Sesión</button>
            <?php else: ?>
                <button class="btn" onclick="window.location.href='login.php'">Iniciar Sesión</button>
            <?php endif; ?>
            <button class="btn" onclick="window.location.href='carrito.php'">Carrito</button>
        </nav>
    </header>

    <!-- Sección de Categorías -->
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

    <!-- Sección de Productos -->
    <section class="products">
        <h2><?= $categoria_id ? "Productos de la Categoría" : "Productos Destacados" ?></h2>
        <div class="product-grid">
            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $producto): ?>
                    <div class="product">
                        <img src="../<?= htmlspecialchars($producto['ImagenURL']) ?>" alt="<?= htmlspecialchars($producto['Nombre']) ?>">
                        <h3><?= htmlspecialchars($producto['Nombre']) ?></h3>
                        <p class="price">$<?= number_format($producto['Precio'], 2) ?></p>
                        <button 
                            class="btn add-to-cart" 
                            data-id="<?= htmlspecialchars($producto['ProductoID']) ?>" 
                            data-cantidad="1">
                            Añadir al Carrito
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay productos disponibles en esta categoría.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Pie de página -->
    <footer class="footer">
        <p>&copy; 2024 Mi Tienda. Todos los derechos reservados.</p>
    </footer>

    <!-- Script de Interacción -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const buttons = document.querySelectorAll(".add-to-cart");

            buttons.forEach(button => {
                button.addEventListener("click", function () {
                    const productId = this.dataset.id;
                    const cantidad = this.dataset.cantidad;

                    // Realizar solicitud AJAX
                    fetch("add_to_cart.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({ producto_id: productId, cantidad: cantidad })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert("Producto añadido al carrito!");
                        } else {
                            alert(data.message || "Error al añadir el producto al carrito.");
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        alert("Hubo un problema con la solicitud.");
                    });
                });
            });
        });
    </script>
</body>
</html>
