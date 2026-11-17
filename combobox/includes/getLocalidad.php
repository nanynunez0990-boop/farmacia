
<?php
require '../conexion.php';

if (isset($_POST['id_municipio'])) {
    $id_municipio = (int) $_POST['id_municipio'];

    $sql = "SELECT id_localidad, localidad
            FROM localidad
            WHERE id_municipio = ?
            ORDER BY localidad";

    $stmt = $mysqli_mexico->prepare($sql);
    $stmt->bind_param("i", $id_municipio);
    $stmt->execute();

    $resultado = $stmt->get_result();

    echo '<option value="0">Seleccionar Localidad</option>';

    while ($row = $resultado->fetch_assoc()) {
        echo '<option value="' . $row['id_localidad'] . '">'
            . htmlspecialchars($row['localidad']) .
            '</option>';
    }

    $stmt->close();
}
?>


