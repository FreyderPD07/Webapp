<?php
header('Content-Type: application/json');
require_once('../db/config.php');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'], $_GET['departamento_id'])) {
        $search = trim($_GET['search']);
        $departamentoId = intval($_GET['departamento_id']);

        $stmt = $conn->prepare("
            SELECT CiudadID, Nombre_Ciudad 
            FROM ciudades 
            WHERE Nombre_Ciudad LIKE :search AND DepartamentoID = :departamento_id
        ");
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':departamento_id', $departamentoId, PDO::PARAM_INT);
        $stmt->execute();

        $municipios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'municipios' => $municipios]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $municipio = trim($data['municipio'] ?? '');
        $departamentoId = intval($data['departamento_id'] ?? 0);

        if ($municipio && $departamentoId) {
            $stmt = $conn->prepare("
                SELECT CiudadID 
                FROM ciudades 
                WHERE Nombre_Ciudad = :municipio AND DepartamentoID = :departamento_id
            ");
            $stmt->bindValue(':municipio', $municipio, PDO::PARAM_STR);
            $stmt->bindValue(':departamento_id', $departamentoId, PDO::PARAM_INT);
            $stmt->execute();

            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                echo json_encode(['success' => true, 'ciudad_id' => $existing['CiudadID']]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO ciudades (Nombre_Ciudad, DepartamentoID) 
                    VALUES (:municipio, :departamento_id)
                ");
                $stmt->bindValue(':municipio', $municipio, PDO::PARAM_STR);
                $stmt->bindValue(':departamento_id', $departamentoId, PDO::PARAM_INT);
                $stmt->execute();

                $ciudadId = $conn->lastInsertId();
                echo json_encode(['success' => true, 'ciudad_id' => $ciudadId]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
        }
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
    exit;
}
