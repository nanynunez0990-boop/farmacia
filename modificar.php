
<?php
require 'conexion.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de registro no válido.");
}

$id = (int) $_GET['id'];

$sql = "SELECT * FROM personas WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();

if (!$resultado || $resultado->num_rows === 0) {
    die("Registro no encontrado.");
}

$row = $resultado->fetch_assoc();
$stmt->close();

// Valores guardados
$estadoSeleccionado = (int)($row['id_estado'] ?? 0);
$municipioSeleccionado = (int)($row['id_municipio'] ?? 0);
$localidadSeleccionada = (int)($row['id_localidad'] ?? 0);

// Consultar estados
$query = "SELECT id_estado, nombre FROM estado ORDER BY nombre";
$resultado_estado = $mysqli_mexico->query($query);

if (!$resultado_estado) {
    die("Error al consultar los estados.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Modificar Registro</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f3e8ff, #fce7f3, #e8eaff);
            color: #59436f;
            font-family: 'Poppins', Arial, sans-serif;
            min-height: 100vh;
            padding: 20px 0;
        }

        .container {
            background: #fff;
            max-width: 1050px;
            padding: 30px;
            margin: 20px auto 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(126, 87, 194, .16);
            border: 1px solid #f0e4ff;
        }

        h3.text-center {
            color: #7952a5;
            font-size: 25px;
            font-weight: 700;
            margin: 0 0 35px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0e4ff;
        }

        .control-label {
            color: #705486;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-control {
            min-height: 40px;
            border: 1px solid #ddc8f5;
            border-radius: 9px;
            box-shadow: none;
            background: #fdfaff;
            color: #59436f;
            transition: .3s;
        }

        .form-control:focus {
            border-color: #a779d4;
            box-shadow: 0 0 7px rgba(167, 121, 212, .35);
        }

        select.form-control {
            cursor: pointer;
        }

        input[type="file"].form-control {
            height: auto;
            padding: 10px;
        }

        input[type="radio"],
        input[type="checkbox"] {
            accent-color: #a779d4;
            cursor: pointer;
        }

        .radio-inline,
        .checkbox-inline {
            color: #705486;
            margin-right: 12px;
            margin-bottom: 8px;
        }

        .btn {
            border-radius: 9px;
            padding: 9px 18px;
            transition: .3s;
            margin-right: 5px;
        }

        .btn-pastel-primary {
            background: linear-gradient(90deg, #a77bd1, #c99bdc);
            border: 1px solid #a77bd1;
            color: white;
        }

        .btn-pastel-primary:hover,
        .btn-pastel-primary:focus {
            background: #9062bd;
            border-color: #9062bd;
            color: white;
        }

        .btn-pastel-danger {
            background: #e9b5cc;
            border: 1px solid #dfa1bc;
            color: #713c58;
        }

        .btn-pastel-danger:hover {
            background: #dfa1bc;
            color: #54253d;
        }

        .btn-default {
            background: #f6effc;
            border: 1px solid #ddc8f5;
            color: #7952a5;
        }

        .btn-default:hover {
            background: #eadcff;
            border-color: #c8a9e8;
            color: #68458e;
        }

        .archivos {
            margin-top: 12px;
            padding: 12px;
            background: #fdfaff;
            border: 1px solid #eee2fa;
            border-radius: 10px;
            overflow-wrap: anywhere;
        }

        .archivos a {
            color: #7952a5;
        }

        .img-thumbnail {
            border-radius: 12px;
            padding: 5px;
            background: #fff;
            border: 1px solid #ddc8f5;
            max-width: 100%;
            height: auto;
        }

        .separador {
            border-top: 1px solid #f0e4ff;
            margin: 28px 0;
        }

        /* Modal de confirmación */
        .modal-content {
            border-radius: 18px;
            border: 1px solid #ddc8f5;
            overflow: hidden;
        }

        .modal-header {
            background: #f3e8ff;
            border-bottom: 1px solid #eadcff;
        }

        .modal-title {
            color: #7952a5;
            font-weight: 700;
        }

        .modal-body {
            padding: 25px 20px;
            color: #59436f;
        }

        .icono-modal {
            font-size: 42px;
            color: #a779d4;
            margin-bottom: 15px;
        }

        .modal-footer {
            background: #fdfaff;
            border-top: 1px solid #eee2fa;
        }

        #mensajeModificar {
            display: none;
            margin-top: 12px;
            overflow-wrap: anywhere;
        }

        @media (max-width: 767px) {
            .container {
                margin: 10px;
                width: auto;
                padding: 20px 15px;
            }

            h3.text-center {
                font-size: 21px;
            }

            .control-label {
                text-align: left !important;
                margin-bottom: 8px;
            }

            .btn {
                margin-bottom: 8px;
            }

            .radio-inline,
            .checkbox-inline {
                display: inline-block;
                margin-left: 0;
            }
        }
    </style>

    <script src="js/jquery-3.1.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>

    <script>
    $(document).ready(function () {

        // Cargar localidades
        function cargarLocalidades(idMunicipio, idLocalidad) {

            $("#cbx_localidad").html(
                '<option value="0">Seleccionar Localidad</option>'
            );

            if (idMunicipio == "0" || idMunicipio == "") {
                return;
            }

            $.ajax({
                url: "combobox/includes/getLocalidad.php",
                type: "POST",
                data: {
                    id_municipio: idMunicipio
                },

                success: function (data) {
                    $("#cbx_localidad").html(data);

                    if (idLocalidad != "0") {
                        $("#cbx_localidad").val(String(idLocalidad));
                    }
                },

                error: function (xhr) {
                    console.log(xhr.responseText);
                    alert("Error al cargar localidades.");
                }
            });
        }

        // Cargar municipios
        function cargarMunicipios(idEstado, idMunicipio, idLocalidad) {

            $("#cbx_municipio").html(
                '<option value="0">Seleccionar Municipio</option>'
            );

            $("#cbx_localidad").html(
                '<option value="0">Seleccionar Localidad</option>'
            );

            if (idEstado == "0" || idEstado == "") {
                return;
            }

            $.ajax({
                url: "combobox/includes/getMunicipio.php",
                type: "POST",
                data: {
                    id_estado: idEstado
                },

                success: function (data) {
                    $("#cbx_municipio").html(data);

                    if (idMunicipio != "0") {
                        $("#cbx_municipio").val(String(idMunicipio));

                        if ($("#cbx_municipio").val() !== String(idMunicipio)) {
                            console.log(
                                "El municipio guardado no pertenece al estado seleccionado."
                            );
                            return;
                        }

                        cargarLocalidades(idMunicipio, idLocalidad);
                    }
                },

                error: function (xhr) {
                    console.log(xhr.responseText);
                    alert("Error al cargar municipios.");
                }
            });
        }

        // Cambiar estado
        $("#cbx_estado").on("change", function () {
            cargarMunicipios($(this).val(), "0", "0");
        });

        // Cambiar municipio
        $("#cbx_municipio").on("change", function () {
            cargarLocalidades($(this).val(), "0");
        });

        // Recuperar las selecciones guardadas
        cargarMunicipios(
            $("#cbx_estado").val(),
            "<?php echo $municipioSeleccionado; ?>",
            "<?php echo $localidadSeleccionada; ?>"
        );

        // Abrir el modal al intentar guardar
        $("#formModificar").on("submit", function (e) {
            e.preventDefault();

            $("#mensajeModificar").hide().text("");

            $("#confirmarModificar")
                .prop("disabled", false)
                .html("Sí, modificar");

            $("#modalModificar").modal("show");
        });

        // Confirmar modificación mediante AJAX
        $("#confirmarModificar").on("click", function () {

            var boton = $(this);
            var formulario = document.getElementById("formModificar");

            // Verificar los campos obligatorios
            if (!formulario.checkValidity()) {
                $("#modalModificar").modal("hide");
                formulario.reportValidity();
                return;
            }

            var datos = new FormData(formulario);

            boton.prop("disabled", true).text("Guardando...");

            $("#mensajeModificar").hide().text("");

            $.ajax({
                url: "update.php",
                type: "POST",
                data: datos,
                processData: false,
                contentType: false,
                dataType: "text",

                success: function (respuesta) {

                    if (respuesta.trim() === "success") {
                        $("#mensajeModificar")
                            .text("¡Registro modificado correctamente!")
                            .css("color", "#43865a")
                            .show();

                        window.location.href = "index.php";
                    } else {
                        $("#mensajeModificar")
                            .text(
                                "No se pudieron guardar los cambios. " +
                                respuesta.trim()
                            )
                            .css("color", "#b54769")
                            .show();

                        boton.prop("disabled", false)
                             .text("Sí, modificar");
                    }
                },

                error: function (xhr) {
                    console.log(xhr.responseText);

                    $("#mensajeModificar")
                        .text(
                            xhr.responseText.trim() ||
                            "Error al comunicarse con el servidor."
                        )
                        .css("color", "#b54769")
                        .show();

                    boton.prop("disabled", false)
                         .text("Sí, modificar");
                }
            });
        });

        // Eliminar archivo adjunto
        $(document).on("click", ".delete", function (e) {

            e.preventDefault();

            var contenedor = $(this).closest(".archivos");
            var filePath = contenedor.attr("data");

            if (!confirm("¿Desea eliminar este archivo?")) {
                return;
            }

            $.ajax({
                type: "POST",
                url: "del_files.php",
                data: {
                    file: filePath
                },

                success: function (response) {
                    if (response.trim() === "success") {
                        contenedor.remove();
                    } else {
                        alert(
                            "No se pudo eliminar el archivo: " + response
                        );
                    }
                },

                error: function () {
                    alert("Error al comunicarse con el servidor.");
                }
            });
        });

    });
    </script>
</head>

<body>

<div class="container">

    <h3 class="text-center">MODIFICAR REGISTRO</h3>

    <form id="formModificar"
          class="form-horizontal"
          method="POST"
          action="update.php"
          enctype="multipart/form-data"
          autocomplete="off">

        <input type="hidden"
               name="id"
               value="<?php echo (int)$row['id']; ?>">

        <!-- Nombre -->
        <div class="form-group">
            <label for="nombre" class="col-sm-2 control-label">
                Nombre
            </label>

            <div class="col-sm-10">
                <input type="text"
                       class="form-control"
                       id="nombre"
                       name="nombre"
                       value="<?php echo htmlspecialchars($row['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       required>
            </div>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email" class="col-sm-2 control-label">
                Email
            </label>

            <div class="col-sm-10">
                <input type="email"
                       class="form-control"
                       id="email"
                       name="email"
                       value="<?php echo htmlspecialchars($row['correo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       required>
            </div>
        </div>

        <!-- Teléfono -->
        <div class="form-group">
            <label for="telefono" class="col-sm-2 control-label">
                Teléfono
            </label>

            <div class="col-sm-10">
                <input type="tel"
                       class="form-control"
                       id="telefono"
                       name="telefono"
                       value="<?php echo htmlspecialchars($row['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <!-- Estado -->
        <div class="form-group">
            <label for="cbx_estado" class="col-sm-2 control-label">
                Estado
            </label>

            <div class="col-sm-10">
                <select name="cbx_estado"
                        id="cbx_estado"
                        class="form-control"
                        required>

                    <option value="0">Seleccionar Estado</option>

                    <?php while ($estado = $resultado_estado->fetch_assoc()) { ?>
                        <option
                            value="<?php echo (int)$estado['id_estado']; ?>"
                            <?php
                            if ($estadoSeleccionado === (int)$estado['id_estado']) {
                                echo 'selected';
                            }
                            ?>>
                            <?php
                            echo htmlspecialchars(
                                $estado['nombre'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </option>
                    <?php } ?>

                </select>
            </div>
        </div>

        <!-- Municipio -->
        <div class="form-group">
            <label for="cbx_municipio" class="col-sm-2 control-label">
                Municipio
            </label>

            <div class="col-sm-10">
                <select name="cbx_municipio"
                        id="cbx_municipio"
                        class="form-control"
                        required>
                    <option value="0">Seleccionar Municipio</option>
                </select>
            </div>
        </div>

        <!-- Localidad -->
        <div class="form-group">
            <label for="cbx_localidad" class="col-sm-2 control-label">
                Localidad
            </label>

            <div class="col-sm-10">
                <select name="cbx_localidad"
                        id="cbx_localidad"
                        class="form-control"
                        required>
                    <option value="0">Seleccionar Localidad</option>
                </select>
            </div>
        </div>

        <div class="separador"></div>

        <!-- Estado civil -->
        <div class="form-group">
            <label for="estado_civil" class="col-sm-2 control-label">
                Estado Civil
            </label>

            <div class="col-sm-10">
                <select class="form-control"
                        id="estado_civil"
                        name="estado_civil">

                    <?php
                    $estadosCiviles = ['SOLTERO', 'CASADO', 'OTRO'];

                    foreach ($estadosCiviles as $estadoCivil) {
                        $selected = (($row['estado_civil'] ?? '') === $estadoCivil)
                            ? 'selected'
                            : '';

                        echo '<option value="' . $estadoCivil . '" ' .
                             $selected . '>' . $estadoCivil . '</option>';
                    }
                    ?>

                </select>
            </div>
        </div>

        <!-- Hijos -->
        <div class="form-group">
            <label class="col-sm-2 control-label">
                ¿Tiene Hijos?
            </label>

            <div class="col-sm-10" style="padding-top:7px;">

                <label class="radio-inline">
                    <input type="radio"
                           name="hijos"
                           value="1"
                           <?php if ((string)($row['hijos'] ?? '') === '1') echo 'checked'; ?>>
                    Sí
                </label>

                <label class="radio-inline">
                    <input type="radio"
                           name="hijos"
                           value="0"
                           <?php if ((string)($row['hijos'] ?? '') === '0') echo 'checked'; ?>>
                    No
                </label>

            </div>
        </div>

        <!-- Intereses -->
        <div class="form-group">
            <label class="col-sm-2 control-label">
                Intereses
            </label>

            <div class="col-sm-10" style="padding-top:7px;">

                <?php
                $intereses_guardados = array_map(
                    'trim',
                    explode(',', $row['intereses'] ?? '')
                );

                $opciones = ['Libros', 'Musica', 'Deportes', 'Otros'];

                foreach ($opciones as $interes) {
                    $marcado = in_array(
                        $interes,
                        $intereses_guardados,
                        true
                    ) ? 'checked' : '';
                ?>

                    <label class="checkbox-inline">
                        <input type="checkbox"
                               name="intereses[]"
                               value="<?php echo htmlspecialchars($interes, ENT_QUOTES, 'UTF-8'); ?>"
                               <?php echo $marcado; ?>>

                        <?php echo $interes === 'Musica' ? 'Música' : $interes; ?>
                    </label>

                <?php } ?>

            </div>
        </div>

        <!-- Archivo adjunto -->
        <div class="form-group">
            <label for="Archivo" class="col-sm-2 control-label">
                Archivo
            </label>

            <div class="col-sm-10">

                <input type="file"
                       class="form-control"
                       id="Archivo"
                       name="Archivo">

                <?php
                $path = "files/" . $id;

                if (is_dir($path)) {
                    $directorio = opendir($path);

                    while (($archivo = readdir($directorio)) !== false) {

                        if (
                            $archivo !== "." &&
                            $archivo !== ".." &&
                            is_file($path . "/" . $archivo)
                        ) {
                            $ruta_archivo = $path . "/" . $archivo;

                            $ruta_segura = htmlspecialchars(
                                $ruta_archivo,
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            $nombre_seguro = htmlspecialchars(
                                $archivo,
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            echo "<div class='archivos' data='" .
                                 $ruta_segura . "'>";

                            echo "<a href='" . $ruta_segura .
                                 "' target='_blank' rel='noopener'>Ver archivo</a> ";

                            echo $nombre_seguro . " ";

                            echo "<a href='#' class='delete btn btn-pastel-danger btn-xs'>";

                            echo "<span class='glyphicon glyphicon-trash'></span> Eliminar</a>";

                            $extension = strtolower(
                                pathinfo($archivo, PATHINFO_EXTENSION)
                            );

                            if (in_array(
                                $extension,
                                ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                                true
                            )) {
                                echo "<br><img src='" . $ruta_segura .
                                     "' width='300' class='img-thumbnail' style='margin-top:10px;'>";
                            }

                            echo "</div>";
                        }
                    }

                    closedir($directorio);
                }
                ?>

            </div>
        </div>

        <div class="separador"></div>

        <!-- Botones -->
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">

                <a href="index.php" class="btn btn-default">
                    <span class="glyphicon glyphicon-arrow-left"></span>
                    Regresar
                </a>

                <button type="submit" class="btn btn-pastel-primary">
                    <span class="glyphicon glyphicon-floppy-disk"></span>
                    Guardar cambios
                </button>

            </div>
        </div>

    </form>
</div>

<!-- Modal de confirmación -->
<div class="modal fade" id="modalModificar"
     tabindex="-1" role="dialog"
     aria-labelledby="tituloModal">

    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close"
                        data-dismiss="modal" aria-label="Cerrar">
                    <span>&times;</span>
                </button>

                <h4 class="modal-title" id="tituloModal">
                    Confirmar modificación
                </h4>
            </div>

            <div class="modal-body text-center">

                <div class="icono-modal">
                    <span class="glyphicon glyphicon-edit"></span>
                </div>

                <p>¿Quieres modificar este registro?</p>

                <p style="font-size:12px; color:#8b789c;">
                    Se guardarán los cambios que realizaste.
                </p>

                <div id="mensajeModificar"></div>
            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-default"
                        data-dismiss="modal">
                    Cancelar
                </button>

                <button type="button" id="confirmarModificar"
                        class="btn btn-pastel-primary">
                    Sí, modificar
                </button>

            </div>

        </div>
    </div>
</div>

</body>
</html>
