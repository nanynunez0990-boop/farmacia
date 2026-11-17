<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'Classes/PHPExcel.php'; 

$objPHPExcel = new PHPExcel();

$objPHPExcel->getProperties()
    ->setCreator("Códigos de Programación")
    ->setLastModifiedBy("Códigos de Programación")
    ->setTitle("Excel en PHP")
    ->setSubject("Documento de prueba")
    ->setDescription("Documento generado con PHPExcel")
    ->setKeywords("excel phpexcel php")
    ->setCategory("Ejemplos");

$objPHPExcel->setActiveSheetIndex(0);
$objPHPExcel->getActiveSheet()->setTitle('Hoja 1');

$objPHPExcel->getActiveSheet()->setCellValue('A1', 'PHPExcel');
$objPHPExcel->getActiveSheet()->setCellValue('A2', 12345.6789);
$objPHPExcel->getActiveSheet()->setCellValue('A3', TRUE);

if (ob_get_contents()) ob_end_clean();
ob_start();

header('Content-Type: application/vnd.openxmlformats-officedocument.sheet');
header('Content-Disposition: inline;filename="Excel.xlsx"');
header('Cache-Control: max-age=0');
    
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit;
?>
