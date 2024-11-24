<?php
include_once "../db/config.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['contrasena'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password === $confirm_password) {
        try {
            $stmt_check = $conn->prepare("SELECT * FROM usuarios WHERE Email = :email");
            $stmt_check->bindParam(':email', $email);
            $stmt_check->execute();
            if ($stmt_check->fetch()) {
                $error = "El correo ya está registrado.";
            } else {
                $password_hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO usuarios (Nombre, Email, Contrasena, Login) VALUES (:nombre, :email, :password, :login)");
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $password_hashed);
                $stmt->bindParam(':login', $email);
                $stmt->execute();

                $usuario_id = $conn->lastInsertId();
                $stmt_rol = $conn->prepare("INSERT INTO usuario_roles (UsuarioID, RolID) VALUES (:usuario_id, 2)");
                $stmt_rol->bindParam(':usuario_id', $usuario_id);
                $stmt_rol->execute();

                header("Location: home.php");
                exit;
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Las contraseñas no coinciden.";
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>
    <div class="register-container">
        <h2>Registro de Usuario</h2>
        <?php if (!empty($error)): ?>
            <div id="register-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div>
                <label for="name">Nombre:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div>
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>
            <div>
                <label for="confirm_password">Confirmar Contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">Registrar</button>
        </form>
    </div>
</body>
</html>
