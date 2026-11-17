<?php
require 'conexion.php';

$nombre = $_POST['nombre'] ?? '';
$email = $_POST['email'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$estado_civil = $_POST['estado_civil'] ?? 'OTRO';
$hijos = isset($_POST['hijos']) ? (int)$_POST['hijos'] : 0;

// El formulario utiliza estos nombres
$id_estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 0;
$id_municipio = isset($_POST['municipio']) ? (int)$_POST['municipio'] : 0;
$id_localidad = isset($_POST['localidad']) ? (int)$_POST['localidad'] : 0;

// Convertir los intereses seleccionados en texto
$intereses = $_POST['intereses'] ?? [];
$arrayIntereses = is_array($intereses)
    ? implode(' ', $intereses)
    : '';

$imagen = '';

// Preparar la consulta para evitar errores con comillas
$sql = "INSERT INTO personas
        (nombre, correo, telefono, estado_civil, hijos, intereses,
         id_estado, id_municipio, id_localidad, imagen)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $mysqli->error);
}

$stmt->bind_param(
    "ssssisiiss",
    $nombre,
    $email,
    $telefono,
    $estado_civil,
    $hijos,
    $arrayIntereses,
    $id_estado,
    $id_municipio,
    $id_localidad,
    $imagen
);

if (!$stmt->execute()) {
    die("Error al guardar el registro: " . $stmt->error);
}

$id_insert = $mysqli->insert_id;
$stmt->close();

$resultado = true;

// Subir archivo, si se seleccionó uno
if (
    isset($_FILES['Archivo']) &&
    $_FILES['Archivo']['error'] !== UPLOAD_ERR_NO_FILE
) {
    if ($_FILES['Archivo']['error'] !== UPLOAD_ERR_OK) {
        $mensajeArchivo = "El registro se guardó, pero ocurrió un error al cargar el archivo.";
    } else {
        $permitidos = [
            'image/gif',
            'image/png',
            'image/jpeg',
            'application/pdf'
        ];

        $limite_kb = 400;
        $tipoArchivo = mime_content_type($_FILES['Archivo']['tmp_name']);

        if (
            in_array($tipoArchivo, $permitidos, true) &&
            $_FILES['Archivo']['size'] <= $limite_kb * 1024
        ) {
            $ruta = __DIR__ . "/files/" . $id_insert . "/";

            if (!is_dir($ruta)) {
                mkdir($ruta, 0755, true);
            }

            // Evitar nombres de archivo con rutas
            $nombreArchivo = basename($_FILES['Archivo']['name']);
            $rutaDestino = $ruta . $nombreArchivo;

            if (move_uploaded_file($_FILES['Archivo']['tmp_name'], $rutaDestino)) {
                $stmtImg = $mysqli->prepare(
                    "UPDATE personas SET imagen = ? WHERE id = ?"
                );

                $stmtImg->bind_param("si", $nombreArchivo, $id_insert);
                $stmtImg->execute();
                $stmtImg->close();
            } else {
                $mensajeArchivo = "El registro se guardó, pero no se pudo mover el archivo.";
            }
        } else {
            $mensajeArchivo = "El registro se guardó, pero el archivo no es válido o supera los 400 KB.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guardar Registro</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #faf7ff, #f1e8ff, #fff0fa);
            font-family: "Trebuchet MS", Arial, sans-serif;
            color: #55436f;
        }

        .mensaje {
            max-width: 600px;
            margin: 70px auto;
            padding: 35px;
            text-align: center;
            background: white;
            border: 1px solid #e5d6ff;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(142, 106, 194, 0.15);
        }

        .mensaje h3 {
            font-weight: bold;
            color: #7957b7;
        }

        .btn-regresar {
            background: #8967cc;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 22px;
        }

        .btn-regresar:hover {
            background: #7151ad;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="mensaje">
            <?php if ($resultado) { ?>
                <h3>💜 REGISTRO GUARDADO</h3>
                <p>La persona se agregó correctamente.</p>
            <?php } ?>

            <?php if (!empty($mensajeArchivo)) { ?>
                <p><?php echo htmlspecialchars($mensajeArchivo); ?></p>
            <?php } ?>

            <br>
            <a href="index.php" class="btn btn-regresar">Regresar</a>
        </div>
    </div>
</body>
</html>
