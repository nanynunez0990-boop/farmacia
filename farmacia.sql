-- Ejecutar en phpMyAdmin sobre la base de datos farmacia.
CREATE TABLE IF NOT EXISTS productos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(180) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_productos_codigo (codigo),
    KEY idx_productos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ventas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero VARCHAR(30) NOT NULL,
    id_cliente VARCHAR(30) NULL,
    cliente VARCHAR(150) NOT NULL DEFAULT 'Consumidor final',
    metodo_pago VARCHAR(30) NOT NULL DEFAULT 'Efectivo',
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ventas_numero (numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detalle_venta (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    venta_id BIGINT UNSIGNED NOT NULL,
    producto_id INT UNSIGNED NOT NULL,
    nombre_producto VARCHAR(180) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_detalle_venta (venta_id),
    KEY idx_detalle_producto (producto_id),
    CONSTRAINT fk_detalle_venta FOREIGN KEY (venta_id) REFERENCES ventas(id),
    CONSTRAINT fk_detalle_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
