<?php
require __DIR__ . '/auth.php';
require_login();
require __DIR__ . '/conexion.php';
require __DIR__ . '/pdf/fpdf/fpdf.php';

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$d = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$d || $d->format('Y-m-d') !== $fecha) $fecha = date('Y-m-d');

$stmt = $mysqli->prepare("SELECT numero, cliente, metodo_pago, total, fecha FROM ventas WHERE estado='completada' AND fecha >= ? AND fecha < DATE_ADD(?, INTERVAL 1 DAY) ORDER BY fecha ASC");
$stmt->bind_param('ss', $fecha, $fecha);
$stmt->execute();
$ventas = $stmt->get_result();
$filas = [];
$total = 0.0;
while ($v = $ventas->fetch_assoc()) {
    $filas[] = $v;
    $total += (float)$v['total'];
}
$stmt->close();

function pdfText($value) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$value);
}

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFillColor(8, 127, 115);
$pdf->Rect(0, 0, 210, 47, 'F');
// Logo de farmacia: emblema verde claro con cruz blanca.
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(15, 12, 22, 22, 'F');
$pdf->SetFillColor(8, 127, 115);
$pdf->Rect(23, 15, 6, 16, 'F');
$pdf->Rect(18, 20, 16, 6, 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetXY(45, 12);
$pdf->SetFont('Arial', 'B', 20);
$pdf->Cell(145, 10, pdfText('FARMACIA'), 0, 1);
$pdf->SetX(45);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(145, 7, pdfText('Cuidamos tu salud'), 0, 1);
$pdf->SetXY(15, 38);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(180, 7, pdfText('REPORTE DIARIO DE VENTAS'), 0, 1);

$pdf->SetTextColor(40, 55, 52);
$pdf->Ln(8);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(95, 8, pdfText('Fecha del reporte: ' . $fecha), 0, 0);
$pdf->Cell(95, 8, pdfText('Facturas: ' . count($filas)), 0, 1);
$pdf->SetFillColor(232, 246, 242);
$pdf->SetDrawColor(202, 226, 218);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(180, 13, pdfText('TOTAL VENDIDO: RD$ ' . number_format($total, 2)), 1, 1, 'C', true);
$pdf->Ln(7);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(8, 127, 115);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(8, 127, 115);
$pdf->Cell(32, 9, pdfText('Factura'), 1, 0, 'L', true);
$pdf->Cell(24, 9, pdfText('Hora'), 1, 0, 'L', true);
$pdf->Cell(60, 9, pdfText('Cliente'), 1, 0, 'L', true);
$pdf->Cell(35, 9, pdfText('Pago'), 1, 0, 'L', true);
$pdf->Cell(35, 9, pdfText('Total RD$'), 1, 1, 'R', true);
$pdf->SetTextColor(40, 55, 52);
$pdf->SetDrawColor(215, 229, 224);
$pdf->SetFont('Arial', '', 9);
foreach ($filas as $v) {
    $pdf->Cell(32, 8, pdfText($v['numero']), 1);
    $pdf->Cell(24, 8, pdfText(date('H:i', strtotime($v['fecha']))), 1);
    $pdf->Cell(60, 8, pdfText(mb_strimwidth($v['cliente'], 0, 32, '...')), 1);
    $pdf->Cell(35, 8, pdfText($v['metodo_pago']), 1);
    $pdf->Cell(35, 8, number_format((float)$v['total'], 2), 1, 1, 'R');
}
$pdf->SetY(-18);
$pdf->SetTextColor(100, 120, 115);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 8, pdfText('Reporte generado por el sistema de farmacia'), 0, 0, 'C');
$pdf->Output('I', 'reporte_farmacia_' . $fecha . '.pdf');
