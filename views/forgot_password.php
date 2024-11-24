<?php
// Incluir archivo de conexión
include_once "../db/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    try {
        // Verificar si el correo existe en la base de datos
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE Email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Aquí puedes enviar un enlace para restablecer la contraseña al correo del usuario.
            $mensaje = "Se ha enviado un enlace de recuperación a tu correo.";
        } else {
            $error = "El correo no está registrado.";
        }
    } catch (Exception $e) {
        $error = "Error al procesar la solicitud: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="forgot-password-container">
        <h2>Recuperar Contraseña</h2>
        <?php if (!empty($error)): ?>
            <div id="error-message"><?= htmlspecialchars($error) ?></div>
        <?php elseif (!empty($mensaje)): ?>
            <div id="success-message"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div>
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit">Enviar Enlace</button>
        </form>
    </div>
</body>
</html>
