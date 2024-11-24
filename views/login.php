<?php
// Incluir archivo de conexión
include_once "../db/config.php";

// Inicializar sesión
if (!isset($_SESSION)) {
    session_start();
}


// Manejo del formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // Consultar si el usuario existe
        $stmt = $conn->prepare("
            SELECT u.UsuarioID, u.Nombre, u.Contrasena, ur.RolID, r.NombreRol
            FROM usuarios u
            LEFT JOIN usuario_roles ur ON u.UsuarioID = ur.UsuarioID
            LEFT JOIN roles r ON ur.RolID = r.RolID
            WHERE u.Email = :email
            
        ");
        
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['Contrasena'])) {
            // Guardar datos del usuario en la sesión
            $_SESSION['usuario_id'] = $usuario['UsuarioID'];
            $_SESSION['nombre'] = $usuario['Nombre'];
            $_SESSION['rol'] = $usuario['NombreRol'];


            if ($usuario['NombreRol'] === 'Administrador') {
                $_SESSION['usuario_id'] = $usuario['UsuarioID'];
                $_SESSION['nombre'] = $usuario['Nombre'];
                $_SESSION['rol'] = $usuario['NombreRol'];
                
                echo "Sesión iniciada como Administrador. Redirigiendo...";
                header("Location: /tienda/admin/admin_productos.php");
                exit;
            } elseif ($usuario['NombreRol'] === 'Cliente') {
                $_SESSION['usuario_id'] = $usuario['UsuarioID'];
                $_SESSION['nombre'] = $usuario['Nombre'];
                $_SESSION['rol'] = $usuario['NombreRol'];
            
                echo "Sesión iniciada como Cliente. Redirigiendo...";
                header("Location: /tienda/views/home.php");
                exit;
            } else {
                $error = "Rol desconocido. Contacte al administrador.";
            }
            


            // Redirigir según el rol
            if ($usuario['NombreRol'] === 'Administrador') {
    header("Location: /tienda/admin/admin_productos.php");
    exit;
} elseif ($usuario['NombreRol'] === 'Cliente') {
    header("Location: /tienda/views/home.php");
    exit;
} else {
    $error = "Rol desconocido. Contacte al administrador.";
}

        } else {
            $error = "Credenciales incorrectas.";
        }

    } catch (Exception $e) {
        $error = "Error al autenticar: " . $e->getMessage();
    }

}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <div>
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Iniciar Sesión</button>
        </form>
        <p><a href="register.php">¿No tienes cuenta? Regístrate</a></p>
        <p><a href="forgot_password.php">¿Olvidaste tu contraseña?</a></p>
    </div>
</body>
</html>
