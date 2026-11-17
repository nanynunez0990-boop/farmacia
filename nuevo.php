<?php
require 'conexion.php';

$consultaEstados = $mysqli_mexico->query(
    "SELECT id_estado, nombre FROM estado ORDER BY nombre"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo Registro</title>

    <link href="/proyecto/css/bootstrap.min.css" rel="stylesheet">
    <link href="/proyecto/css/bootstrap-theme.css" rel="stylesheet">

    <script src="/proyecto/js/jquery-3.1.1.min.js"></script>
    <script src="/proyecto/js/bootstrap.min.js"></script>

    <style>
        body {
            background: linear-gradient(135deg, #faf7ff, #f1e8ff, #fff0fa);
            font-family: "Trebuchet MS", Arial, sans-serif;
            color: #55436f;
            padding: 30px 0;
        }

        .formulario {
            background: rgba(255, 255, 255, 0.95);
            max-width: 850px;
            margin: auto;
            padding: 30px 35px;
            border-radius: 22px;
            border: 1px solid #e5d6ff;
            box-shadow: 0 8px 25px rgba(142, 106, 194, 0.15);
        }

        .encabezado {
            text-align: center;
            margin-bottom: 30px;
            padding: 22px;
            background: linear-gradient(135deg, #d9c5ff, #efc8f2);
            border-radius: 17px;
            color: #62458e;
        }

        .encabezado h2 {
            font-weight: bold;
            margin: 0 0 8px;
        }

        .encabezado p {
            margin: 0;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .control-label {
            color: #715495;
            font-weight: bold;
            padding-top: 10px;
        }

        .form-control {
            height: 43px;
            border: 1px solid #ddcdf7;
            border-radius: 10px;
            box-shadow: none;
            background: #fdfbff;
            color: #55436f;
        }

        .form-control:focus {
            border-color: #ad8be8;
            box-shadow: 0 0 8px rgba(173, 139, 232, 0.25);
        }

        input[type="file"].form-control {
            height: auto;
            padding: 10px;
        }

        .opciones {
            padding-top: 8px;
        }

        .radio-inline,
        .checkbox-inline {
            color: #66517f;
            margin-right: 12px;
            margin-bottom: 8px;
        }

        .btn {
            border: none;
            border-radius: 10px;
            padding: 11px 22px;
            font-weight: bold;
            transition: 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(100, 70, 140, 0.15);
        }

        .btn-guardar {
            background: linear-gradient(135deg, #a987e8, #8967cc);
            color: white;
        }

        .btn-guardar:hover,
        .btn-guardar:focus {
            background: #7957b7;
            color: white;
        }

        .btn-regresar {
            background: #f4eaff;
            color: #775b9c;
            border: 1px solid #e3d3fa;
        }

        .btn-regresar:hover {
            background: #e9d9ff;
            color: #62458e;
        }

        .acciones {
            border-top: 1px solid #eee4fb;
            padding-top: 22px;
            margin-top: 28px;
        }

        @media (max-width: 767px) {
            .formulario {
                margin: 0 15px;
                padding: 20px 16px;
            }

            .control-label {
                text-align: left !important;
                padding-bottom: 8px;
            }

            .acciones .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>

<div class="container">
    <div class="formulario">

        <div class="encabezado">
            <h2>💜 Nuevo Registro</h2>
            <p>Completa los datos para agregar una persona</p>
        </div>

        <form method="POST" action="guardar.php"
              enctype="multipart/form-data" autocomplete="off">

            <div class="form-group">
                <label for="nombre" class="col-sm-2 control-label">Nombre</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="nombre"
                           name="nombre" placeholder="Escribe el nombre"
                           required>
                </div>
                <div class="clearfix"></div>
            </div>

            <div class="form-group">
                <label for="email" class="col-sm-2 control-label">Email</label>
                <div class="col-sm-10">
                    <input type="email" class="form-control" id="email"
                           name="email" placeholder="ejemplo@correo.com"
                           required>
                </div>
                <div class="clearfix"></div>
            </div>

            <div class="form-group">
                <label for="telefono" class="col-sm-2 control-label">Teléfono</label>
                <div class="col-sm-10">
                    <input type="tel" class="form-control" id="telefono"
                           name="telefono" placeholder="Escribe el teléfono">
                </div>
                <div class="clearfix"></div>
            </div>

            <!-- Estado -->
            <div class="form-group">
                <label for="estado" class="col-sm-2 control-label">
                    Estado
                </label>
                <div class="col-sm-10">
                    <select class="form-control" id="estado" name="estado"
                            required>
                        <option value="">Selecciona un estado</option>

                        <?php while ($estado = $consultaEstados->fetch_assoc()) { ?>
                            <option value="<?php echo $estado['id_estado']; ?>">
                                <?php echo htmlspecialchars($estado['nombre']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="clearfix"></div>
            </div>

            <!-- Municipio -->
            <div class="form-group">
                <label for="municipio" class="col-sm-2 control-label">
                    Municipio
                </label>
                <div class="col-sm-10">
                    <select class="form-control" id="municipio"
                            name="municipio" disabled required>
                        <option value="">Selecciona un municipio</option>
                    </select>
                </div>
                <div class="clearfix"></div>
            </div>

            <!-- Localidad -->
            <div class="form-group">
                <label for="localidad" class="col-sm-2 control-label">
                    Localidad
                </label>
                <div class="col-sm-10">
                    <select class="form-control" id="localidad"
                            name="localidad" disabled required>
                        <option value="">Selecciona una localidad</option>
                    </select>
                </div>
                <div class="clearfix"></div>
            </div>

            <div class="form-group">
                <label for="estado_civil" class="col-sm-2 control-label">
                    Estado civil
                </label>
                <div class="col-sm-10">
                    <select class="form-control" id="estado_civil"
                            name="estado_civil">
                        <option value="SOLTERO">SOLTERO</option>
                        <option value="CASADO">CASADO</option>
                        <option value="OTRO">OTRO</option>
                    </select>
                </div>
                <div class="clearfix"></div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">¿Tiene hijos?</label>
                <div class="col-sm-10 opciones">
                    <label class="radio-inline">
                        <input type="radio" name="hijos" value="1" checked>
                        Sí
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="hijos" value="0">
                        No
                    </label>
                </div>
                <div class="clearfix"></div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Intereses</label>
                <div class="col-sm-10 opciones">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="intereses[]" value="Libros">
                        📚 Libros
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="intereses[]" value="Musica">
                        🎵 Música
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="intereses[]" value="Deportes">
                        ⚽ Deportes
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="intereses[]" value="Otros">
                        ✨ Otros
                    </label>
                </div>
                <div class="clearfix"></div>
            </div>

            <div class="form-group">
                <label for="Archivo" class="col-sm-2 control-label">
                    Archivo / Imagen
                </label>
                <div class="col-sm-10">
                    <input type="file" class="form-control" id="Archivo"
                           name="Archivo">
                </div>
                <div class="clearfix"></div>
            </div>

            <div class="acciones text-right">
                <a href="index.php" class="btn btn-regresar">
                    ← Regresar
                </a>
                <button type="submit" class="btn btn-guardar">
                    💜 Guardar registro
                </button>
            </div>

        </form>
    </div>
</div>

<script>
$(document).ready(function () {

    // Cargar municipios cuando se seleccione un estado
    $('#estado').on('change', function () {
        var idEstado = $(this).val();

        $('#municipio')
            .html('<option value="">Cargando municipios...</option>')
            .prop('disabled', true);

        $('#localidad')
            .html('<option value="">Selecciona una localidad</option>')
            .prop('disabled', true);

        if (idEstado !== '') {
            $.ajax({
                url: 'combobox/includes/getMunicipio.php',
                type: 'POST',
                data: { id_estado: idEstado },
                success: function (respuesta) {
                    $('#municipio').html(respuesta).prop('disabled', false);
                },
                error: function () {
                    $('#municipio')
                        .html('<option value="">Error al cargar municipios</option>')
                        .prop('disabled', true);
                }
            });
        } else {
            $('#municipio')
                .html('<option value="">Selecciona un municipio</option>')
                .prop('disabled', true);
        }
    });

    // Cargar localidades cuando se seleccione un municipio
    $('#municipio').on('change', function () {
        var idMunicipio = $(this).val();

        $('#localidad')
            .html('<option value="">Cargando localidades...</option>')
            .prop('disabled', true);

        if (idMunicipio !== '') {
            $.ajax({
                url: 'combobox/includes/getLocalidad.php',
                type: 'POST',
                data: { id_municipio: idMunicipio },
                success: function (respuesta) {
                    $('#localidad').html(respuesta).prop('disabled', false);
                },
                error: function () {
                    $('#localidad')
                        .html('<option value="">Error al cargar localidades</option>')
                        .prop('disabled', true);
                }
            });
        } else {
            $('#localidad')
                .html('<option value="">Selecciona una localidad</option>')
                .prop('disabled', true);
        }
    });

});
</script>

</body>
</html>
