-- Crear base de datos
CREATE DATABASE IF NOT EXISTS sistema_pedidos;
USE sistema_pedidos;

-- Tabla de categorías
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla de productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    imagen VARCHAR(255),
    categoria_id INT,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Tabla de clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de pedidos
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'confirmado', 'preparando', 'en_camino', 'entregado', 'cancelado') DEFAULT 'pendiente',
    total DECIMAL(10,2) NOT NULL,
    notas TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

-- Tabla de detalles de pedido
CREATE TABLE pedido_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    producto_id INT,
    cantidad INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Insertar datos de ejemplo
INSERT INTO categorias (nombre, descripcion) VALUES 
('Entradas', 'Deliciosas entradas para comenzar tu comida'),
('Platos Fuertes', 'Nuestros platos principales'),
('Postres', 'Dulces tentaciones para finalizar'),
('Bebidas', 'Refrescantes bebidas');

INSERT INTO productos (nombre, descripcion, precio, categoria_id) VALUES 
('Ensalada César', 'Lechuga romana, crutones, parmesano y aderezo césar', 8.99, 1),
('Sopa del Día', 'Sopa casera preparada diariamente', 6.50, 1),
('Pasta Alfredo', 'Fettuccine con salsa cremosa de parmesano', 12.99, 2),
('Pizza Margherita', 'Tomate, mozzarella y albahaca fresca', 10.99, 2),
('Tiramisú', 'Postre italiano clásico con café y cacao', 7.50, 3),
('Cheesecake', 'Tarta de queso con base de galleta', 6.99, 3),
('Refresco', 'Bebida gaseosa de 500ml', 2.50, 4),
('Jugo Natural', 'Jugo de frutas naturales', 3.50, 4);