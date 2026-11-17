
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'conexion.php';

echo "<h2>Prueba de conexión</h2>";

if (!$mysqli_mexico) {
    die("No se pudo conectar a la base de datos mexico.");
}

echo "<p>Conexión a mexico correcta.</p>";

$sql = "SELECT l.id_localidad, l.localidad,
               m.id_municipio, m.id_estado
        FROM localidad AS l
        INNER JOIN municipio AS m
            ON l.id_municipio = m.id_municipio
        WHERE l.id_localidad = 1";

$resultado = $mysqli_mexico->query($sql);

if (!$resultado) {
    die("Error SQL: " . $mysqli_mexico->error);
}

if ($resultado->num_rows == 0) {
    die("No se encontró la localidad 1 o no tiene municipio relacionado.");
}

$fila = $resultado->fetch_assoc();

echo "<pre>";
print_r($fila);
echo "</pre>";
?>
