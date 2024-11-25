<?php
header('Content-Type: application/json');
require_once('../db/config.php');

try {
    // Verificar que los parámetros GET existen
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'], $_GET['departamento_id'])) {
        $search = trim($_GET['search']);
        $departamentoId = intval($_GET['departamento_id']);

        // Registrar los parámetros para depuración
        file_put_contents('log.txt', print_r($_GET, true), FILE_APPEND);

        // Validar que los parámetros no están vacíos
        if (empty($search) || $departamentoId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
            exit;
        }

        // Preparar la consulta SQL
        $stmt = $conn->prepare("
            SELECT CiudadID, Nombre_Ciudad 
            FROM ciudades 
            WHERE Nombre_Ciudad LIKE :search AND DepartamentoID = :departamento_id
        ");
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':departamento_id', $departamentoId, PDO::PARAM_INT);

        // Registrar la consulta SQL generada para depuración
        $query = "SELECT CiudadID, Nombre_Ciudad 
                  FROM ciudades 
                  WHERE Nombre_Ciudad LIKE '%$search%' AND DepartamentoID = $departamentoId;";
        file_put_contents('query_log.txt', $query . "\n", FILE_APPEND);

        $stmt->execute();
        $municipios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Responder con los resultados en formato JSON
        echo json_encode(['success' => true, 'municipios' => $municipios]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Solicitud inválida.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
    exit;
}
?>
