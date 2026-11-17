<?php
require __DIR__ . '/auth.php';
require_login();
require __DIR__ . '/conexion.php';
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$d = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$d || $d->format('Y-m-d') !== $fecha) $fecha = date('Y-m-d');

$stmt = $mysqli->prepare("SELECT COUNT(*) AS cantidad, COALESCE(SUM(total),0) AS total FROM ventas WHERE estado='completada' AND fecha >= ? AND fecha < DATE_ADD(?, INTERVAL 1 DAY)");
$stmt->bind_param('ss', $fecha, $fecha);
$stmt->execute();
$resumen = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $mysqli->prepare("SELECT id, numero, cliente, metodo_pago, total, fecha FROM ventas WHERE estado='completada' AND fecha >= ? AND fecha < DATE_ADD(?, INTERVAL 1 DAY) ORDER BY fecha DESC");
$stmt->bind_param('ss', $fecha, $fecha);
$stmt->execute();
$ventas = $stmt->get_result();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reporte diario</title><style>
*{box-sizing:border-box}body{margin:0;background:#f2f7f5;color:#243b37;font:15px Arial,sans-serif}.wrap{max-width:1050px;margin:30px auto;padding:0 16px}.head,.box{background:white;padding:22px;border:1px solid #dce9e5;border-radius:14px;margin-bottom:18px}.head{background:#087f73;color:white}.head h1{margin:0 0 7px;font-size:24px}.box h2{font-size:19px;color:#087f73;margin:0 0 16px}.filter{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.filter label{font-weight:bold;display:block;margin-bottom:6px}.filter input,.filter button,.btn{padding:10px;border:1px solid #cbdad6;border-radius:7px;font:inherit}.filter button,.btn{background:#087f73;color:white;border:0;text-decoration:none;cursor:pointer;display:inline-block}.stats{display:flex;gap:30px;flex-wrap:wrap}.stat strong{display:block;color:#087f73;font-size:25px;margin-top:5px}table{width:100%;border-collapse:collapse}th,td{padding:11px;border-bottom:1px solid #e1eae7;text-align:left}th{background:#eff7f4}.scroll{overflow:auto}@media print{.noprint{display:none}}
</style></head><body><main class="wrap"><header class="head"><h1>Reporte de ventas del día</h1><div>Consulta el total y las facturas registradas en una fecha.</div></header>
<section class="box noprint"><form class="filter" method="get"><div><label for="fecha">Fecha del reporte</label><input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>" required></div><button type="submit">Ver reporte</button><button type="button" onclick="window.print()">Imprimir</button><a class="btn" href="index.php">Volver a facturación</a></form></section>
<section class="box"><div class="stats"><div>Fecha<strong><?php echo htmlspecialchars($fecha); ?></strong></div><div>Facturas<strong><?php echo (int)$resumen['cantidad']; ?></strong></div><div>Total vendido<strong>RD$ <?php echo number_format((float)$resumen['total'],2); ?></strong></div></div></section>
<section class="box"><h2>Facturas</h2><div class="scroll"><table><thead><tr><th>Número</th><th>Hora</th><th>Cliente</th><th>Pago</th><th>Total</th></tr></thead><tbody>
<?php if ($ventas->num_rows) { while ($v = $ventas->fetch_assoc()) { ?><tr><td><?php echo htmlspecialchars($v['numero']); ?></td><td><?php echo htmlspecialchars(date('H:i', strtotime($v['fecha']))); ?></td><td><?php echo htmlspecialchars($v['cliente']); ?></td><td><?php echo htmlspecialchars($v['metodo_pago']); ?></td><td>RD$ <?php echo number_format((float)$v['total'],2); ?></td></tr><?php } } else { ?><tr><td colspan="5">No hay ventas registradas en esta fecha.</td></tr><?php } ?>
</tbody></table></div></section></main></body></html>
<?php $stmt->close(); ?>
