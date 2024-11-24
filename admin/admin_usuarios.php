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

// Obtener la lista de usuarios
try {
    $query = $conn->query("SELECT UsuarioID, Nombre, Email, Activo FROM usuarios");
    $usuarios = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al obtener usuarios: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="../assets/css/admin_productos.css">
</head>
<body>
    <header class="header-admin">
        <h1>Gestión de Usuarios</h1>
        <nav>
            <button onclick="window.location.href='dashboard.php'">Volver al Panel</button>
            <button onclick="window.location.href='logout.php'">Cerrar Sesión</button>
        </nav>
    </header>
    <main>
        <h2>Lista de Usuarios</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= htmlspecialchars($usuario['UsuarioID']) ?></td>
                        <td><?= htmlspecialchars($usuario['Nombre']) ?></td>
                        <td><?= htmlspecialchars($usuario['Email']) ?></td>
                        
                        <td><?= $usuario['Activo'] ? 'Activo' : 'Inactivo' ?></td>
                        <td>
                            <a href="edit_usuario.php?id=<?= $usuario['UsuarioID'] ?>" class="btn">Editar</a>
                            <a href="delete_usuario.php?id=<?= $usuario['UsuarioID'] ?>" class="btn danger">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
