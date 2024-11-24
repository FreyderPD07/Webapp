<?php
ob_start(); // Inicia el buffer de salida
require_once('../db/config.php');
require_once('../lib/fpdf/fpdf.php');

if (!isset($_GET['venta_id']) || !is_numeric($_GET['venta_id'])) {
    die("Error: No se especificó un ID de venta válido.");
}

$venta_id = intval($_GET['venta_id']);

try {
    $stmt_venta = $conn->prepare("
        SELECT v.VentaID, v.FechaVenta, v.Total, v.DireccionCompleta, 
               c.Nombre_Ciudad AS Nombre_Ciudad, d.Nombre_Departamento AS Nombre_Departamento, 
               m.Nombre AS MetodoEnvio, p.Nombre AS MetodoPago, u.Nombre AS Cliente
        FROM ventas v
        JOIN ciudades c ON v.CiudadID = c.CiudadID
        JOIN departamentos d ON c.DepartamentoID = d.DepartamentoID
        JOIN metodos_envio m ON v.MetodoEnvioID = m.MetodoEnvioID
        JOIN metodos_pago p ON p.MetodoID = v.MetodoEnvioID
        JOIN usuarios u ON v.UsuarioID = u.UsuarioID
        WHERE v.VentaID = :venta_id
    ");
    $stmt_venta->bindParam(':venta_id', $venta_id, PDO::PARAM_INT);
    $stmt_venta->execute();
    $venta = $stmt_venta->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        die("Error: No se encontró la venta con el ID especificado.");
    }
} catch (Exception $e) {
    die("Error al obtener los detalles de la venta: " . htmlspecialchars($e->getMessage()));
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

$pdf->Cell(0, 10, 'Factura de Compra', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 10, 'Cliente:', 0, 0);
$pdf->Cell(100, 10, $venta['Cliente'], 0, 1);
$pdf->Cell(50, 10, 'Direccion:', 0, 0);
$pdf->Cell(100, 10, $venta['DireccionCompleta'], 0, 1);
$pdf->Cell(50, 10, 'Ciudad:', 0, 0);
$pdf->Cell(100, 10, $venta['Nombre_Ciudad'], 0, 1);
$pdf->Cell(50, 10, 'Departamento:', 0, 0);
$pdf->Cell(100, 10, $venta['Nombre_Departamento'], 0, 1);
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Fecha de Compra:', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 10, $venta['FechaVenta'], 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Metodo de Envio:', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 10, $venta['MetodoEnvio'], 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Total:', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 10, '$' . number_format($venta['Total'], 2), 0, 1);
$pdf->Ln(10);

$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, 'Gracias por tu compra', 0, 1, 'C');

ob_end_clean(); // Limpia el buffer de salida
$pdf->Output('I', 'factura_' . $venta_id . '.pdf');
?>
