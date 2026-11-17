<?php
	require 'conexion.php';
	
	$where = "";
	
	if(!empty($_POST))
	{
		$valor = $_POST['campo'];
		if(!empty($valor)){
			$where = "WHERE p.nombre LIKE '%$valor%'";
		}
	}
	
	$sql = "DELETE FROM personas WHERE id = '$id'";
	$resultado = $mysqli->query($sql);
	
?>
<html lang="es">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link href="css/bootstrap.min.css" rel="stylesheet">
		<link href="css/bootstrap-theme.css" rel="stylesheet">
        <link href="css/dataTables.min.css" rel="stylesheet">	
		<script src="js/jquery-3.1.1.min.js"></script>
		<script src="js/bootstrap.min.js"></script>	
        <script src="js/dataTables.min.js"></script>

        <script>
	$(document).ready(function(){
		$('#mitabla').DataTable({
			"order": [[1, "asc"]],
			"language":{
				"lengthMenu": "Mostrar _MENU_ registros por pagina",
				"info": "Mostrando registros _START_ a _END_ de _TOTAL_ registros",
				"infoEmpty": "No hay registros disponibles",
				"infoFiltered": "(filtrada de _MAX_ registros)",
				"loadingRecords": "Cargando...",
				"processing":     "Procesando...",
				"search": "Buscar:",
				"zeroRecords":    "No se encontraron registros coincidentes",
				"paginate": {
					"next":       "Siguiente",
					"previous":   "Anterior"
				},					
			}
		});	
	});	
</script>

	</head>
	
	<body>
		
		<div class="container" style="margin-top: 20px;">
			<div class="row">
				<h2 style="text-align:center; font-weight: bold; margin-bottom: 30px;">proyecto de PHP y MySQL</h2>
			</div>
			
			<div class="row" style="margin-bottom: 20px; text-align: center;">
				<div class="col-md-12">
					<a href="nuevo.php" class="btn btn-primary" style="border-radius: 4px; font-weight: bold; margin: 2px;">Nuevo Registro</a>
					<a href="pdf/index.php" class="btn btn-danger" style="border-radius: 4px; font-weight: bold; margin: 2px;">Generar PDF</a>
					<a href="Excel/reporte.php" class="btn btn-success" style="border-radius: 4px; font-weight: bold; margin: 2px;">Exportar a Excel</a>
					<a href="email/index.php" class="btn btn-warning" style="border-radius: 4px; font-weight: bold; margin: 2px; background-color: #f0ad4e; color: white;">Enviar correo</a>
					<a href="codigos_barras/index.php" class="btn btn-default" style="border-radius: 4px; font-weight: bold; margin: 2px; background-color: #343a40; color: white; border: none;">Código de barras</a>
					<a href="codigoqr/index.php" class="btn btn-info" style="border-radius: 4px; font-weight: bold; margin: 2px;">Código QR</a>
					<a href="captcha/validar.php" class="btn btn-success" style="border-radius: 4px; font-weight: bold; margin: 2px; background-color: #20c997; border: none;">CAPTCHA</a>
				</div>
			</div>

			<div class="row" style="margin-bottom: 15px;">
				<div class="col-md-4">
					<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="form-inline">
						<div class="form-group">
							<input type="text" id="campo" name="campo" class="form-control" placeholder="Buscar por nombre..." />
						</div>
						<input type="submit" id="enviar" name="enviar" value="Buscar" class="btn btn-info" />
					</form>
				</div>
			</div>
			
			<div class="row table-responsive">
				<table class="table table-striped table-bordered table-hover" id="mitabla" width="100%">
					<thead style="background-color: #111111; color: #ffffff;">
						<tr>
							<th style="border-color: #222;">ID</th>
							<th style="border-color: #222;">Nombre</th>
							<th style="border-color: #222;">Email</th>
							<th style="border-color: #222;">Teléfono</th>
							<th style="border-color: #222;">Estado</th>
							<th style="border-color: #222;">Imagen</th>
							<th style="border-color: #222; text-align: center;">Modificar</th>
							<th style="border-color: #222; text-align: center;">Eliminar</th>
						</tr>
					</thead>
					
					<tbody>
						<?php while($row = $resultado->fetch_array(MYSQLI_ASSOC)) { ?>
							<tr>
								<td><?php echo $row['id']; ?></td>
								<td><?php echo $row['nombre']; ?></td>
								<td><?php echo $row['correo']; ?></td>
								<td><?php echo $row['telefono']; ?></td>
								<td>
									<?php 
										echo (!empty($row['estado_nombre'])) ? $row['estado_nombre'] : 'Sin asignar'; 
									?>
								</td>
								<td style="text-align: center;">
									<?php 
										$path_img = "files/" . $row['id'] . "/" . $row['imagen'];
										if(!empty($row['imagen']) && file_exists($path_img)) { 
									?>
										<img src="<?php echo $path_img; ?>" width="50" class="img-thumbnail">
									<?php } else { ?>
										Sin imagen
									<?php } ?>
								</td>
								<td style="text-align: center;">
									<a href="modificar.php?id=<?php echo $row['id']; ?>" class="btn btn-xs btn-warning" style="color: white; padding: 2px 6px;">
										<span class="glyphicon glyphicon-pencil"></span>
									</a>
								</td>
								<td style="text-align: center;">
									<a href="#" data-href="eliminar.php?id=<?php echo $row['id']; ?>" data-toggle="modal" data-target="#confirm-delete" class="btn btn-xs btn-danger" style="padding: 2px 6px;">
										<span class="glyphicon glyphicon-trash"></span>
									</a>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
		
		<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title" id="myModalLabel">Eliminar Registro</h4>
					</div>
					
					<div class="modal-body">
						¿Desea eliminar este registro?
					</div>
					
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
						<a class="btn btn-danger btn-ok">Eliminar</a>
					</div>
				</div>
			</div>
		</div>
		
		<script>
			$('#confirm-delete').on('show.bs.modal', function(e) {
				$(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
				
				$('.debug-url').html('Delete URL: <strong>' + $(this).find('.btn-ok').attr('href') + '</strong>');
			});
		</script>	
		
	</body>
</html>