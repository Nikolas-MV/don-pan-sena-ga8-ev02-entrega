-- Solo esquema; aplicar exclusivamente mediante inicializador de laboratorio validado.
-- =========================================================
-- Panadería "Don Pan" - Esquema de Base de Datos
-- Basado en: MAPA DE PROCESOS (Conexión 1-4, Flujos 1-3)
-- =========================================================


-- Conexión 1: Turno A <-> Turno B (historial continuo, sin pérdida de info)
CREATE TABLE turnos (
    id_turno INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,           -- Mañana, Tarde, Noche
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL
);

CREATE TABLE empleados (
    id_empleado INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('dueño','empleada') NOT NULL DEFAULT 'empleada',
    id_turno INT,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_turno) REFERENCES turnos(id_turno)
);

-- Categorías (1FN: evita la columna multi-valor "Panes, Artesanal")
CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255)
);

-- Conexión 2: Ventas <-> Inventario (stock en tiempo real)
CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 5,   -- gatilla alerta de reposición
    codigo_barra VARCHAR(50) UNIQUE,
    id_categoria INT NULL,                  -- Producto N:1 Categoria (MER)
    activo TINYINT(1) NOT NULL DEFAULT 1,   -- 0 = dado de baja (no se vende)
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
);

-- Conexión 4: Cliente <-> Sistema (historial de compras, opcional)
CREATE TABLE clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(120),
    direccion VARCHAR(150),
    puntos INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1    -- 0 = dado de baja
);

-- Flujo 1: Venta de productos (registro digital, reemplaza el cuaderno)
CREATE TABLE ventas (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    id_turno INT NOT NULL,
    id_cliente INT NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    metodo_pago ENUM('efectivo','tarjeta','transferencia') NOT NULL DEFAULT 'efectivo',
    anulada TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = anulada (no cuenta en reportes)
    anulada_por INT NULL,                    -- empleado que realizo la anulacion
    fecha_anulacion DATETIME NULL,
    FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado),
    FOREIGN KEY (id_turno) REFERENCES turnos(id_turno),
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente),
    FOREIGN KEY (anulada_por) REFERENCES empleados(id_empleado)
);

CREATE TABLE detalle_ventas (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
);

-- Flujo 2: Control de inventario (entradas de proveedor / ajustes)
CREATE TABLE movimientos_inventario (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    tipo ENUM('entrada','salida','ajuste') NOT NULL,
    cantidad INT NOT NULL,
    motivo VARCHAR(150),
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
);

-- Índices de apoyo para reportes (Flujo 3: Toma de decisiones en tiempo real)
CREATE INDEX idx_ventas_fecha ON ventas(fecha_hora);
CREATE INDEX idx_mov_fecha ON movimientos_inventario(fecha_hora);
CREATE INDEX idx_ventas_anulada ON ventas(anulada);

-- =========================================================
-- Módulo de insumos y compras (MER GA6-220501096-AA1-EV02)
-- Materias primas, recetas de producción y proveedores.
-- =========================================================

CREATE TABLE insumos (
    id_insumo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    unidad VARCHAR(20) NOT NULL DEFAULT 'kg',
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 5
);

-- Tabla intermedia que resuelve la relación N:N Producto <-> Insumo
CREATE TABLE recetas (
    id_receta INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    id_insumo INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE,
    FOREIGN KEY (id_insumo) REFERENCES insumos(id_insumo)
);

CREATE TABLE proveedores (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    contacto VARCHAR(100),
    telefono VARCHAR(20),
    email VARCHAR(120),
    activo TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE compras (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    id_empleado INT NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    anulada TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = anulada
    anulada_por INT NULL,
    fecha_anulacion DATETIME NULL,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor),
    FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado),
    FOREIGN KEY (anulada_por) REFERENCES empleados(id_empleado)
);

CREATE TABLE detalle_compras (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_insumo INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_compra) REFERENCES compras(id_compra) ON DELETE CASCADE,
    FOREIGN KEY (id_insumo) REFERENCES insumos(id_insumo)
);

CREATE INDEX idx_compras_fecha ON compras(fecha_hora);
CREATE INDEX idx_recetas_producto ON recetas(id_producto);
CREATE UNIQUE INDEX idx_recetas_unicidad ON recetas(id_producto, id_insumo);
CREATE INDEX idx_compras_anulada ON compras(anulada);

-- Historial de movimientos de insumos. Va aparte de movimientos_inventario
-- porque esa tabla tiene clave foranea a productos, no a insumos.
CREATE TABLE movimientos_insumos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_insumo INT NOT NULL,
    id_empleado INT NULL,
    tipo ENUM('entrada','salida','ajuste') NOT NULL,
    cantidad INT NOT NULL,
    motivo VARCHAR(150),
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_insumo) REFERENCES insumos(id_insumo),
    FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado)
);

CREATE INDEX idx_mov_insumos_insumo ON movimientos_insumos(id_insumo);
CREATE INDEX idx_mov_insumos_fecha ON movimientos_insumos(fecha_hora);
