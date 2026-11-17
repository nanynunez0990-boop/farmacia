<?php
require __DIR__ . '/auth.php';
require_login();
require __DIR__ . '/conexion.php';

function mostrarErrorVenta($mensaje)
{
    http_response_code(400);
    $mensaje = htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>No se pudo registrar</title>';
    echo '<body style="font:16px Arial;background:#f2f7f5;padding:30px"><main style="max-width:600px;margin:auto;background:white;padding:25px;border-radius:12px"><h2>No se pudo registrar la venta</h2><p>' . $mensaje . '</p><a href="index.php">Volver a facturación</a></main></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$cliente = trim($_POST['cliente'] ?? '');
if ($cliente === '') $cliente = 'Consumidor final';
if (strlen($cliente) > 150) mostrarErrorVenta('El nombre del cliente es demasiado largo.');
$idCliente = trim($_POST['id_cliente'] ?? '');
if (strlen($idCliente) > 30) mostrarErrorVenta('El ID del cliente no puede superar 30 caracteres.');
if ($idCliente === '') $idCliente = null;

$metodos = ['Efectivo', 'Tarjeta', 'Transferencia'];
$metodo = $_POST['metodo'] ?? 'Efectivo';
if (!in_array($metodo, $metodos, true)) $metodo = 'Efectivo';
$descuentoPct = filter_var($_POST['descuento_pct'] ?? 0, FILTER_VALIDATE_FLOAT);
if ($descuentoPct === false || $descuentoPct < 0) mostrarErrorVenta('El descuento no es válido.');
$ajustes = $mysqli->query("SELECT clave, valor FROM configuracion WHERE clave IN ('impuesto_pct','descuento_max_pct')");
$tasas = ['impuesto_pct' => 0.0, 'descuento_max_pct' => 100.0];
if ($ajustes) while ($a = $ajustes->fetch_assoc()) $tasas[$a['clave']] = (float)$a['valor'];
if ($descuentoPct > $tasas['descuento_max_pct']) mostrarErrorVenta('El descuento supera el máximo configurado.');

$items = json_decode($_POST['items'] ?? '', true);
if (!is_array($items) || count($items) === 0) mostrarErrorVenta('La factura no tiene productos.');

$cantidades = [];
foreach ($items as $item) {
    $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
    $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || !$cantidad || $cantidad < 1) mostrarErrorVenta('Hay un producto o una cantidad inválida.');
    $cantidades[$id] = ($cantidades[$id] ?? 0) + $cantidad;
}

try {
    $mysqli->begin_transaction();
    $buscar = $mysqli->prepare('SELECT nombre, precio, stock FROM productos WHERE id=? AND activo=1 FOR UPDATE');
    $descontar = $mysqli->prepare('UPDATE productos SET stock=stock-? WHERE id=? AND stock>=?');
    $detalle = $mysqli->prepare('INSERT INTO detalle_venta (venta_id, producto_id, nombre_producto, precio, cantidad, subtotal, lote_id) VALUES (?,?,?,?,?,?,?)');
    $lotesStmt = $mysqli->prepare('SELECT id,cantidad_disponible FROM lotes_producto WHERE producto_id=? AND cantidad_disponible>0 AND (fecha_vencimiento IS NULL OR fecha_vencimiento>=CURDATE()) ORDER BY fecha_vencimiento IS NULL,fecha_vencimiento,id FOR UPDATE');
    $actualizaLote = $mysqli->prepare('UPDATE lotes_producto SET cantidad_disponible=cantidad_disponible-? WHERE id=? AND cantidad_disponible>=?');
    $lineas = [];
    $total = 0.0;

    foreach ($cantidades as $id => $cantidad) {
        $buscar->bind_param('i', $id);
        $buscar->execute();
        $producto = $buscar->get_result()->fetch_assoc();
        if (!$producto) throw new RuntimeException('Uno de los productos ya no está disponible.');
        if ((int)$producto['stock'] < $cantidad) {
            throw new RuntimeException('Stock insuficiente para ' . $producto['nombre'] . '. Disponible: ' . (int)$producto['stock'] . '.');
        }
        $precio = (float)$producto['precio'];
        $subtotal = round($precio * $cantidad, 2);
        $total += $subtotal;
        $lotesStmt->bind_param('i', $id);
        $lotesStmt->execute();
        $resultLotes = $lotesStmt->get_result();
        $pendiente = $cantidad;
        $asignaciones = [];
        while ($lote = $resultLotes->fetch_assoc()) {
            if ($pendiente <= 0) break;
            $tomar = min($pendiente, (int)$lote['cantidad_disponible']);
            $asignaciones[] = ['lote_id' => (int)$lote['id'], 'cantidad' => $tomar];
            $pendiente -= $tomar;
        }
        if ($pendiente > 0) throw new RuntimeException('No hay suficiente inventario vigente por lotes para ' . $producto['nombre'] . '. Registra una compra con lote y vencimiento.');
        $lineas[] = ['id' => $id, 'nombre' => $producto['nombre'], 'precio' => $precio, 'cantidad' => $cantidad, 'subtotal' => $subtotal, 'lotes' => $asignaciones];
    }

    $descuento = round($total * $descuentoPct / 100, 2);
    $impuestoPct = $tasas['impuesto_pct'];
    $impuesto = round(($total - $descuento) * $impuestoPct / 100, 2);
    $total = round($total - $descuento + $impuesto, 2);
    $numero = 'F-' . date('YmdHis') . '-' . random_int(100, 999);
    $venta = $mysqli->prepare('INSERT INTO ventas (numero, id_cliente, cliente, metodo_pago, descuento, impuesto_pct, impuesto, total) VALUES (?,?,?,?,?,?,?,?)');
    $venta->bind_param('ssssdddd', $numero, $idCliente, $cliente, $metodo, $descuento, $impuestoPct, $impuesto, $total);
    $venta->execute();
    $ventaId = $mysqli->insert_id;

    foreach ($lineas as $linea) {
        $qty = $linea['cantidad'];
        $pid = $linea['id'];
        $descontar->bind_param('iii', $qty, $pid, $qty);
        $descontar->execute();
        if ($descontar->affected_rows !== 1) throw new RuntimeException('No se pudo actualizar el stock de ' . $linea['nombre'] . '.');

        foreach ($linea['lotes'] as $asignacion) {
            $cantidadLote = $asignacion['cantidad'];
            $loteId = $asignacion['lote_id'];
            $subtotalLote = round($linea['precio'] * $cantidadLote, 2);
            $actualizaLote->bind_param('iii', $cantidadLote, $loteId, $cantidadLote);
            $actualizaLote->execute();
            if ($actualizaLote->affected_rows !== 1) throw new RuntimeException('Cambió el inventario de un lote; intenta otra vez.');
            $detalle->bind_param('iisdidi', $ventaId, $pid, $linea['nombre'], $linea['precio'], $cantidadLote, $subtotalLote, $loteId);
            $detalle->execute();
            $tipoMovimiento = 'venta';
            $refMovimiento = $numero;
            $usuarioMovimiento = $_SESSION['farmacia_usuario_id'] ?? null;
            $mov = $mysqli->prepare('INSERT INTO movimientos_inventario(producto_id,lote_id,tipo,cantidad,referencia,usuario_id) VALUES(?,?,?,?,?,?)');
            $mov->bind_param('iisisi', $pid, $loteId, $tipoMovimiento, $cantidadLote, $refMovimiento, $usuarioMovimiento);
            $mov->execute();
        }
    }

    $mysqli->commit();
    header('Location: index.php?ok=' . urlencode($numero));
    exit;
} catch (Throwable $e) {
    $mysqli->rollback();
    mostrarErrorVenta($e instanceof RuntimeException ? $e->getMessage() : 'Verifica que las tablas productos, ventas y detalle_venta estén creadas correctamente.');
}
