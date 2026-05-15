-- --------------------------------------------------------
-- Base de Datos Unideportes
-- --------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 1. Crear y seleccionar la base de datos
CREATE DATABASE IF NOT EXISTS `unideportes` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `unideportes`;

-- 2. Tabla de USUARIOS
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','colaborador','vendedor') DEFAULT 'colaborador',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabla de CLIENTES
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(150) NOT NULL,
  `nit_cedula` varchar(30) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tipo_cliente` varchar(30) NOT NULL DEFAULT 'Individual',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_clientes_nit_cedula` (`nit_cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabla de PRODUCTOS
CREATE TABLE IF NOT EXISTS `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `referencia` varchar(50) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `talla` varchar(10) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `precio` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `referencia` (`referencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabla de VENTAS
CREATE TABLE IF NOT EXISTS `ventas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `vendedor_id` int(11) NOT NULL,
  `total_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ventas_cliente` (`cliente_id`),
  KEY `idx_ventas_vendedor` (`vendedor_id`),
  CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ventas_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabla de DETALLES DE VENTA
CREATE TABLE IF NOT EXISTS `detalles_venta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_detalles_venta` (`venta_id`),
  KEY `idx_detalles_producto` (`producto_id`),
  CONSTRAINT `fk_detalles_venta_id` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_detalles_producto_id` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Tabla de PEDIDOS
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `detalle` text NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `estado` enum('En Corte','En Costura','Terminado','Entregado') NOT NULL DEFAULT 'En Corte',
  `fecha_entrega` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pedidos_cliente` (`cliente_id`),
  KEY `idx_pedidos_estado` (`estado`),
  CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Tabla de DETALLE_PEDIDO (CORREGIDA)
CREATE TABLE IF NOT EXISTS `detalle_pedido` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_d_pedido` int(11) NOT NULL,
  `id_d_producto` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT 1,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_det_ped_pedido` (`id_d_pedido`),
  KEY `idx_det_ped_producto` (`id_d_producto`),
  CONSTRAINT `fk_det_pedido_rel` FOREIGN KEY (`id_d_pedido`) REFERENCES `pedidos` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_det_producto_rel` FOREIGN KEY (`id_d_producto`) REFERENCES `productos` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Tabla de PAGOS
CREATE TABLE IF NOT EXISTS `pagos` (
  `id_pago` int(11) NOT NULL AUTO_INCREMENT,
  `id_pg_pedido` int(11) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  PRIMARY KEY (`id_pago`),
  KEY `fk_pago_pedido` (`id_pg_pedido`),
  CONSTRAINT `fk_pago_pedido_rel` FOREIGN KEY (`id_pg_pedido`) REFERENCES `pedidos` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- DATOS DE PRUEBA
-- --------------------------------------------------------

INSERT INTO `usuarios` (`id`, `name`, `lastname`, `username`, `password`, `email`, `role`) VALUES
(1, 'Admin', 'Principal', 'admin', '$2y$10$7FV3L0RhxphrMhhszj2KcupvKxTRFOxJ0tY1zgaOTVogILwZSVVde', 'admin@unideportes.com', 'admin'),
(2, 'Joel', 'Castro', 'joel_dev', '$2y$10$d3MsHK0/r9PtMLXMCM1Sdu5c86YVHI7GQMwc9AjXi.XzTQiS5xLtu', 'joel@unideportes.com', 'colaborador'),
(3, 'Pablo', 'Rios', 'Pablo', '$2y$10$9mDI0hEEhXmKXu4DVCO/ee/1DRVNb0E5ERiBXBbqYKyZtxxMEyMBu', 'pablo@unideportes.com', 'colaborador'),
(5, 'Jonathan', 'Suarez', 'JonathanS', '$2y$10$VEwyaS6wWBzPA17Ywc3x9eEVwDim4oRLyufJv82bUXRyhRj3RFU76', 'jaysuarezap@gmail.com', 'vendedor');

INSERT INTO `clientes` (`id`, `nombre_completo`, `nit_cedula`, `telefono`, `email`, `tipo_cliente`) VALUES
(1, 'Cliente General', '000000', '000-000', NULL, 'Individual'),
(2, 'Las señoritas de la misericordia', '987654', '741258', NULL, 'Empresa'),
(3, 'Ramon Valdez', '78952147', '7155956', NULL, 'Individual');

INSERT INTO `productos` (`id`, `nombre`, `referencia`, `categoria`, `talla`, `stock`, `precio`) VALUES
(1, 'Camiseta Polo Azul', 'REF-001', 'Camisetas', 'S', 7, 20000.00),
(2, 'Pantaloneta Roja', 'REF-002', 'Pantalonetas', 'S', 12, 0.00),
(3, 'Camiseta Selección Colombia 2024', 'COL-HOME-01', 'Selección', 'M', 25, 249900.00),
(4, 'Camiseta Selección Colombia Visitante', 'COL-AWAY-02', 'Selección', 'L', 5, 249900.00),
(5, 'Balón Adidas Al Rihla Pro', 'BALL-QA22', 'Balones', '5', 12, 185000.00),
(6, 'Tenis Running UltraBoost', 'RUN-UB-22', 'Calzado', '40', 2, 650000.00),
(7, 'Sudadera Entrenamiento Negra', 'SUD-TR-05', 'Sudaderas', 'S', 18, 145000.00),
(8, 'Gorra Unideportes Classic', 'ACC-CAP-01', 'Accesorios', 'Única', 50, 45000.00),
(9, 'Guayos Predator Edge', 'GYO-AD-P', 'Calzado', '41', 3, 480000.00),
(10, 'Canilleras de Protección', 'PRO-CAN-02', 'Accesorios', 'M', 30, 35000.00);

INSERT INTO `pedidos` (`id`, `cliente_id`, `detalle`, `descripcion`, `cantidad`, `estado`, `fecha_entrega`) VALUES
(1, 1, 'Uniformes Local - Tela DryFit', 'Uniformes Local', 22, 'En Corte', '2026-03-25'),
(2, 1, 'Sudaderas Prom 2026', 'Sudaderas Prom', 45, 'En Costura', '2026-03-18');

INSERT INTO `ventas` (`id`, `cliente_id`, `vendedor_id`, `total_venta`, `fecha_venta`) VALUES
(2, 1, 1, 60000.00, '2026-05-02 19:36:53');

INSERT INTO `detalles_venta` (`id`, `venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 2, 1, 3, 20000.00, 60000.00);

-- Ajuste de Auto-Incrementos
ALTER TABLE `usuarios` AUTO_INCREMENT = 6;
ALTER TABLE `clientes` AUTO_INCREMENT = 4;
ALTER TABLE `productos` AUTO_INCREMENT = 11;
ALTER TABLE `ventas` AUTO_INCREMENT = 3;

// Ajuste en la tabla de ventas
ALTER TABLE `ventas` ADD COLUMN `metodo_pago` ENUM('Efectivo', 'Tarjeta', 'Transferencia', 'Otro') 
NOT NULL DEFAULT 'Efectivo' AFTER `total_venta`;

// cambios en pagos de fecha a datetime
ALTER TABLE `pagos` MODIFY COLUMN `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;