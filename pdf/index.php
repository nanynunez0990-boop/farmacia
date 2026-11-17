
<?php
require_once __DIR__ . '/plantilla.php';
require_once __DIR__ . '/conexion.php';

$query = "SELECT id, nombre, correo, telefono FROM personas ORDER BY id";
$resultado = $mysqli->query($query);

if (!$resultado) {
    die("Error en la consulta: " . $mysqli->error);
}

// Guardar los registros para calcular la altura del recibo
$personas = [];

while ($fila = $resultado->fetch_assoc()) {
    $personas[] = $fila;
}

$resultado->free();

$total = count($personas);

// Recibo estrecho: 80 mm de ancho y altura según los registros
$alto = 105 + ($total * 25);

$pdf = new PDF('P', 'mm', [80, $alto]);
$pdf->SetMargins(5, 5, 5);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

$ancho = 70;

// Nombre de la tienda
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell($ancho, 7, utf8_decode('MI TIENDA'), 0, 1, 'C');

$pdf->SetFont('Arial', '', 8);
$pdf->Cell($ancho, 4, utf8_decode('RECIBO DE REGISTRO'), 0, 1, 'C');
$pdf->Cell($ancho, 4, utf8_decode('Dirección:los cocos de jacagua'), 0, 1, 'C');
$pdf->Cell($ancho, 4, utf8_decode('Tel.:8494859490'), 0, 1, 'C');

$pdf->Ln(3);

// Línea divisoria
$pdf->SetLineWidth(0.2);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());

// Número y fecha
$pdf->Ln(3);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell($ancho, 4, 'Recibo No.: ' . date('YmdHis'), 0, 1);
$pdf->Cell($ancho, 4, 'Fecha: ' . date('d/m/Y'), 0, 1);
$pdf->Cell($ancho, 4, 'Hora: ' . date('h:i A'), 0, 1);
$pdf->Cell($ancho, 4, utf8_decode('Elaborado por:Naurys Nuñez'), 0, 1);

$pdf->Ln(3);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

// Detalle de registros
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell($ancho, 5, utf8_decode('DETALLE'), 0, 1, 'C');

$pdf->Ln(1);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell($ancho, 5, utf8_decode('REGISTROS: ' . $total), 0, 1);


$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(2);

// Cada persona se muestra como un bloque
foreach ($personas as $persona) {
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell($ancho, 4, 'ID: ' . $persona['id'], 0, 1);

    $pdf->SetFont('Arial', '', 8);

    $nombre = substr(
        utf8_decode($persona['nombre']),
        0,
        100
    );

    $correo = substr(
        utf8_decode($persona['correo']),
        0,
        150
    );

    $telefono = substr(
        utf8_decode($persona['telefono']),
        0,
        50
    );

    $pdf->MultiCell($ancho, 4, utf8_decode('Nombre: ') . $nombre);
    $pdf->MultiCell($ancho, 4, utf8_decode('Correo: ') . $correo);
    $pdf->MultiCell($ancho, 4, utf8_decode('Teléfono: ') . $telefono);

    $pdf->Ln(2);
    $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
    $pdf->Ln(2);
}

// Resumen final
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($ancho, 6, 'Total de registros: ' . $total, 0, 1, 'R');

$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($ancho, 5, 'GRACIAS POR SU VISITA', 0, 1, 'C');

$pdf->SetFont('Arial', '', 8);
$pdf->Cell($ancho, 4, utf8_decode('Conserve este recibo.'), 0, 1, 'C');

$mysqli->close();

$pdf->Output('I', 'Recibo.pdf');
exit;
?>
