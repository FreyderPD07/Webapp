<?php
include_once "../db/config.php";
session_start();

$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $direccion = $_POST['direccion'] ?? '';

    try {
        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET Nombre = :nombre, Email = :email 
            WHERE UsuarioID = :usuario_id
        ");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();

        // Actualizar dirección en la tabla de ventas
        $stmt_direccion = $conn->prepare("
            UPDATE ventas 
            SET DireccionCompleta = :direccion 
            WHERE UsuarioID = :usuario_id
        ");
        $stmt_direccion->bindParam(':direccion', $direccion);
        $stmt_direccion->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt_direccion->execute();

        header('Location: perfil.php?success=1');
    } catch (Exception $e) {
        echo "Error al actualizar perfil: " . $e->getMessage();
    }
}
