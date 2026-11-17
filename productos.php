<?php
require __DIR__ . '/auth.php';
require_login();
require __DIR__ . '/conexion.php';
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT);
        $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
        $minimo = filter_var($_POST['stock_minimo'] ?? 5, FILTER_VALIDATE_INT);
        if ($codigo === '' || $nombre === '' || $precio === false || $precio < 0 || $stock === false || $stock < 0 || $minimo === false || $minimo < 0) {
            $error = 'Completa código, nombre, precio y stock con valores válidos.';
        } else {
            try {
                $mysqli->begin_transaction();
                $stmt = $mysqli->prepare('INSERT INTO productos (codigo,nombre,precio,stock,stock_minimo,activo) VALUES (?,?,?,?,?,1)');
                $stmt->bind_param('ssdii', $codigo, $nombre, $precio, $stock, $minimo);
                $stmt->execute();
                $nuevoId = $mysqli->insert_id;
                if ($stock > 0) {
                    $lote = 'INICIAL-' . $nuevoId; $sinVencimiento = null;
                    $insLote = $mysqli->prepare('INSERT INTO lotes_producto (producto_id,numero_lote,fecha_vencimiento,cantidad_inicial,cantidad_disponible) VALUES (?,?,?,?,?)');
                    $insLote->bind_param('issii', $nuevoId, $lote, $sinVencimiento, $stock, $stock);
                    $insLote->execute();
                }
                $mysqli->commit();
                $mensaje = 'Medicamento agregado correctamente.';
            } catch (Throwable $e) {
                $mysqli->rollback();
                $error = 'No se pudo guardar. Verifica que el código no esté repetido.';
            }
        }
    } elseif ($accion === 'actualizar') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT);
        $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
        $minimo = filter_var($_POST['stock_minimo'] ?? 5, FILTER_VALIDATE_INT);
        if (!$id || $precio === false || $precio < 0 || $stock === false || $stock < 0 || $minimo === false || $minimo < 0) {
            $error = 'Precio o stock no válido.';
        } else {
            try {
                $mysqli->begin_transaction();
                $q = $mysqli->prepare('SELECT stock FROM productos WHERE id=? FOR UPDATE');
                $q->bind_param('i', $id); $q->execute();
                $anterior = (int)$q->get_result()->fetch_assoc()['stock'];
                $delta = $stock - $anterior;
                if ($delta > 0) {
                    $lote = 'AJUSTE-' . $id . '-' . date('YmdHis'); $sinVencimiento = null;
                    $insLote = $mysqli->prepare('INSERT INTO lotes_producto (producto_id,numero_lote,fecha_vencimiento,cantidad_inicial,cantidad_disponible) VALUES (?,?,?,?,?)');
                    $insLote->bind_param('issii', $id, $lote, $sinVencimiento, $delta, $delta); $insLote->execute();
                } elseif ($delta < 0) {
                    $pendiente = -$delta;
                    $qLotes = $mysqli->prepare('SELECT id,cantidad_disponible FROM lotes_producto WHERE producto_id=? AND cantidad_disponible>0 ORDER BY fecha_vencimiento,id FOR UPDATE');
                    $qLotes->bind_param('i', $id); $qLotes->execute(); $lotes = $qLotes->get_result();
                    $bajar = $mysqli->prepare('UPDATE lotes_producto SET cantidad_disponible=cantidad_disponible-? WHERE id=?');
                    while ($pendiente > 0 && ($l = $lotes->fetch_assoc())) { $n = min($pendiente, (int)$l['cantidad_disponible']); $lid = (int)$l['id']; $bajar->bind_param('ii', $n, $lid); $bajar->execute(); $pendiente -= $n; }
                    if ($pendiente > 0) throw new RuntimeException('No se pudo ajustar la cantidad por lotes.');
                }
                $stmt = $mysqli->prepare('UPDATE productos SET precio=?, stock=?, stock_minimo=? WHERE id=?');
                $stmt->bind_param('diii', $precio, $stock, $minimo, $id); $stmt->execute();
                $mysqli->commit(); $mensaje = 'Precio y stock actualizados.';
            } catch (Throwable $e) { $mysqli->rollback(); $error = 'No se pudo actualizar el inventario: ' . $e->getMessage(); }
        }
    }
}

$productos = $mysqli->query('SELECT id,codigo,nombre,precio,stock,stock_minimo,activo FROM productos ORDER BY nombre');
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Administrar medicamentos</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:#f2f7f5;color:#243b37;font:15px Arial,sans-serif}.wrap{width:100%;max-width:none;min-height:100vh;margin:0;padding:20px 28px}.head,.box{background:#fff;padding:22px;border:1px solid #dce9e5;border-radius:14px;margin-bottom:18px}.head{background:#087f73;color:white}.head h1{margin:0 0 8px;font-size:24px}.box h2{margin:0 0 16px;color:#087f73;font-size:19px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end}label{display:block;font-weight:bold;margin-bottom:6px}input,button{width:100%;padding:10px;border:1px solid #cbdad6;border-radius:7px;font:inherit}button,.btn{background:#087f73;color:#fff;border:0;cursor:pointer;padding:10px 14px;border-radius:7px;text-decoration:none;display:inline-block}.btn-light{background:#edf7f4;color:#087f73}.tablewrap{overflow:auto}table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #e1eae7;text-align:left}th{background:#eff7f4}.inline{display:flex;gap:6px;align-items:center}.inline input{min-width:80px}.msg{padding:12px;background:#e4f5ec;border-radius:8px;margin-bottom:14px}.err{background:#fff0ed;color:#8c3024}@media(max-width:700px){.wrap{padding:12px}.grid{grid-template-columns:1fr 1fr}}
</style></head><body><main class="wrap">
<header class="head"><h1>Administrar medicamentos</h1><div>Agrega productos y mantén actualizado el precio y las existencias.</div></header>
<?php if ($mensaje) { ?><div class="msg"><?php echo htmlspecialchars($mensaje); ?></div><?php } ?>
<?php if ($error) { ?><div class="msg err"><?php echo htmlspecialchars($error); ?></div><?php } ?>
<section class="box"><h2>Registrar medicamento</h2><form method="post"><input type="hidden" name="accion" value="crear"><div class="grid"><div><label for="codigo">Código</label><input id="codigo" name="codigo" maxlength="50" required></div><div><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="180" required></div><div><label for="precio">Precio (RD$)</label><input id="precio" name="precio" type="number" min="0" step="0.01" required></div><div><label for="stock">Stock inicial</label><input id="stock" name="stock" type="number" min="0" step="1" required></div><div><label>Stock mínimo</label><input name="stock_minimo" type="number" min="0" value="5" required></div></div><p><button type="submit">Guardar medicamento</button> <a class="btn btn-light" href="index.php">Volver a facturación</a></p></form></section>
<section class="box"><h2>Inventario</h2><div class="tablewrap"><table><thead><tr><th>Código</th><th>Medicamento</th><th>Precio</th><th>Stock</th><th>Mínimo</th><th>Estado</th><th>Actualizar</th></tr></thead><tbody>
<?php if ($productos) { while ($p = $productos->fetch_assoc()) { ?><tr><form method="post"><td><?php echo htmlspecialchars($p['codigo']); ?></td><td><?php echo htmlspecialchars($p['nombre']); ?></td><td><input type="number" name="precio" min="0" step="0.01" value="<?php echo htmlspecialchars($p['precio']); ?>" required></td><td><input type="number" name="stock" min="0" step="1" value="<?php echo (int)$p['stock']; ?>" required></td><td><input type="number" name="stock_minimo" min="0" value="<?php echo (int)$p['stock_minimo']; ?>" required></td><td><?php echo (int)$p['stock'] > (int)$p['stock_minimo'] ? 'Hay en stock' : 'Stock bajo'; ?></td><td><input type="hidden" name="accion" value="actualizar"><input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>"><button type="submit">Guardar</button></td></form></tr><?php } } ?>
</tbody></table></div></section></main></body></html>
