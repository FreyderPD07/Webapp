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
    $stmt_carrito->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="../assets/css/checkout.css">
    <style>
        .suggestions {
            border: 1px solid #ccc;
            max-height: 150px;
            overflow-y: auto;
            position: absolute;
            background-color: #fff;
            z-index: 1000;
            width: calc(100% - 20px);
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 5px;
        }

        .suggestion-item {
            padding: 10px;
            cursor: pointer;
        }

        .suggestion-item:hover {
            background-color: #f0f0f0;
        }

        .no-results {
            padding: 10px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">Mi Tienda</div>
    </header>

    <section class="checkout-section">
        <h2>Finalizar Compra</h2>

        <h3>Tu Carrito</h3>
        <ul>
            <?php foreach ($carrito as $item): ?>
                <li><?= htmlspecialchars($item['Nombre']) ?> (x<?= htmlspecialchars($item['Cantidad']) ?>) - $<?= number_format($item['Precio'] * $item['Cantidad'], 2) ?></li>
            <?php endforeach; ?>
        </ul>

        <form method="POST">
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

    <script>
      document.addEventListener("DOMContentLoaded", function () {
    const municipioInput = document.getElementById("municipio");
    const departamentoSelect = document.getElementById("departamento");
    const municipioSuggestions = document.getElementById("municipio-suggestions");
    const ciudadIdInput = document.getElementById("ciudad_id");

    municipioInput.addEventListener("input", function () {
        const search = this.value.trim();
        const departamentoId = departamentoSelect.value;

        municipioSuggestions.innerHTML = "";
        ciudadIdInput.value = "";

        if (search.length < 2 || !departamentoId) return;

        // Ruta corregida a ../api/municipios.php
        fetch(`../api/municipios.php?search=${encodeURIComponent(search)}&departamento_id=${encodeURIComponent(departamentoId)}`)
            .then(response => response.json())
            .then(data => {
                console.log("Respuesta del servidor:", data); // Depuración en la consola

                if (data.success && data.municipios.length > 0) {
                    municipioSuggestions.innerHTML = "";
                    data.municipios.forEach(municipio => {
                        const suggestion = document.createElement("div");
                        suggestion.classList.add("suggestion-item");
                        suggestion.textContent = municipio.Nombre_Ciudad;
                        suggestion.dataset.ciudadId = municipio.CiudadID;

                        suggestion.addEventListener("click", function () {
                            municipioInput.value = municipio.Nombre_Ciudad;
                            ciudadIdInput.value = municipio.CiudadID;
                            municipioSuggestions.innerHTML = ""; // Limpiar las sugerencias
                        });

                        municipioSuggestions.appendChild(suggestion);
                    });
                } else {
                    municipioSuggestions.innerHTML = `<div class="no-results">Sin resultados</div>`;
                }
            })
            .catch(error => console.error("Error al buscar municipios:", error));
    });

    municipioInput.addEventListener("blur", function () {
        setTimeout(() => municipioSuggestions.innerHTML = "", 200);
    });

    departamentoSelect.addEventListener("change", function () {
        municipioInput.value = "";
        ciudadIdInput.value = "";
        municipioSuggestions.innerHTML = "";
    });
});

    </script>
</body>
</html>
