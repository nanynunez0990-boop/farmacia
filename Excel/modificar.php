<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
	require 'Classes/PHPExcel/IOFactory.php';
	
	$objPHPExcel = new PHPExcel();
	$objReader = PHPExcel_IOFactory::createReader('Excel2007');
	$objPHPExcel = $objReader->load('ejemplo.xlsx');
	$objPHPExcel->setActiveSheetIndex(0);
	
	$objPHPExcel->getActiveSheet()->SetCellValue('A2', 'Nuevos Dulces');
	$objPHPExcel->getActiveSheet()->SetCellValue('B2', 8.30);
	$objPHPExcel->getActiveSheet()->SetCellValue('C2', 10);
	
	$objPHPExcel->getActiveSheet()->SetCellValue('A7', 'Nuevo Producto');
	$objPHPExcel->getActiveSheet()->SetCellValue('B7', 10);
	$objPHPExcel->getActiveSheet()->SetCellValue('C7', 2);

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$objWriter->save("Archivo_salida.xlsx");
?>