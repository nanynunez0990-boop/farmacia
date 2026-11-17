-- Ejecutar una sola vez desde phpMyAdmin en la base de datos farmacia.
-- El sistema parte de las tablas productos, ventas y detalle_venta existentes.

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    correo VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uq_usuarios_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO usuarios (nombre, correo, password_hash)
VALUES ('Farmacia', 'farmacia@gmail.com', '$2y$10$Uj9V80p/FUOr2QyvIw5TPODHLdIFszgJ.eJhYcEbxryKoOUe83n..');

CREATE TABLE IF NOT EXISTS clientes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    documento VARCHAR(30) NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    telefono VARCHAR(40) NULL,
    correo VARCHAR(190) NULL,
    direccion VARCHAR(255) NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uq_clientes_documento (documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proveedores (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(180) NOT NULL,
    documento VARCHAR(40) NULL,
    telefono VARCHAR(40) NULL,
    correo VARCHAR(190) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lotes_producto (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    producto_id INT UNSIGNED NOT NULL,
    numero_lote VARCHAR(80) NOT NULL,
    fecha_vencimiento DATE NULL,
    cantidad_inicial INT UNSIGNED NOT NULL,
    cantidad_disponible INT UNSIGNED NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), KEY idx_lotes_producto_vencimiento (producto_id, fecha_vencimiento),
    CONSTRAINT fk_lotes_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- El inventario previo se conserva como lote inicial sin vencimiento conocido.
INSERT INTO lotes_producto (producto_id, numero_lote, fecha_vencimiento, cantidad_inicial, cantidad_disponible)
SELECT p.id, CONCAT('INICIAL-', p.id), NULL, p.stock, p.stock
FROM productos p
WHERE p.stock > 0
  AND NOT EXISTS (SELECT 1 FROM lotes_producto l WHERE l.producto_id = p.id);

CREATE TABLE IF NOT EXISTS compras (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    proveedor_id INT UNSIGNED NOT NULL,
    numero VARCHAR(60) NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    usuario_id INT UNSIGNED NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), UNIQUE KEY uq_compras_numero (numero),
    CONSTRAINT fk_compras_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
    CONSTRAINT fk_compras_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detalle_compra (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    compra_id BIGINT UNSIGNED NOT NULL,
    producto_id INT UNSIGNED NOT NULL,
    lote_id BIGINT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    costo_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_detalle_compra_compra FOREIGN KEY (compra_id) REFERENCES compras(id),
    CONSTRAINT fk_detalle_compra_producto FOREIGN KEY (producto_id) REFERENCES productos(id),
    CONSTRAINT fk_detalle_compra_lote FOREIGN KEY (lote_id) REFERENCES lotes_producto(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS movimientos_inventario (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    producto_id INT UNSIGNED NOT NULL,
    lote_id BIGINT UNSIGNED NULL,
    tipo VARCHAR(30) NOT NULL,
    cantidad INT NOT NULL,
    referencia VARCHAR(80) NULL,
    usuario_id INT UNSIGNED NULL,
    nota VARCHAR(255) NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_mov_producto FOREIGN KEY (producto_id) REFERENCES productos(id),
    CONSTRAINT fk_mov_lote FOREIGN KEY (lote_id) REFERENCES lotes_producto(id),
    CONSTRAINT fk_mov_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracion (
    clave VARCHAR(80) NOT NULL,
    valor VARCHAR(255) NOT NULL,
    PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO configuracion (clave, valor) VALUES ('impuesto_pct', '0'), ('descuento_max_pct', '100');

-- Columnas nuevas para controlar mínimos, impuestos, anulaciones y auditoría.
ALTER TABLE productos ADD COLUMN stock_minimo INT UNSIGNED NOT NULL DEFAULT 5;
ALTER TABLE ventas
    ADD COLUMN descuento DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN impuesto_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN impuesto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN estado ENUM('completada','anulada') NOT NULL DEFAULT 'completada',
    ADD COLUMN motivo_anulacion VARCHAR(255) NULL,
    ADD COLUMN usuario_id INT UNSIGNED NULL,
    ADD KEY idx_ventas_fecha_estado (fecha, estado),
    ADD CONSTRAINT fk_ventas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id);
ALTER TABLE detalle_venta ADD COLUMN lote_id BIGINT UNSIGNED NULL;
ALTER TABLE detalle_venta ADD CONSTRAINT fk_detalle_lote FOREIGN KEY (lote_id) REFERENCES lotes_producto(id);
