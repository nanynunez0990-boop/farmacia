<?php
require __DIR__ . '/auth.php';
require_login();
require __DIR__ . '/conexion.php';

$productos = [];
$error = '';
$config = $mysqli->query("SELECT clave, valor FROM configuracion WHERE clave IN ('impuesto_pct','descuento_max_pct')");
$tasas = ['impuesto_pct' => 0, 'descuento_max_pct' => 100];
if ($config) while ($cfg = $config->fetch_assoc()) $tasas[$cfg['clave']] = (float)$cfg['valor'];

$res = $mysqli->query(
    "SELECT p.id, p.nombre, p.codigo, p.precio,
            COALESCE(SUM(CASE WHEN l.fecha_vencimiento IS NULL OR l.fecha_vencimiento >= CURDATE() THEN l.cantidad_disponible ELSE 0 END), 0) AS stock
     FROM productos p
     LEFT JOIN lotes_producto l ON l.producto_id = p.id
     WHERE p.activo = 1
     GROUP BY p.id, p.nombre, p.codigo, p.precio
     ORDER BY p.nombre"
);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $productos[] = $row;
    }
} else {
    $error = 'Falta crear las tablas del sistema. Importa farmacia.sql en la base de datos farmacia.';
}
$alertasStock = $mysqli->query('SELECT COUNT(*) total FROM productos WHERE activo=1 AND stock<=stock_minimo')->fetch_assoc()['total'] ?? 0;
$alertasVence = $mysqli->query("SELECT COUNT(*) total FROM lotes_producto WHERE cantidad_disponible>0 AND fecha_vencimiento<=DATE_ADD(CURDATE(), INTERVAL 60 DAY)")->fetch_assoc()['total'] ?? 0;
$clientesLista = [];
$clientesRes = $mysqli->query('SELECT documento,nombre FROM clientes ORDER BY nombre');
if ($clientesRes) while ($c = $clientesRes->fetch_assoc()) $clientesLista[] = $c;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Facturación de farmacia</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f2f7f5;
            color: #243b37;
            font: 15px Arial, sans-serif;
        }

        .wrap {
            width: 10000%;
            max-width: none;
            min-height: 100vh;
            margin: 0;
            padding: 20px 28px;
        }

        .head {
            background: #087f73;
            color: white;
            padding: 24px;
            border-radius: 14px;
            margin-bottom: 18px;
        }

        .head-content {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
        }

        .menu-toggle {
            width: 48px;
            height: 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            background: #fff;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
        }
        .menu-toggle span { display:block; width:23px; height:3px; border-radius:3px; background:#087f73; }
        .head-menu {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            z-index: 1000;
            flex-direction: column;
            gap: 6px;
            width: 270px;
            padding: 10px;
            background: #fff;
            border: 1px solid #dce9e5;
            border-radius: 10px;
            box-shadow: 0 10px 24px rgba(20, 60, 50, .2);
        }
        .head-menu.open { display: flex; }
        .head-menu .btn { background: #fff; color: #087f73; text-align: left; }
        .head-menu .btn:hover { background: #e9f5f1; }

        .head h1 {
            margin: 0 0 6px;
            font-size: 25px;
        }

        .box {
            background: white;
            padding: 22px;
            border: 1px solid #dce9e5;
            border-radius: 14px;
            margin: 16px 0;
            box-shadow: 0 4px 14px #173d3510;
        }

        h2 {
            font-size: 19px;
            color: #087f73;
            margin: 0 0 18px;
        }

        label {
            display: block;
            font-weight: bold;
            margin: 10px 0 6px;
        }

        input, select, button {
            font: inherit;
            padding: 11px;
            border: 1px solid #cbdad6;
            border-radius: 7px;
        }

        input, select { width: 100%; }

        .grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 14px;
            align-items: end;
        }

        .grid-producto { grid-template-columns: 2fr .7fr 1fr; margin-top: 10px; }

        .btn {
            background: #087f73;
            color: white;
            border: 0;
            cursor: pointer;
            display: inline-block;
            text-decoration: none;
            padding: 11px 14px;
            border-radius: 7px;
        }

        .btn:hover { background: #06675e; color: white; text-decoration: none; }
        .btn:disabled { opacity: .5; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        th, td {
            border-bottom: 1px solid #e1eae7;
            padding: 11px;
            text-align: left;
        }

        th { background: #eff7f4; }

        .total {
            text-align: right;
            color: #087f73;
            font-size: 25px;
            font-weight: bold;
            margin: 18px 0;
        }

        .row {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .warn {
            background: #fff6df;
            border: 1px solid #ecd391;
            padding: 12px;
            border-radius: 8px;
        }

        @media (max-width: 650px) {
            .grid { grid-template-columns: 1fr; }
            .grid-producto { grid-template-columns: 1fr; }
            .wrap { padding: 12px; }
            .head-menu { width: min(270px, calc(100vw - 48px)); }
        }

        @media print {
            .noprint { display: none; }
        }
    </style>
</head>

<body>
<main class="wrap">
    <header class="head">
        <div class="head-content">
            <div>
                <h1>Farmacia · Punto de venta</h1>
                <div>Sistema de facturación</div>
            </div>
            <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="menuPrincipal">
                <span></span><span></span><span></span>
            </button>
            <nav class="head-menu" id="menuPrincipal">
                <a class="btn" href="productos.php">Administrar medicamentos</a>
                <a class="btn" href="alertas_inventario.php">Alertas de inventario</a>
                <a class="btn" href="reporte_diario_pdf.php">Reporte del día (PDF)</a>
                <a class="btn" href="facturas.php">Facturas</a>
                <a class="btn" href="clientes.php">Clientes</a>
                <a class="btn" href="compras.php">Proveedores / compras</a>
                <a class="btn" href="configuracion.php">Impuestos</a>
                <a class="btn" href="usuarios.php">Usuarios / contraseña</a>
                <a class="btn" href="respaldo_bd.php">Respaldo</a>
                <a class="btn" href="logout.php">Cerrar sesión</a>
            </nav>
        </div>
    </header>

    <?php if ($alertasStock || $alertasVence) { ?>
        <div class="warn">
            <?php if ($alertasStock) { ?>Hay <?php echo (int)$alertasStock; ?> medicamento(s) con stock bajo o agotado.<?php } ?>
            <?php if ($alertasVence) { ?> Hay <?php echo (int)$alertasVence; ?> lote(s) vencidos o próximos a vencer en 60 días.<?php } ?>
            <a href="alertas_inventario.php">Ver alertas</a>
        </div>
    <?php } ?>

    <?php if ($error) { ?>
        <div class="warn"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <form class="box" id="factura" method="post" action="guardar_venta.php">
        <h2>Nueva factura</h2>

        <div class="grid">
            <div>
                <label for="cliente">Cliente</label>
                <input
                    id="cliente"
                    name="cliente"
                    maxlength="150"
                    placeholder="Consumidor final"
                >
            </div>

            <div>
                <label for="id_cliente">ID del cliente</label>
                <input
                    id="id_cliente"
                    name="id_cliente"
                    maxlength="30"
                    placeholder="Cédula o código (opcional)"
                    list="listaClientes"
                >
                <datalist id="listaClientes"><?php foreach ($clientesLista as $c) { ?><option value="<?php echo htmlspecialchars($c['documento'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option><?php } ?></datalist>
            </div>

            <div>
                <label for="metodo">Método de pago</label>
                <select id="metodo" name="metodo">
                    <option>Efectivo</option>
                    <option>Tarjeta</option>
                    <option>Transferencia</option>
                </select>
            </div>

        </div>

        <div class="grid grid-producto noprint">
            <div>
                <label for="producto">Medicamento</label>
                <select id="producto">
                    <option value="">Selecciona</option>
                    <?php foreach ($productos as $p) { ?>
                        <option value="<?php echo (int) $p['id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>"
                            data-precio="<?php echo (float) $p['precio']; ?>"
                            data-stock="<?php echo (int) $p['stock']; ?>">
                            <?php echo htmlspecialchars($p['nombre']); ?> — RD$ <?php echo number_format((float) $p['precio'], 2); ?> (stock <?php echo (int) $p['stock']; ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label for="cantidad">Cantidad</label>
                <input id="cantidad" type="number" min="1" value="1">
            </div>
            <button type="button" class="btn" id="agregar">
                Agregar al carrito
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Medicamento</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Importe</th>
                    <th class="noprint">Acción</th>
                </tr>
            </thead>
            <tbody id="lineas">
                <tr>
                    <td colspan="5">Agrega medicamentos para iniciar la factura.</td>
                </tr>
            </tbody>
        </table>

        <div class="grid" style="margin-top:18px">
            <div><label for="descuento_pct">Descuento (%)</label><input id="descuento_pct" name="descuento_pct" type="number" min="0" max="<?php echo htmlspecialchars((string)$tasas['descuento_max_pct']); ?>" step="0.01" value="0"></div>
            <div>Impuesto configurado: <?php echo number_format($tasas['impuesto_pct'], 2); ?>%</div>
            <div class="total">Total: RD$ <span id="total">0.00</span></div>
        </div>
        <div class="row" style="justify-content:flex-end;gap:24px"><span>Subtotal: RD$ <b id="subtotal">0.00</b></span><span>Descuento: RD$ <b id="descuento">0.00</b></span><span>Impuesto: RD$ <b id="impuesto">0.00</b></span></div>

        <input type="hidden" name="items" id="items">

        <div class="row noprint">
            <button class="btn" id="cobrar" disabled>Registrar venta</button>
            <button type="button" onclick="window.print()">Imprimir</button>
        </div>
    </form>

    <section class="box">
        <h2>Ventas recientes</h2>
        <p><a class="btn" href="facturas.php">Consultar facturas / reimprimir / anular</a></p>

        <?php
        $ventas = $mysqli->query(
            "SELECT numero, cliente, fecha, total
             FROM ventas
             ORDER BY id DESC
             LIMIT 8"
        );
        ?>

        <table>
            <thead>
                <tr>
                    <th>Factura</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($ventas) {
                    while ($v = $ventas->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($v['numero']); ?></td>
                            <td><?php echo htmlspecialchars($v['cliente']); ?></td>
                            <td><?php echo htmlspecialchars($v['fecha']); ?></td>
                            <td>RD$ <?php echo number_format((float) $v['total'], 2); ?></td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>
    </section>
</main>

<script>
    const menuToggle = document.getElementById('menuToggle');
    const menuPrincipal = document.getElementById('menuPrincipal');
    menuToggle.addEventListener('click', () => {
        const abierto = menuPrincipal.classList.toggle('open');
        menuToggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        menuToggle.setAttribute('aria-label', abierto ? 'Cerrar menú' : 'Abrir menú');
    });

    const carrito = new Map();
    const selector = document.getElementById('producto');
    const tasaImpuesto = <?php echo json_encode($tasas['impuesto_pct']); ?>;
    const clientesFarmacia = <?php echo json_encode($clientesLista, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    document.getElementById('id_cliente').addEventListener('change', () => {
        const encontrado = clientesFarmacia.find(c => c.documento === document.getElementById('id_cliente').value);
        if (encontrado) document.getElementById('cliente').value = encontrado.nombre;
    });

    function actualizarFactura() {
        const cuerpo = document.getElementById('lineas');
        let total = 0;

        cuerpo.innerHTML = '';

        carrito.forEach((producto, id) => {
            const importe = producto.precio * producto.cantidad;
            total += importe;

            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td>${producto.nombre}</td>
                <td>RD$ ${producto.precio.toFixed(2)}</td>
                <td>${producto.cantidad}</td>
                <td>RD$ ${importe.toFixed(2)}</td>
                <td class="noprint"><button type="button">Quitar</button></td>
            `;

            fila.querySelector('button').onclick = () => {
                carrito.delete(id);
                actualizarFactura();
            };

            cuerpo.appendChild(fila);
        });

        if (carrito.size === 0) {
            cuerpo.innerHTML = `
                <tr>
                    <td colspan="5">Agrega medicamentos para iniciar la factura.</td>
                </tr>
            `;
        }

        const descuentoPct = Number(document.getElementById('descuento_pct').value || 0);
        const descuento = total * descuentoPct / 100;
        const impuesto = (total - descuento) * tasaImpuesto / 100;
        document.getElementById('subtotal').textContent = total.toFixed(2);
        document.getElementById('descuento').textContent = descuento.toFixed(2);
        document.getElementById('impuesto').textContent = impuesto.toFixed(2);
        document.getElementById('total').textContent = (total - descuento + impuesto).toFixed(2);

        document.getElementById('items').value = JSON.stringify(
            [...carrito].map(([id, producto]) => ({
                id: id,
                cantidad: producto.cantidad
            }))
        );

        document.getElementById('cobrar').disabled = carrito.size === 0;
    }

    document.getElementById('agregar').onclick = () => {
        const opcion = selector.selectedOptions[0];

        if (!opcion.value) {
            return;
        }

        const id = opcion.value;
        const cantidad = Number(document.getElementById('cantidad').value);
        const cantidadExistente = carrito.get(id)?.cantidad || 0;
        const stock = Number(opcion.dataset.stock);

        if (!Number.isInteger(cantidad) || cantidad < 1) {
            alert('Indica una cantidad válida.');
            return;
        }

        if (cantidadExistente + cantidad > stock) {
            alert('Existencias insuficientes.');
            return;
        }

        carrito.set(id, {
            nombre: opcion.dataset.nombre,
            precio: Number(opcion.dataset.precio),
            cantidad: cantidadExistente + cantidad
        });

        actualizarFactura();
    };
    document.getElementById('descuento_pct').addEventListener('input', actualizarFactura);

    // Al seleccionar un medicamento, se agrega automáticamente una unidad.
    selector.addEventListener('change', () => {
        if (selector.value) {
            document.getElementById('cantidad').value = 1;
            document.getElementById('agregar').click();
            selector.value = '';
        }
    });

    document.getElementById('factura').onsubmit = (evento) => {
        if (
            carrito.size === 0 ||
            !confirm(
                '¿Confirmas la venta por RD$ ' +
                document.getElementById('total').textContent +
                '?'
            )
        ) {
            evento.preventDefault();
        }
    };
</script>
</body>
</html>
