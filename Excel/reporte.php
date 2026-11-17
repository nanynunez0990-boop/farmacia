<?php
require 'conexion.php'; 

$sql = "SELECT id, nombre, correo, telefono FROM personas";
$resultado = $mysqli->query($sql);

// Definimos la ruta y el nombre fijo o con fecha dentro de la carpeta Excel
$nombreArchivo = "Reporte_Personas.xls"; // Puedes usar un nombre fijo para que se sobrescriba o con fecha
$rutaCarpeta = __DIR__ . "/" . $nombreArchivo; 

// Creamos el contenido HTML de la tabla
$contenido = "<table border='1'>";
$contenido .= "<tr style='background: color #000; color:white; font-weight:bold; text-align:center;'>";
$contenido .= "<th>ID</th>";
$contenido .= "<th>NOMBRE</th>";
$contenido .= "<th>EMAIL</th>";
$contenido .= "<th>TELEFONO</th>";
$contenido .= "</tr>";

while($rows = $resultado->fetch_assoc()){
    $contenido .= "<tr>";
    $contenido .= "<td style='text-align:center;'>" . $rows['id'] . "</td>";
    $contenido .= "<td style='text-align:left;'>" . utf8_decode($rows['nombre']) . "</td>";
    $contenido .= "<td>" . utf8_decode($rows['correo']) . "</td>";
    $contenido .= "<td style='text-align:center;'>" . $rows['telefono'] . "</td>";
    $contenido .= "</tr>";
}
$contenido .= "</table>";

// Guardamos el archivo físicamente en la carpeta del servidor
file_put_contents($rutaCarpeta, $contenido);

// Redirige instantáneamente al index sin mostrar ninguna página de texto
header("Location: ../index.php");
exit;
?>