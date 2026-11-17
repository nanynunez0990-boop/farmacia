<?php
if (isset($_POST['file'])) {
    $archivoRelativo = $_POST['file'];
    
    // Ruta absoluta en el servidor usando __DIR__
    $filePath = __DIR__ . '/' . $archivoRelativo;

    // Comprobamos si el archivo existe y lo eliminamos
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo "success";
        } else {
            echo "error_unlink";
        }
    } else if (file_exists($archivoRelativo)) {
        if (unlink($archivoRelativo)) {
            echo "success";
        } else {
            echo "error_unlink";
        }
    } else {
        echo "file_not_found";
    }
}
?>