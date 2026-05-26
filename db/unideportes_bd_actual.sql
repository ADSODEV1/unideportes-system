
-- --------------------------------------------------------
-- Base de Datos Unideportes
-- --------------------------------------------------------
 
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-05-2026 a las 23:40:18
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12


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
 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `unideportes`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `codigo_descriptivo` varchar(20) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `nit_cedula` varchar(30) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `barrio` varchar(100) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT 'Sogamoso',
  `referencia_entrega` text DEFAULT NULL,
  `tipo_cliente` varchar(30) NOT NULL DEFAULT 'Individual',
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre_completo`, `nit_cedula`, `telefono`, `tipo_cliente`, `email`) VALUES
(1, 'Cliente General', '000000', '000-000', NULL, NULL),
(2, 'Las señoritas de la misericordia', '987654', '741258', 'Empresa', NULL),
(3, 'Ramon Valdez', '78952147', '7155956', 'Individual', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_venta`
--

CREATE TABLE `detalle_venta` (
  `id` int(11) NOT NULL,
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
 
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalles_venta` (`id`, `venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 2, 1, 3, 20000.00, 60000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id` int(11) NOT NULL,
  `id_d_pedido` int(11) DEFAULT NULL,
  `id_d_producto` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_pg_pedido` int(11) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `fecha` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id_pago`, `id_pg_pedido`, `monto`, `fecha`) VALUES
(1, 1, 500000.00, '2026-05-25 12:43:41'),
(2, 2, 3000000.00, '2026-05-25 12:43:41'),
(3, 3, 360000.00, '2026-05-25 12:43:41'),
(4, 3, 360000.00, '2026-05-26 15:44:45'),
(5, 2, 1500000.00, '2026-05-26 16:13:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `detalle` text NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `total_pedido` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('En Corte','En Costura','Terminado','Entregado') NOT NULL DEFAULT 'En Corte',
  `fecha_entrega` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `cliente_id`, `detalle`, `descripcion`, `cantidad`, `total_pedido`, `estado`, `fecha_entrega`, `created_at`) VALUES
(1, 2, '22 Uniformes de Fútbol - Inter de Sogamoso', 'Camiseta dry-fit con escudo bordado, pantaloneta y medias. Tallas: 10 M, 12 L.', 22, 1100000.00, 'En Costura', '2026-06-15', '2026-05-25 17:43:41'),
(2, 6, '50 Chaquetas Universitarias - Prom Lorena', 'Chaqueta impermeable con forro térmico y logo personalizado en la espalda.', 50, 4500000.00, 'Entregado', '2026-06-10', '2026-05-25 17:43:41'),
(3, 3, '12 Conjuntos de Baloncesto sobre medida', 'Camisilla y pantaloneta holgada con números estampados.', 12, 720000.00, 'Entregado', '2026-06-28', '2026-05-25 17:43:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo_descriptivo` varchar(20) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `referencia` varchar(50) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `talla` varchar(10) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `unidad` varchar(50) DEFAULT 'Unidad',
  `precio` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo_descriptivo`, `nombre`, `referencia`, `categoria`, `talla`, `stock`, `unidad`, `precio`, `created_at`) VALUES
(1, 'PROD-0001', 'Camiseta Polo Azul', 'REF-001', 'Camisetas', 'S', 19, 'Unidad', 20000.00, '2026-05-12 00:39:13'),
(2, 'PROD-0002', 'Pantaloneta Roja', 'REF-002', 'Pantalonetas', 'S', 9, 'Unidad', 0.00, '2026-05-12 00:39:13'),
(3, 'PROD-0003', 'Camiseta Selección Colombia 2024', 'COL-HOME-01', 'Selección', 'M', 21, 'Unidad', 249900.00, '2026-05-12 00:39:13'),
(4, 'PROD-0004', 'Camiseta Selección Colombia Visitante', 'COL-AWAY-02', 'Selección', 'L', 15, 'Unidad', 249900.00, '2026-05-12 00:39:13'),
(5, 'PROD-0005', 'Balón Adidas Al Rihla Pro', 'BALL-QA22', 'Balones', '5', 17, 'Unidad', 185000.00, '2026-05-12 00:39:13'),
(6, 'PROD-0006', 'Tenis Running UltraBoost', 'RUN-UB-22', 'Calzado', '40', 20, 'Unidad', 650000.00, '2026-05-12 00:39:13'),
(7, 'PROD-0007', 'Sudadera Entrenamiento Negra', 'SUD-TR-05', 'Sudaderas', 'S', 17, 'Unidad', 145000.00, '2026-05-12 00:39:13'),
(8, 'PROD-0008', 'Gorra Unideportes Classic', 'ACC-CAP-01', 'Accesorios', 'Única', 30, 'Unidad', 45000.00, '2026-05-12 00:39:13'),
(9, 'PROD-0009', 'Guayos Predator Edge', 'GYO-AD-P', 'Calzado', '41', 19, 'Unidad', 480000.00, '2026-05-12 00:39:13'),
(10, 'PROD-0010', 'Canilleras de Protección', 'PRO-CAN-02', 'Accesorios', 'M', 29, 'Unidad', 35000.00, '2026-05-12 00:39:13'),
(11, NULL, 'Chaqueta Rompevientos Unideportes', 'CHA-ROM-01', NULL, 'L', 15, 'Unidad', 120000.00, '2026-05-26 01:16:38'),
(12, NULL, 'Medias Ciclismo Negras', 'MED-CIC-02', NULL, 'Única', 40, 'Unidad', 15000.00, '2026-05-26 01:16:38'),
(13, NULL, 'Maletín Deportivo Gym', 'MAL-GYM-05', NULL, 'Única', 8, 'Unidad', 85000.00, '2026-05-26 01:16:38'),
(14, NULL, 'Tula Deportiva Impermeable', 'TUL-IMP-09', NULL, 'Única', 25, 'Unidad', 25000.00, '2026-05-26 01:16:38'),
(15, NULL, 'Camiseta Cuello V', 'CAMCUE-M-547', 'Camisetas', 'M', 50, 'Unidad', 85000.00, '2026-05-26 03:37:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','colaborador','vendedor') DEFAULT 'colaborador',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `name`, `lastname`, `username`, `password`, `email`, `role`) VALUES
(1, 'Admin', 'Principal', 'admin', '$2y$10$7FV3L0RhxphrMhhszj2KcupvKxTRFOxJ0tY1zgaOTVogILwZSVVde', 'admin@unideportes.com', 'admin'),
(2, 'Joel', 'Castro', 'joel_dev', '$2y$10$d3MsHK0/r9PtMLXMCM1Sdu5c86YVHI7GQMwc9AjXi.XzTQiS5xLtu', 'joel@unideportes.com', 'colaborador'),
(3, 'Pablo', 'Rios', 'Pablo', '$2y$10$9mDI0hEEhXmKXu4DVCO/ee/1DRVNb0E5ERiBXBbqYKyZtxxMEyMBu', 'pablo@unideportes.com', 'colaborador'),
(5, 'Jonathan ', 'Suarez', 'JonathanS', '$2y$10$VEwyaS6wWBzPA17Ywc3x9eEVwDim4oRLyufJv82bUXRyhRj3RFU76', 'jaysuarezap@gmail.com', 'vendedor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `codigo_descriptivo` varchar(20) DEFAULT NULL,
  `ticket_numero` varchar(20) DEFAULT NULL,
  `cliente_id` int(11) NOT NULL,
  `vendedor_id` int(11) NOT NULL,
  `total_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` enum('Efectivo','Tarjeta','Transferencia','Otro') NOT NULL DEFAULT 'Efectivo',
  `tipo_entrega` enum('Tienda','Domicilio') NOT NULL DEFAULT 'Tienda',
  `costo_envio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `direccion_entrega` varchar(255) DEFAULT NULL,
  `barrio_entrega` varchar(100) DEFAULT NULL,
  `ciudad_entrega` varchar(100) DEFAULT NULL,
  `observaciones_entrega` text DEFAULT NULL,
  `cambio` decimal(10,2) DEFAULT 0.00,
  `tipo_transferencia` varchar(30) DEFAULT NULL,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `cliente_id`, `vendedor_id`, `total_venta`, `fecha_venta`) VALUES
(2, 1, 1, 57000.00, '2026-05-02 19:36:53');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_clientes_nit_cedula` (`nit_cedula`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `fk_pago_pedido` (`id_pg_pedido`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pedidos_cliente` (`cliente_id`),
  ADD KEY `idx_pedidos_estado` (`estado`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `referencia` (`referencia`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_numero` (`ticket_numero`),
  ADD KEY `idx_ventas_cliente` (`cliente_id`),
  ADD KEY `idx_ventas_vendedor` (`vendedor_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pago_pedido_rel` FOREIGN KEY (`id_pg_pedido`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventas_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

