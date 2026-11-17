<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);

require 'Classes/PHPExcel/IOFactory.php'; 
require 'conexion.php';

$nombreArchivo = 'ejemplo.xlsx';

$objPHPExcel = PHPExcel_IOFactory::load($nombreArchivo);

$objPHPExcel->setActiveSheetIndex(0);
$numRows = $objPHPExcel->setActiveSheetIndex(0)->getHighestRow();

echo '<table border=1><tr><td>Producto</td><td>Precio</td><td>Existencia</td></tr>';

for ($i = 2; $i <= $numRows; $i++) {
    
    $nombre = $objPHPExcel->getActiveSheet()->getCell('A'.$i)->getCalculatedValue();
    $precio = $objPHPExcel->getActiveSheet()->getCell('B'.$i)->getCalculatedValue();
    $existencia = $objPHPExcel->getActiveSheet()->getCell('C'.$i)->getCalculatedValue();
    
    echo '<tr>';
    echo '<td>'. $nombre.'</td>';
    echo '<td>'. $precio.'</td>';
    echo '<td>'. $existencia.'</td>';
    echo '</tr>';
    
    if (isset($mysqli)) {
        $sql = "INSERT INTO productos (nombre, precio, existencia) VALUES('$nombre','$precio','$existencia')";
        $result = $mysqli->query($sql);
        
        if (!$result) {
            echo "<tr><td colspan='3' style='color:red; font-weight:bold;'>❌ Error en fila $i: " . $mysqli->error . "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='3' style='color:orange; font-weight:bold;'>⚠️ Error: La variable \$mysqli no existe. Revisa tu archivo conexion.php</td></tr>";
        break; 
    }
}

echo '</table>';
?>
