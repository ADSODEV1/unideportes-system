-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2026 at 06:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `unideportes`
--

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
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
-- Dumping data for table `clientes`
--

INSERT INTO `clientes` (`id`, `codigo_descriptivo`, `nombre_completo`, `nit_cedula`, `telefono`, `email`, `direccion`, `barrio`, `ciudad`, `referencia_entrega`, `tipo_cliente`, `estado`, `created_at`) VALUES
(1, 'CLI-0001', 'Cliente General', '000000', '000-000', NULL, NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-05-12 00:39:13'),
(2, 'CLI-0002', 'Las señoritas de la misericordia', '987654', '741258', NULL, NULL, NULL, 'Sogamoso', NULL, 'Empresa', 'activo', '2026-05-12 00:39:13'),
(3, 'CLI-0003', 'Ramon Valdez', '78952147', '7155956', NULL, NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-05-12 00:39:13'),
(4, 'CLI-0004', 'Valeria Mora', '45678900', '3003435678', 'vale@gmail.com', NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-05-15 21:00:09'),
(5, 'CLI-0005', 'Facundo Cabral', '11678900', '30014563212', 'fc@gmail.com', NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-05-16 20:58:15'),
(6, 'CLI-0006', 'Lorena Unideportes', '345678945_9', '3185509709', 'lorena@unideportes.com', 'Calle 14 N 10-54', 'Centro', 'Sogamoso', 'Local 109', 'Empresa', 'activo', '2026-05-23 22:09:02'),
(12, '', 'Benito Machuca', '11678009', '3219000892', 'benito@gmail.com', NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-05-25 17:09:40');

-- --------------------------------------------------------

--
-- Table structure for table `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`cantidad` * `precio_unitario`) STORED,
  `color` varchar(50) DEFAULT NULL,
  `talla` varchar(10) DEFAULT NULL,
  `comentario_vendedor` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `talla` varchar(10) DEFAULT NULL,
  `comentario_vendedor` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detalle_venta`
--

INSERT INTO `detalle_venta` (`id`, `venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`, `color`, `talla`, `comentario_vendedor`) VALUES
(2, 26, 1, 2, 100.00, 200.00, NULL, NULL, NULL),
(3, 26, 1, 2, 100.00, 200.00, NULL, NULL, NULL),
(4, 33, 3, 1, 249900.00, 249900.00, NULL, NULL, NULL),
(5, 34, 3, 1, 249900.00, 249900.00, NULL, NULL, NULL),
(6, 35, 4, 1, 249900.00, 249900.00, NULL, NULL, NULL),
(8, 37, 4, 1, 249900.00, 249900.00, NULL, NULL, NULL),
(9, 38, 4, 1, 249900.00, 249900.00, NULL, NULL, NULL),
(10, 39, 3, 1, 249900.00, 249900.00, NULL, NULL, NULL),
(18, 40, 4, 1, 249900.00, 249900.00, NULL, NULL, NULL),
(19, 41, 4, 1, 249900.00, 249900.00, NULL, NULL, NULL),
(20, 42, 3, 1, 249900.00, 249900.00, NULL, NULL, NULL),
(21, 43, 3, 1, 249900.00, 249900.00, NULL, NULL, NULL),
(22, 44, 15, 1, 85000.00, 85000.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_pg_pedido` int(11) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pagos`
--

INSERT INTO `pagos` (`id_pago`, `id_pg_pedido`, `monto`, `fecha`) VALUES
(1, 1, 500000.00, '2026-05-25 12:43:41'),
(2, 2, 3000000.00, '2026-05-25 12:43:41'),
(3, 3, 360000.00, '2026-05-25 12:43:41'),
(4, 3, 360000.00, '2026-05-26 15:44:45'),
(5, 2, 1500000.00, '2026-05-26 16:13:56'),
(6, 1, 600000.00, '2026-06-08 10:20:17');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pedidos`
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vendedor_id` int(11) DEFAULT NULL,
  `abono` decimal(10,2) DEFAULT NULL,
  `saldo_pendiente` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pedidos`
--

INSERT INTO `pedidos` (`id`, `cliente_id`, `detalle`, `descripcion`, `cantidad`, `total_pedido`, `estado`, `fecha_entrega`, `created_at`, `vendedor_id`, `abono`, `saldo_pendiente`) VALUES
(1, 2, '22 Uniformes de Fútbol - Inter de Sogamoso', 'Camiseta dry-fit con escudo bordado, pantaloneta y medias. Tallas: 10 M, 12 L.', 22, 1100000.00, 'Entregado', '2026-06-15', '2026-05-25 17:43:41', NULL, NULL, NULL),
(2, 6, '50 Chaquetas Universitarias - Prom Lorena', 'Chaqueta impermeable con forro térmico y logo personalizado en la espalda.', 50, 4500000.00, 'Entregado', '2026-06-10', '2026-05-25 17:43:41', NULL, NULL, NULL),
(3, 3, '12 Conjuntos de Baloncesto sobre medida', 'Camisilla y pantaloneta holgada con números estampados.', 12, 720000.00, 'Entregado', '2026-06-28', '2026-05-25 17:43:41', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo_descriptivo` varchar(20) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `referencia` varchar(50) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `material` varchar(50) DEFAULT NULL,
  `genero` varchar(20) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `descripcion` text DEFAULT NULL,
  `talla` varchar(10) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `unidad` varchar(50) DEFAULT 'Unidad',
  `precio` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productos`
--

INSERT INTO `productos` (`id`, `codigo_descriptivo`, `nombre`, `referencia`, `categoria`, `color`, `material`, `genero`, `estado`, `descripcion`, `talla`, `stock`, `unidad`, `precio`, `created_at`) VALUES
(1, 'PROD-0001', 'Camiseta Polo Azul', 'REF-001', 'Camisetas', NULL, NULL, NULL, 'activo', NULL, 'S', 19, 'Unidad', 20000.00, '2026-05-12 00:39:13'),
(2, 'PROD-0002', 'Pantaloneta Roja', 'REF-002', 'Pantalonetas', NULL, NULL, NULL, 'activo', NULL, 'S', 9, 'Unidad', 0.00, '2026-05-12 00:39:13'),
(3, 'PROD-0003', 'Camiseta Selección Colombia 2024', 'COL-HOME-01', 'Selección', NULL, NULL, NULL, 'activo', NULL, 'M', 21, 'Unidad', 249900.00, '2026-05-12 00:39:13'),
(4, 'PROD-0004', 'Camiseta Selección Colombia Visitante', 'COL-AWAY-02', 'Selección', NULL, NULL, NULL, 'activo', NULL, 'L', 15, 'Unidad', 249900.00, '2026-05-12 00:39:13'),
(5, 'PROD-0005', 'Balón Adidas Al Rihla Pro', 'BALL-QA22', 'Balones', NULL, NULL, NULL, 'activo', NULL, '5', 17, 'Unidad', 185000.00, '2026-05-12 00:39:13'),
(6, 'PROD-0006', 'Tenis Running UltraBoost', 'RUN-UB-22', 'Calzado', NULL, NULL, NULL, 'activo', NULL, '40', 20, 'Unidad', 650000.00, '2026-05-12 00:39:13'),
(7, 'PROD-0007', 'Sudadera Entrenamiento Negra', 'SUD-TR-05', 'Sudaderas', NULL, NULL, NULL, 'activo', NULL, 'S', 17, 'Unidad', 145000.00, '2026-05-12 00:39:13'),
(8, 'PROD-0008', 'Gorra Unideportes Classic', 'ACC-CAP-01', 'Accesorios', NULL, NULL, NULL, 'activo', NULL, 'Única', 30, 'Unidad', 45000.00, '2026-05-12 00:39:13'),
(9, 'PROD-0009', 'Guayos Predator Edge', 'GYO-AD-P', 'Calzado', NULL, NULL, NULL, 'activo', NULL, '41', 19, 'Unidad', 480000.00, '2026-05-12 00:39:13'),
(10, 'PROD-0010', 'Canilleras de Protección', 'PRO-CAN-02', 'Accesorios', NULL, NULL, NULL, 'activo', NULL, 'M', 29, 'Unidad', 35000.00, '2026-05-12 00:39:13'),
(11, NULL, 'Chaqueta Rompevientos Unideportes', 'CHA-ROM-01', NULL, NULL, NULL, NULL, 'activo', NULL, 'L', 15, 'Unidad', 120000.00, '2026-05-26 01:16:38'),
(12, NULL, 'Medias Ciclismo Negras', 'MED-CIC-02', NULL, NULL, NULL, NULL, 'activo', NULL, 'Única', 40, 'Unidad', 15000.00, '2026-05-26 01:16:38'),
(13, NULL, 'Maletín Deportivo Gym', 'MAL-GYM-05', NULL, NULL, NULL, NULL, 'activo', NULL, 'Única', 8, 'Unidad', 85000.00, '2026-05-26 01:16:38'),
(14, NULL, 'Tula Deportiva Impermeable', 'TUL-IMP-09', NULL, NULL, NULL, NULL, 'activo', NULL, 'Única', 25, 'Unidad', 25000.00, '2026-05-26 01:16:38'),
(15, NULL, 'Camiseta Cuello V', 'CAMCUE-M-547', 'Camisetas', NULL, NULL, NULL, 'activo', NULL, 'M', 49, 'Unidad', 85000.00, '2026-05-26 03:37:04');

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
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
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `name`, `lastname`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'Admin', 'Principal', 'admin', '$2y$10$oZlWCHxkLWRyaEF1mOvzzOuHTv4EUxdmB3Z/m9ZP4i9Dj7mI./ZkW', 'admin@unideportes.com', 'admin', '2026-05-12 00:39:11'),
(2, 'Joel', 'Castro', 'joel_dev', '$2y$10$MgVEakN4Aw0PbSAJtzgg2.ub83ZnW.2U.wduFxFY5E54c3UgOwnuO', 'joel@unideportes.com', 'admin', '2026-05-12 00:39:11'),
(3, 'Pablo', 'Rios', 'Pablo', '$2y$10$lw5ofRmHQ2eWpHJwG/qvbuxeyrr7vNcZ.QeNdcA9h.0FnRPzZB6xu', 'pablo@unideportes.com', 'colaborador', '2026-05-12 00:39:11'),
(5, 'Jonathan', 'Suarez', 'JonathanS', '$2y$10$rNeSPoTbGVM34ZAuhdE/Be1tXDrpDXUiE6p2uEi6Eb2.Q4pW3A3PO', 'jaysuarezap@gmail.com', 'vendedor', '2026-05-12 00:39:11'),
(8, 'Administrador Dos', 'Central', 'admin_sena', '$2y$10$M9rWvXexamplehashforadminpassworddontchange', 'admin2@unideportes.com', 'admin', '2026-05-26 18:50:40'),
(9, 'Vendedor Nuevo', 'Caja', 'vendedor02', '$2y$10$M9rWvXexamplehashforvendedorpassworddontchange', 'ventas2@unideportes.com', 'vendedor', '2026-05-26 18:50:40');

-- --------------------------------------------------------

--
-- Table structure for table `ventas`
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
-- Dumping data for table `ventas`
--

INSERT INTO `ventas` (`id`, `codigo_descriptivo`, `ticket_numero`, `cliente_id`, `vendedor_id`, `total_venta`, `metodo_pago`, `tipo_entrega`, `costo_envio`, `direccion_entrega`, `barrio_entrega`, `ciudad_entrega`, `observaciones_entrega`, `cambio`, `tipo_transferencia`, `fecha_venta`) VALUES
(2, 'VEN-000002', 'FAC-000002', 1, 1, 60000.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-02 19:36:53'),
(3, 'VEN-000003', 'FAC-000003', 2, 3, 145000.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-11 14:44:03'),
(4, 'VEN-000004', 'FAC-000004', 4, 3, 810000.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-15 11:56:57'),
(5, 'VEN-000005', 'FAC-000005', 3, 3, 45000.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-15 12:06:15'),
(6, 'VEN-000006', 'FAC-000006', 4, 3, 480000.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-15 12:08:20'),
(7, 'VEN-000007', 'FAC-000007', 3, 3, 145000.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-15 17:44:31'),
(8, 'VEN-000008', 'FAC-000008', 3, 3, 185000.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-16 10:14:49'),
(9, 'VEN-000009', 'FAC-000009', 3, 3, 4669500.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-16 10:26:57'),
(10, 'VEN-000010', 'FAC-000010', 4, 3, 2070000.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-16 10:47:57'),
(11, 'VEN-000011', 'FAC-000011', 5, 3, 185000.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-17 13:02:09'),
(12, 'VEN-000012', 'FAC-000012', 5, 3, 20000.00, 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, 'Breve', '2026-05-17 13:13:45'),
(13, 'VEN-000013', 'FAC-000013', 5, 3, 185000.00, 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, 'Breve', '2026-05-17 13:20:10'),
(14, 'VEN-000014', 'FAC-000014', 5, 3, 290000.00, 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, 'Bancolombia', '2026-05-17 13:51:45'),
(15, 'VEN-000015', 'FAC-000015', 5, 3, 185000.00, 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, 'Bancolombia', '2026-05-18 14:54:07'),
(16, 'VEN-000016', 'FAC-000016', 5, 3, 145000.00, 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, 'Daviplata', '2026-05-20 09:08:29'),
(17, 'VEN-000017', 'FAC-000017', 1, 3, 480000.00, 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-20 09:11:23'),
(18, 'VEN-000018', 'FAC-000018', 5, 3, 45000.00, 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-20 09:14:51'),
(19, 'VEN-000019', 'FAC-000019', 1, 3, 0.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-20 10:17:31'),
(20, 'VEN-000020', 'FAC-000020', 5, 3, 0.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-20 10:27:14'),
(21, 'VEN-000021', 'FAC-000021', 3, 3, 0.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-20 10:47:44'),
(23, 'VEN-000023', 'FAC-000023', 5, 3, 0.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-20 10:59:37'),
(25, 'VEN-000025', 'FAC-000025', 3, 3, 0.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-20 11:06:17'),
(26, 'VEN-000026', 'FAC-000026', 5, 3, 0.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-20 11:39:33'),
(27, 'VEN-000027', 'FAC-000027', 4, 3, 0.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-20 11:49:26'),
(28, 'VEN-000028', 'FAC-000028', 3, 3, 0.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-20 12:00:37'),
(31, 'VEN-000031', 'FAC-000031', 5, 3, 0.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-20 12:05:41'),
(32, 'VEN-000032', 'FAC-000032', 1, 3, 0.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-20 12:12:24'),
(33, 'VEN-000033', 'FAC-000033', 5, 3, 249900.00, 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-20 12:36:20'),
(34, 'VEN-000034', 'FAC-000034', 3, 2, 249900.00, 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-22 00:17:38'),
(35, 'VEN-000035', 'FAC-000035', 5, 2, 249900.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-22 00:27:24'),
(37, 'VEN-000037', 'FAC-000037', 5, 3, 249900.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-23 13:09:09'),
(38, 'VEN-000038', 'FAC-000038', 5, 3, 249900.00, 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-23 13:12:56'),
(39, 'VEN-000039', 'FAC-000039', 6, 3, 249900.00, 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, 'Nequi', '2026-05-23 13:16:39'),
(40, NULL, 'T-20260524212424-434', 6, 3, 249900.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-24 14:24:24'),
(41, NULL, 'T-20260524215507-819', 3, 3, 249900.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-05-24 14:55:07'),
(42, NULL, 'T-20260525190940-871', 12, 3, 254900.00, 'Tarjeta', 'Domicilio', 5000.00, 'Calle 14 B BIS 6 - 38', 'sadsad', 'Sogamoso', 'Puerta Roja', 0.00, NULL, '2026-05-25 12:09:40'),
(43, NULL, 'T-20260526155241-769', 3, 3, 249900.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 100.00, NULL, '2026-05-26 08:52:41'),
(44, NULL, 'T-20260526235209-569', 12, 3, 85000.00, 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, 'Nequi', '2026-05-26 16:52:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_clientes_nit_cedula` (`nit_cedula`);

--
-- Indexes for table `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detalle_pedido_pedidos` (`pedido_id`),
  ADD KEY `fk_detalle_pedido_productos` (`producto_id`);

--
-- Indexes for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indexes for table `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `fk_pago_pedido` (`id_pg_pedido`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pedidos_cliente` (`cliente_id`),
  ADD KEY `idx_pedidos_estado` (`estado`),
  ADD KEY `idx_vendedor_id` (`vendedor_id`);

--
-- Indexes for table `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `referencia` (`referencia`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_numero` (`ticket_numero`),
  ADD KEY `idx_ventas_cliente` (`cliente_id`),
  ADD KEY `idx_ventas_vendedor` (`vendedor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `fk_detalle_pedido_pedidos_rel` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_pedido_productos_rel` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pago_pedido_rel` FOREIGN KEY (`id_pg_pedido`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventas_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
