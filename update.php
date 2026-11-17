
<?php
require 'conexion.php';

header('Content-Type: text/plain; charset=UTF-8');

// Comprobar que se recibieron los datos
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

if (
    !isset($_POST['id']) ||
    !is_numeric($_POST['id']) ||
    !isset($_POST['nombre']) ||
    !isset($_POST['email'])
) {
    http_response_code(400);
    exit('Faltan datos obligatorios.');
}

$id = (int) $_POST['id'];

$nombre = trim($_POST['nombre']);
$email = trim($_POST['email']);
$telefono = trim($_POST['telefono'] ?? '');
$estado_civil = $_POST['estado_civil'] ?? '';
$hijos = isset($_POST['hijos']) ? (int) $_POST['hijos'] : 0;

// ComboBox
$id_estado = isset($_POST['cbx_estado'])
    ? (int) $_POST['cbx_estado'] : 0;

$id_municipio = isset($_POST['cbx_municipio'])
    ? (int) $_POST['cbx_municipio'] : 0;

$id_localidad = isset($_POST['cbx_localidad'])
    ? (int) $_POST['cbx_localidad'] : 0;

// Validaciones
if (
    $id <= 0 ||
    $nombre === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    http_response_code(400);
    exit('Verifica el nombre y el correo electrónico.');
}

if (!in_array($estado_civil, ['SOLTERO', 'CASADO', 'OTRO'], true)) {
    http_response_code(400);
    exit('El estado civil no es válido.');
}

if (!in_array($hijos, [0, 1], true)) {
    http_response_code(400);
    exit('El valor de hijos no es válido.');
}

if (
    $id_estado <= 0 ||
    $id_municipio <= 0 ||
    $id_localidad <= 0
) {
    http_response_code(400);
    exit('Selecciona un estado, municipio y localidad.');
}

// Guardar intereses separados por comas
$opcionesPermitidas = ['Libros', 'Musica', 'Deportes', 'Otros'];
$interesesRecibidos = $_POST['intereses'] ?? [];

if (!is_array($interesesRecibidos)) {
    http_response_code(400);
    exit('Los intereses enviados no son válidos.');
}

$interesesRecibidos = array_values(array_unique($interesesRecibidos));

foreach ($interesesRecibidos as $interes) {
    if (!in_array($interes, $opcionesPermitidas, true)) {
        http_response_code(400);
        exit('Se recibió un interés no válido.');
    }
}

$arrayIntereses = implode(',', $interesesRecibidos);

// Comprobar que el registro existe
$stmt = $mysqli->prepare(
    "SELECT id FROM personas WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    http_response_code(404);
    exit('El registro no existe.');
}

$stmt->close();

// Actualizar los datos personales y los ComboBox
$sql = "UPDATE personas
        SET nombre = ?,
            correo = ?,
            telefono = ?,
            estado_civil = ?,
            hijos = ?,
            intereses = ?,
            id_estado = ?,
            id_municipio = ?,
            id_localidad = ?
        WHERE id = ?";

$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    exit('No se pudo preparar la actualización.');
}

$stmt->bind_param(
    "ssssissiii",
    $nombre,
    $email,
    $telefono,
    $estado_civil,
    $hijos,
    $arrayIntereses,
    $id_estado,
    $id_municipio,
    $id_localidad,
    $id
);

if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    exit('No se pudieron actualizar los datos.');
}

$stmt->close();

// Procesar archivo adjunto, si se seleccionó uno
if (
    isset($_FILES['Archivo']) &&
    $_FILES['Archivo']['error'] !== UPLOAD_ERR_NO_FILE
) {
    if ($_FILES['Archivo']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        exit('Error al cargar el archivo.');
    }

    $limiteBytes = 10 * 1024 * 1024;

    if ($_FILES['Archivo']['size'] > $limiteBytes) {
        http_response_code(400);
        exit('El archivo supera los 10 MB.');
    }

    // Verificar el tipo real del archivo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipoArchivo = $finfo->file($_FILES['Archivo']['tmp_name']);

    $tiposPermitidos = [
        'image/gif'  => 'gif',
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'application/pdf' => 'pdf'
    ];

    if (!isset($tiposPermitidos[$tipoArchivo])) {
        http_response_code(400);
        exit('Archivo no permitido. Usa JPG, PNG, GIF o PDF.');
    }

    $directorio = __DIR__ . '/files/' . $id . '/';

    if (
        !is_dir($directorio) &&
        !mkdir($directorio, 0755, true) &&
        !is_dir($directorio)
    ) {
        http_response_code(500);
        exit('No se pudo crear la carpeta de archivos.');
    }

    // Nombre seguro para evitar rutas introducidas por el usuario
    $nombreOriginal = basename($_FILES['Archivo']['name']);
    $nombreBase = pathinfo($nombreOriginal, PATHINFO_FILENAME);

    $nombreBase = preg_replace(
        '/[^A-Za-z0-9_-]/',
        '_',
        $nombreBase
    );

    if ($nombreBase === '') {
        $nombreBase = 'archivo';
    }

    $extension = $tiposPermitidos[$tipoArchivo];

    $nombreArchivo = $nombreBase . '.' . $extension;
    $destino = $directorio . $nombreArchivo;

    if (file_exists($destino)) {
        http_response_code(409);
        exit('Ya existe un archivo con ese nombre.');
    }

    if (
        !move_uploaded_file(
            $_FILES['Archivo']['tmp_name'],
            $destino
        )
    ) {
        http_response_code(500);
        exit('No se pudo guardar el archivo adjunto.');
    }
}

// Respuesta que espera el AJAX de modificar.php
echo 'success';
exit;
?>
