
<?php
require_once '../conexion.php';

header('Content-Type: text/html; charset=UTF-8');

if (!isset($mysqli_mexico) || $mysqli_mexico->connect_error) {
    http_response_code(500);
    exit('Error de conexión con la base de datos mexico.');
}

if (!isset($_POST['id_estado'])) {
    http_response_code(400);
    exit('No se recibió el ID del estado.');
}

$id_estado = (int) $_POST['id_estado'];

$sql = "SELECT id_municipio, municipio
        FROM municipio
        WHERE id_estado = ?
        ORDER BY municipio";

$stmt = $mysqli_mexico->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    exit('Error al preparar la consulta: ' . $mysqli_mexico->error);
}

$stmt->bind_param("i", $id_estado);

if (!$stmt->execute()) {
    http_response_code(500);
    exit('Error al consultar municipios: ' . $stmt->error);
}

$resultado = $stmt->get_result();

echo '<option value="0">Seleccionar Municipio</option>';

while ($row = $resultado->fetch_assoc()) {
    echo '<option value="' . (int)$row['id_municipio'] . '">'
        . htmlspecialchars($row['municipio'], ENT_QUOTES, 'UTF-8')
        . '</option>';
}

$stmt->close();
?>
