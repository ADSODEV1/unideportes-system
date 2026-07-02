-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 03, 2026 at 01:51 AM
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
(1, 'CLI-0001', 'Cliente General', '000000', '000-000', 'general@unideportes.com', 'Cll 15 # 14-12', 'El Rosario', 'Sogamoso', NULL, 'Individual', 'activo', '2026-05-12 00:39:13'),
(2, 'CLI-0002', 'Las señoritas de la misericordia', '987654', '741258', NULL, NULL, NULL, 'Sogamoso', NULL, 'Empresa', 'activo', '2026-05-12 00:39:13'),
(3, 'CLI-0003', 'Ramon Valdez', '78952147', '7155956', NULL, NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-05-12 00:39:13'),
(4, 'CLI-0004', 'Valeria Mora', '45678900', '3003435678', 'vale@gmail.com', NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-05-15 21:00:09'),
(5, 'CLI-0005', 'Facundo Cabral', '11678900', '30014563212', 'fc@gmail.com', NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-05-16 20:58:15'),
(6, 'CLI-0006', 'Lorena Unideportes', '345678945_9', '3185509709', 'lorena@unideportes.com', 'Calle 14 N 10-54', 'Centro', 'Sogamoso', 'Local 109', 'Empresa', 'activo', '2026-05-23 22:09:02'),
(12, 'CLI-00012', 'Benito Machuca', '11678009', '3219000892', 'benito@gmail.com', NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-05-25 17:09:40'),
(13, 'CLI-260625-885', 'Luis Suarez', '11223366', '3111234569', 'luis@unideportes.com', NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-06-25 17:31:13'),
(14, 'CLI-260625-682', 'Juan Perez', '1234567890', '3101234567', 'juan@gmail.com', NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-06-25 18:15:17'),
(15, 'CLI-260625-663', 'Luis Diaz', '1123456789', '3102345678', 'luisdiaz@gmail.com', NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-06-25 18:33:05'),
(16, 'CLI-260625-123', 'Luis Perez', '1123456781', '3102345671', 'luis_p2@gmail.com', NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-06-25 18:51:00'),
(17, 'CLI-260626-125', 'Juan Paz', '11236523-1', '3112302356', 'juanpaz@gmail.com', NULL, NULL, 'Sogamoso', NULL, 'Empresa', 'activo', '2026-06-26 04:09:48'),
(18, 'CLI-260626-266', 'Carlos Nuñes', '11552369', '3124569864', 'carlos5@gmail.com', NULL, NULL, 'Sogamoso', NULL, 'Individual', 'activo', '2026-06-26 20:04:46'),
(19, 'CLI-260627-890', 'Club Deportivo Real Sogamoso', '900123456-7', '3102345672', 'club@gmail.com', 'Calle 10 #5-20', 'El Rosario', 'Sogamoso', 'Frente al parque, casa de rejas negras y timbrar el el piso 2', 'Equipo', 'activo', '2026-06-27 02:30:38'),
(20, 'CLI-260627-980', 'Jose Paez', '900078906-3', '3101234559', 'josepaez@gmail.com', NULL, NULL, 'Sogamoso', NULL, 'Empresa', 'activo', '2026-06-27 19:19:02');

-- --------------------------------------------------------

--
-- Table structure for table `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `tipo_prenda_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`cantidad` * `precio_unitario`) STORED,
  `color` varchar(50) DEFAULT NULL,
  `talla` varchar(10) DEFAULT NULL,
  `comentario_vendedor` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`id`, `pedido_id`, `tipo_prenda_id`, `cantidad`, `precio_unitario`, `color`, `talla`, `comentario_vendedor`) VALUES
(1, 1, 1, 3, 25000.00, 'Azul', 'M', 'Despachar en empaque original'),
(2, 1, 2, 1, 85000.00, 'Negro', 'L', 'Cliente solicita revisar costuras antes de enviar'),
(3, 2, 2, 2, 85000.00, 'Blanco', 'S', NULL),
(4, 2, 3, 10, 12000.00, 'Rojo', 'Única', 'Pedido al por mayor para dotación'),
(15, 26, 6, 10, 85000.00, NULL, NULL, 'm'),
(16, 27, 4, 19, 180000.00, NULL, NULL, 'T 10 - 7 -, T 12 - 5, color azul con franja amarilla');

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
(22, 44, 15, 1, 85000.00, 85000.00, NULL, NULL, NULL),
(25, 47, 16, 5, 58900.00, 294500.00, 'Azul', 'M', 'Cuello mas pequeño v'),
(26, 48, 17, 1, 55000.00, 55000.00, 'Azul', 'XL', NULL),
(27, 48, 18, 1, 55000.00, 55000.00, 'Azul', 'M', NULL),
(28, 49, 19, 2, 40000.00, 80000.00, 'Negra', 'M', NULL),
(29, 50, 5, 5, 185000.00, 925000.00, NULL, '5', NULL),
(30, 51, 18, 15, 55000.00, 783750.00, 'Azul', '14', NULL),
(31, 52, 19, 10, 40000.00, 380000.00, 'Roja', 'M', NULL),
(32, 53, 15, 10, 85000.00, 807500.00, 'Negro', 'L', NULL),
(33, 54, 15, 10, 85000.00, 807500.00, 'negra', 'M', 'Pedido de prueba'),
(34, 55, 11, 8, 120000.00, 960000.00, 'Amarilla', 'L', NULL),
(35, 56, 3, 11, 249900.00, 2611455.00, 'Sin color', 'M', NULL),
(36, 57, 10, 1, 35000.00, 35000.00, NULL, 'M', NULL),
(37, 58, 16, 10, 58900.00, 559550.00, 'Azul', 'M', 'Ped prueba'),
(38, 59, 8, 20, 45000.00, 810000.00, 'Sin color', 'Única', 'fyukjmf'),
(39, 60, 20, 2, 35000.00, 70000.00, 'Negro', 'M', NULL),
(40, 61, 20, 10, 35000.00, 332500.00, 'Negro', 'M', 'Pedido para recoger en tienda en horas de la tarde'),
(41, 62, 15, 20, 85000.00, 1530000.00, 'negra', 'M', 'Empacar camisetas en caja'),
(42, 63, 22, 2, 75000.00, 150000.00, 'Negro', 'M', 'Venta de prueba'),
(43, 64, 21, 1, 50000.00, 50000.00, NULL, 'Unica', NULL),
(44, 65, 11, 2, 120000.00, 240000.00, 'Amarilla', 'L', NULL),
(45, 66, 22, 2, 75000.00, 150000.00, 'Negro', 'M', NULL),
(46, 67, 12, 1, 15000.00, 15000.00, 'azul', 'Única', NULL),
(48, 69, 1, 10, 20000.00, 200000.00, 'Sin color', 'S', NULL),
(49, 70, 17, 10, 55000.00, 550000.00, 'Azul', 'XL', NULL),
(50, 71, 4, 10, 249900.00, 2374050.00, 'azul', 'L', NULL),
(51, 72, 8, 10, 45000.00, 427500.00, 'Sin color', 'Única', 'Prueba'),
(52, 73, 18, 3, 55000.00, 148500.00, 'Azul', 'M', 'Abono'),
(53, 73, 17, 8, 55000.00, 396000.00, 'Azul', 'XL', 'Abono'),
(54, 73, 15, 9, 85000.00, 688500.00, 'negra', 'M', 'Abono'),
(55, 74, 15, 1, 85000.00, 85000.00, 'negra', 'M', NULL),
(56, 75, 1, 1, 20000.00, 20000.00, 'Azul', 'S', NULL),
(57, 76, 16, 5, 58900.00, 294500.00, 'Azul', 'M', NULL),
(58, 76, 22, 4, 75000.00, 300000.00, 'Negro', 'M', NULL),
(59, 77, 21, 10, 50000.00, 475000.00, 'negra', 'Unica', NULL),
(60, 78, 17, 10, 55000.00, 495000.00, 'Azul', 'XL', 'Tallas S en una caja.'),
(61, 78, 12, 10, 15000.00, 135000.00, 'azul', 'Única', 'Tallas S en una caja.'),
(62, 78, 4, 5, 249900.00, 1124550.00, 'azul', 'L', 'Tallas S en una caja.'),
(63, 79, 16, 1, 58900.00, 58900.00, 'Azul', 'M', NULL),
(64, 80, 15, 1, 85000.00, 85000.00, 'negra', 'M', NULL),
(65, 81, 16, 4, 58900.00, 223820.00, 'Azul', 'M', NULL),
(66, 81, 1, 6, 20000.00, 114000.00, 'Azul', 'S', NULL),
(67, 82, 23, 3, 45000.00, 135000.00, 'Roja', 'S', 'Tono rojo mas intenso'),
(68, 82, 22, 3, 75000.00, 225000.00, 'Negro', 'M', NULL),
(69, 82, 1, 3, 20000.00, 60000.00, 'Azul', 'S', NULL),
(70, 83, 3, 10, 249900.00, 2249100.00, 'Verde', 'M', NULL),
(71, 83, 21, 8, 50000.00, 360000.00, 'negra', 'Unica', NULL),
(72, 83, 15, 2, 85000.00, 153000.00, 'negra', 'M', NULL),
(73, 84, 1, 11, 20000.00, 209000.00, 'Azul', 'S', NULL),
(74, 85, 12, 11, 15000.00, 165000.00, 'azul', 'Única', NULL),
(75, 86, 1, 1, 20000.00, 20000.00, 'Azul', 'S', NULL),
(76, 87, 1, 7, 20000.00, 140000.00, 'Azul', 'S', NULL),
(77, 88, 15, 6, 85000.00, 510000.00, 'negra', 'M', NULL),
(78, 88, 11, 4, 120000.00, 480000.00, 'Amarilla', 'L', NULL),
(79, 89, 22, 15, 75000.00, 1125000.00, 'Negro', 'M', NULL),
(80, 90, 11, 9, 120000.00, 1080000.00, 'Amarilla', 'L', NULL),
(81, 91, 23, 20, 45000.00, 900000.00, 'Roja', 'S', NULL),
(82, 92, 20, 11, 35000.00, 385000.00, 'Negro', 'M', NULL);

--
-- Triggers `detalle_venta`
--
DELIMITER $$
CREATE TRIGGER `tg_actualizar_stock_venta` AFTER UPDATE ON `detalle_venta` FOR EACH ROW BEGIN
    IF OLD.`producto_id` = NEW.`producto_id` THEN
        UPDATE `productos`
        SET `stock` = `stock` + OLD.`cantidad` - NEW.`cantidad`
        WHERE `id` = NEW.`producto_id`;
    ELSE
        UPDATE `productos`
        SET `stock` = `stock` + OLD.`cantidad`
        WHERE `id` = OLD.`producto_id`;
        UPDATE `productos`
        SET `stock` = `stock` - NEW.`cantidad`
        WHERE `id` = NEW.`producto_id`;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_devolver_stock_venta` AFTER DELETE ON `detalle_venta` FOR EACH ROW BEGIN
    UPDATE `productos`
    SET `stock` = `stock` + OLD.`cantidad`
    WHERE `id` = OLD.`producto_id`;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_restar_stock_venta` AFTER INSERT ON `detalle_venta` FOR EACH ROW BEGIN
    UPDATE `productos`
    SET `stock` = `stock` - NEW.`cantidad`
    WHERE `id` = NEW.`producto_id`;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_pg_pedido` int(11) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `metodo_pago` enum('Efectivo','Tarjeta','Transferencia','Otro') DEFAULT 'Efectivo',
  `plataforma` varchar(30) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pagos`
--

INSERT INTO `pagos` (`id_pago`, `id_pg_pedido`, `monto`, `metodo_pago`, `plataforma`, `referencia`, `fecha`) VALUES
(1, 1, 500000.00, 'Efectivo', NULL, NULL, '2026-05-25 12:43:41'),
(2, 2, 3000000.00, 'Efectivo', NULL, NULL, '2026-05-25 12:43:41'),
(3, 3, 360000.00, 'Efectivo', NULL, NULL, '2026-05-25 12:43:41'),
(4, 3, 360000.00, 'Efectivo', NULL, NULL, '2026-05-26 15:44:45'),
(5, 2, 1500000.00, 'Efectivo', NULL, NULL, '2026-05-26 16:13:56'),
(6, 1, 600000.00, 'Efectivo', NULL, NULL, '2026-06-08 10:20:17'),
(9, 5, 500000.00, 'Efectivo', NULL, NULL, '2026-06-13 11:08:44'),
(10, 5, 625000.00, 'Efectivo', NULL, NULL, '2026-06-13 11:22:34'),
(11, NULL, 800000.00, 'Efectivo', NULL, NULL, '2026-06-16 17:30:51'),
(12, NULL, 50000000.00, 'Efectivo', NULL, NULL, '2026-06-16 19:06:47'),
(13, NULL, 8500000.00, 'Efectivo', NULL, NULL, '2026-06-16 20:35:38'),
(14, 9, 850000.00, 'Efectivo', NULL, NULL, '2026-06-17 12:07:44'),
(15, 10, 550000.00, 'Efectivo', NULL, NULL, '2026-06-25 16:46:04'),
(16, 11, 1500000.00, 'Efectivo', NULL, NULL, '2026-06-25 17:37:57'),
(17, 12, 315000.00, 'Efectivo', 'Nequi', NULL, '2026-06-25 17:51:25'),
(18, 13, 250000.00, 'Efectivo', 'Nequi', NULL, '2026-06-25 18:02:29'),
(19, 14, 250000.00, 'Efectivo', 'Nequi', NULL, '2026-06-26 22:56:11'),
(20, 9, 200000.00, 'Efectivo', NULL, NULL, '2026-06-26 23:22:11'),
(21, 9, 30000.00, 'Efectivo', NULL, NULL, '2026-06-26 23:22:32'),
(22, 26, 300000.00, 'Efectivo', 'Nequi', NULL, '2026-06-27 17:29:15'),
(23, 27, 1050000.00, 'Efectivo', 'Nequi', NULL, '2026-06-27 17:44:10'),
(24, 9, 500000.00, 'Efectivo', NULL, NULL, '2026-06-27 18:23:40'),
(25, 10, 3450000.00, 'Tarjeta', NULL, NULL, '2026-06-27 18:24:16');

-- --------------------------------------------------------

--
-- Table structure for table `pagos_venta`
--

CREATE TABLE `pagos_venta` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('Efectivo','Tarjeta','Transferencia') DEFAULT 'Efectivo',
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pagos_venta`
--

INSERT INTO `pagos_venta` (`id`, `venta_id`, `monto`, `metodo_pago`, `fecha`) VALUES
(1, 52, 377974.00, 'Tarjeta', '2026-06-16 16:13:26'),
(2, 54, 397500.00, 'Efectivo', '2026-06-16 16:14:14'),
(3, 53, 805474.00, 'Efectivo', '2026-06-16 16:14:50'),
(4, 51, 783750.00, 'Tarjeta', '2026-06-16 16:54:19'),
(5, 59, 310000.00, 'Tarjeta', '2026-06-16 19:27:00'),
(6, 61, 166250.00, 'Tarjeta', '2026-06-18 18:46:18'),
(7, 58, 259550.00, 'Efectivo', '2026-06-26 22:38:32'),
(8, 88, 500000.00, 'Transferencia', '2026-06-27 01:18:19'),
(9, 89, 600000.00, 'Efectivo', '2026-06-27 01:19:43'),
(10, 91, 450000.00, 'Efectivo', '2026-06-27 15:21:22'),
(11, 92, 370000.00, 'Tarjeta', '2026-06-27 15:24:15');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(17, 'admin2@unideportes.com', '78dcad57f4f98d48dd2923570045ec638c02fe90a4709c976140ec2ea43d5f3f', '2026-06-27 21:47:44', '2026-06-27 18:47:44');

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
  `tipo_entrega` varchar(50) DEFAULT 'Tienda',
  `direccion_entrega` varchar(255) DEFAULT NULL,
  `barrio_entrega` varchar(100) DEFAULT NULL,
  `ciudad_entrega` varchar(100) DEFAULT NULL,
  `observaciones_entrega` varchar(500) DEFAULT NULL,
  `fecha_entrega` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vendedor_id` int(11) DEFAULT NULL,
  `abono` decimal(10,2) DEFAULT NULL,
  `saldo_pendiente` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pedidos`
--

INSERT INTO `pedidos` (`id`, `cliente_id`, `detalle`, `descripcion`, `cantidad`, `total_pedido`, `estado`, `tipo_entrega`, `direccion_entrega`, `barrio_entrega`, `ciudad_entrega`, `observaciones_entrega`, `fecha_entrega`, `created_at`, `vendedor_id`, `abono`, `saldo_pendiente`) VALUES
(1, 2, '22 Uniformes de Fútbol - Inter de Sogamoso', 'Camiseta dry-fit con escudo bordado, pantaloneta y medias. Tallas: 10 M, 12 L.', 22, 1100000.00, 'Entregado', 'Tienda', NULL, NULL, NULL, NULL, '2026-06-15', '2026-05-25 17:43:41', NULL, NULL, NULL),
(2, 6, '50 Chaquetas Universitarias - Prom Lorena', 'Chaqueta impermeable con forro térmico y logo personalizado en la espalda.', 50, 4500000.00, 'Entregado', 'Tienda', NULL, NULL, NULL, NULL, '2026-06-10', '2026-05-25 17:43:41', NULL, NULL, NULL),
(3, 3, '12 Conjuntos de Baloncesto sobre medida', 'Camisilla y pantaloneta holgada con números estampados.', 12, 720000.00, 'Entregado', 'Tienda', NULL, NULL, NULL, NULL, '2026-06-28', '2026-05-25 17:43:41', NULL, NULL, NULL),
(4, 5, 'Pedido de confección mayorista', NULL, 1, 0.00, 'Entregado', 'Domicilio', 'Calle 3 este 9 20', 'rosario', 'Sogamoso', 'casa', '2026-06-25', '2026-06-10 20:08:36', NULL, 900000.00, 0.00),
(5, 12, '25 uniformes tipo Inter niño', 'Tela algodon 100', 25, 1125000.00, 'Entregado', 'Tienda', NULL, NULL, NULL, NULL, '2026-06-25', '2026-06-13 16:08:44', NULL, NULL, NULL),
(9, 12, '52 Uniformes de colegio la paz preescolar', 'Camisas cuello V', 52, 2080000.00, 'En Corte', 'Tienda', NULL, NULL, NULL, NULL, '2026-06-29', '2026-06-17 17:07:44', NULL, NULL, NULL),
(10, 4, '25 uniformes tipo Inter niño', 'Escudos bordadodos', 100, 4000000.00, 'Entregado', 'Tienda', NULL, NULL, NULL, NULL, '2026-07-07', '2026-06-25 21:46:04', NULL, NULL, NULL),
(11, 14, '25 uniformes de baloncesto para niño', 'tela polisster', 25, 4125000.00, 'En Costura', 'Tienda', NULL, NULL, NULL, NULL, '2026-07-11', '2026-06-25 22:37:57', 3, 1500000.00, 2625000.00),
(12, 15, 'Uniformes colegio san pedro', 'costura reforzadas', 25, 1050000.00, 'Terminado', 'Tienda', NULL, NULL, NULL, NULL, '2026-07-11', '2026-06-25 22:51:25', 3, 315000.00, 735000.00),
(13, 5, 'Uniformes colegio san benito', '', 18, 756000.00, 'Terminado', 'Tienda', NULL, NULL, NULL, NULL, '2026-07-11', '2026-06-25 23:02:29', 3, 250000.00, 506000.00),
(14, 19, 'Camiseta Niños', 'Tela Nylon, Talla 12, Color Azul, BalonMano, Escudo a la izquierda Leon amarillo,', 22, 792000.00, 'En Corte', 'Tienda', NULL, NULL, NULL, NULL, '2026-07-12', '2026-06-27 03:56:11', 3, 250000.00, 542000.00),
(26, 1, 'Camiseta Niños', 'Camiseta Niños', 10, 850000.00, 'En Corte', 'Tienda', NULL, NULL, NULL, NULL, '2026-07-13', '2026-06-27 22:29:15', 3, 300000.00, 550000.00),
(27, 19, 'Camiseta Niños', 'Pedido torneo microfutbol niños 12 años, tallas 10 - und 7, tallas 12 - und 5', 19, 3420000.00, 'En Corte', 'Tienda', NULL, NULL, NULL, NULL, '2026-07-13', '2026-06-27 22:44:10', 3, 1050000.00, 2370000.00);

-- --------------------------------------------------------

--
-- Table structure for table `precios_base_confeccion`
--

CREATE TABLE `precios_base_confeccion` (
  `id` int(11) NOT NULL,
  `tipo_prenda` varchar(100) NOT NULL,
  `precio_base` decimal(10,2) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `precios_base_confeccion`
--

INSERT INTO `precios_base_confeccion` (`id`, `tipo_prenda`, `precio_base`, `descripcion`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Pantaloneta', 45000.00, 'Pantaloneta deportiva básica', 1, '2026-06-25 22:12:14', '2026-06-25 22:12:14'),
(2, 'Camiseta manga corta', 35000.00, 'Camiseta deportiva manga corta', 1, '2026-06-25 22:12:14', '2026-06-25 22:12:14'),
(3, 'Camiseta manga larga', 42000.00, 'Camiseta deportiva manga larga', 1, '2026-06-25 22:12:14', '2026-06-25 22:12:14'),
(4, 'Uniforme completo fútbol', 180000.00, 'Camiseta + pantaloneta + medias', 1, '2026-06-25 22:12:14', '2026-06-25 22:12:14'),
(5, 'Uniforme completo baloncesto', 165000.00, 'Camiseta sin mangas + pantaloneta', 1, '2026-06-25 22:12:14', '2026-06-25 22:12:14'),
(6, 'Chaqueta deportiva', 85000.00, 'Chaqueta con cierre y bolsillos', 1, '2026-06-25 22:12:14', '2026-06-25 22:12:14'),
(7, 'Sudadera', 75000.00, 'Sudadera con capucha', 1, '2026-06-25 22:12:14', '2026-06-25 22:12:14'),
(8, 'Short deportivo', 38000.00, 'Short deportivo básico', 1, '2026-06-25 22:12:14', '2026-06-25 22:12:14');

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
(1, 'PROD-0001', 'Camiseta Polo V', 'REF-001', 'Camisetas', 'Azul', 'Nylon', 'Unisex', 'activo', '', 'S', 21, 'Unidad', 20000.00, '2026-05-12 00:39:13'),
(2, 'PROD-0002', 'Pantaloneta ', 'REF-002', 'Pantalonetas', 'Roja', 'Algodon', 'Hombre', 'activo', NULL, 'S', 10, 'Unidad', 35000.00, '2026-05-12 00:39:13'),
(3, 'PROD-0003', 'Camiseta Selección Colombia 2024', 'COL-HOME-01', 'Selección', 'Verde', 'Algodon', 'Unisex', 'activo', NULL, 'M', 0, 'Unidad', 249900.00, '2026-05-12 00:39:13'),
(4, 'PROD-0004', 'Camiseta Selección Colombia Visitante', 'COL-AWAY-02', 'Seleccion Col', 'azul', 'Nylon', 'unisex', 'activo', 'Camiseta Selección Colombia Visitante', 'L', 0, 'Unidad', 249900.00, '2026-05-12 00:39:13'),
(5, 'PROD-0005', 'Balón Adidas Al Rihla Pro', 'BALL-QA22', 'Balones', 'azul', 'Cintetico', 'Unisex', 'activo', 'Balón Adidas Al Rihla Pro', 'Única', 15, 'Unidad', 185000.00, '2026-05-12 00:39:13'),
(6, 'PROD-0006', 'Tenis Running UltraBoost', 'RUN-UB-22', 'Calzado', 'Sin color', 'Sin especificar', 'Unisex', 'activo', NULL, '40', 20, 'Unidad', 650000.00, '2026-05-12 00:39:13'),
(7, 'PROD-0007', 'Sudadera Entrenamiento ', 'SUD-TR-05', 'Sudaderas', 'negra', 'algodon', 'unisex', 'activo', 'Sudadera Entrenamiento unisex', 'S', 17, 'Unidad', 145000.00, '2026-05-12 00:39:13'),
(8, 'PROD-0008', 'Gorra Unideportes Classic', 'ACC-CAP-01', 'Accesorios', 'Sin color', 'Sin especificar', 'Unisex', 'activo', NULL, 'Única', 10, 'Unidad', 45000.00, '2026-05-12 00:39:13'),
(9, 'PROD-0009', 'Guayos Predator Edge', 'GYO-AD-P', 'Calzado', 'Sin color', 'Sin especificar', 'Unisex', 'activo', NULL, '41', 19, 'Unidad', 480000.00, '2026-05-12 00:39:13'),
(10, 'PROD-0010', 'Canilleras de Protección', 'PRO-CAN-02', 'Accesorios', 'Sin color', 'Sin especificar', 'Unisex', 'activo', NULL, 'M', 27, 'Unidad', 35000.00, '2026-05-12 00:39:13'),
(11, 'PROD-0011', 'Chaqueta Rompevientos Unideportes', 'CHA-ROM-01', 'Chaqueta', 'Amarilla', 'Corta Viento', 'Hombre', 'activo', 'Corta vientos hombre', 'L', -9, 'Unidad', 120000.00, '2026-05-26 01:16:38'),
(12, 'PROD-0012', 'Medias Ciclismo ', 'MED-CIC-02', 'Medias', 'azul', 'algodon', 'unisex', 'activo', 'Medias ciclismo', 'Única', 7, 'Unidad', 15000.00, '2026-05-26 01:16:38'),
(13, 'PROD-0013', 'Maletín Deportivo Gym', 'MAL-GYM-05', 'General', 'Sin color', 'Sin especificar', 'Unisex', 'activo', NULL, 'Única', 8, 'Unidad', 85000.00, '2026-05-26 01:16:38'),
(14, 'PROD-0014', 'Tula Deportiva Impermeable', 'TUL-IMP-09', 'General', 'Sin color', 'Sin especificar', 'Unisex', 'activo', NULL, 'Única', 25, 'Unidad', 25000.00, '2026-05-26 01:16:38'),
(15, 'PROD-0015', 'Camiseta Cuello V', 'CAMCUE-M-547', 'Camisetas', 'negra', 'algodon', 'Unisex', 'activo', 'Camiseta cuello v sencilla', 'M', 10, 'Unidad', 85000.00, '2026-05-26 03:37:04'),
(16, 'PROD-0016', 'Camiseta Polo', 'CAMPOL-M-431', 'Camisetas', 'Azul', 'Poliester', 'Hombre', 'activo', 'Camiseta polo sport hombre', 'M', 0, 'Unidad', 58900.00, '2026-06-08 17:06:20'),
(17, 'PROD-0017', 'Buso', 'BUSOXX-XL-374', 'Sudaderas', 'Azul', 'Nylon', 'Unisex', 'activo', 'Buso marca Unideportes diseño sport', 'XL', 15, 'Unidad', 55000.00, '2026-06-13 16:35:20'),
(18, 'PROD-0018', 'Buso', 'BUSOXX-M-792', 'Sudaderas', 'Azul', 'Nylon', 'Unisex', 'activo', 'Buso Unideportes Sport', 'M', 8, 'Unidad', 55000.00, '2026-06-13 16:39:14'),
(19, 'PROD-0019', 'Camiseta Micro', 'CAMMIC-M-568', 'Camisetas', 'Negra', 'Poliester', 'Unisex', 'activo', 'Camiseta diseñada para los amantes del futsal o futbol de salon con diseño personalizado', 'M', 30, 'Unidad', 40000.00, '2026-06-14 19:02:59'),
(20, 'PROD-0020', 'Short Deportivo Negro', 'SHO-DEP-01', 'Shorts', 'Negro', 'Poliester', 'Unisex', 'activo', 'Short deportivo para entrenamiento', 'M', -11, 'Unidad', 35000.00, '2026-06-18 20:52:53'),
(21, 'PROD-0021', 'Camiseta Futsal', 'camiseta', 'Camiseta unisex para deporte futsal', 'negra', 'Nylon', 'Unisex', 'activo', '', 'Única', 10, 'Unidad', 50000.00, '2026-06-18 21:35:21'),
(22, 'PROD-0022', 'Pantalon Deportivo Negro', 'PAN-DEP-01', 'Pantalones', 'Negro', 'Poliester', 'Unisex', 'activo', 'Pantalon deportivo para entrenamiento', 'M', -13, 'Unidad', 75000.00, '2026-06-19 18:00:05'),
(23, NULL, 'Camiseta Deportiva', 'CAM-DAM-001', 'Camisetas', 'Roja', 'Poliéster', 'Dama', 'activo', 'Camiseta deportiva de alto rendimiento', 'S', 7, 'Unidad', 45000.00, '2026-06-26 04:15:48'),
(24, NULL, 'Camiseta Deportiva', 'CAM-NI-001', 'Camisetas', 'Verde', 'Poliéster', 'Niños', 'activo', 'Camiseta deportiva de alto rendimiento', '12', 50, 'Unidad', 45000.00, '2026-06-26 04:17:31'),
(25, NULL, 'Camiseta Deportiva', 'CAM-NIN-001', 'Camisetas', 'Verde', 'Poliéster', 'Niños', 'activo', 'Camiseta deportiva de alto rendimiento', '16', 50, 'Unidad', 45000.00, '2026-06-26 04:18:34');

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
(1, 'Admin', 'Principal', 'admin', '$2y$10$frP94r.LbtDioA81uIV00Or5H0yIr.K7SNSbk1ExRmpvuagcLFDlK', 'admin1@unideportes.com', 'admin', '2026-05-12 00:39:11'),
(2, 'Joel', 'Castro', 'joel_dev', '$2y$10$COcs9nq1Z1PkNbUYgFQrl.EIYQ3dHUfUTP8NN4DARbAgnJmN0.Kry', 'joel@unideportes.com', 'admin', '2026-05-12 00:39:11'),
(3, 'Pablo', 'Rios', 'Pablo', '$2y$10$ppDYaZs/kKgLulMaP7kAaeVLD3UaeRFiAVEQq.FSsgWpwz9JGW8NO', 'pablo@unideportes.com', 'vendedor', '2026-05-12 00:39:11'),
(5, 'Jonathan', 'Suarez', 'JonathanS', '$2y$10$COcs9nq1Z1PkNbUYgFQrl.EIYQ3dHUfUTP8NN4DARbAgnJmN0.Kry', 'jaysuarezap@gmail.com', 'vendedor', '2026-05-12 00:39:11'),
(8, 'Administrador Dos', 'Central', 'admin_sena', '$2y$10$M9rWvXexamplehashforadminpassworddontchange', 'admin2@unideportes.com', 'admin', '2026-05-26 18:50:40'),
(9, 'Vendedor Nuevo', 'Fabrica', 'Colaborador02', '$2y$10$ILG8DHzkcBKoJUcgXaYOd.94ujhPspoboxgK7feweT1J1yf0nklNm', 'ventas2@unideportes.com', 'colaborador', '2026-05-26 18:50:40');

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
  `descuento_monto` decimal(10,2) DEFAULT 0.00,
  `tipo_venta` enum('directa','mayorista') DEFAULT 'directa',
  `metodo_pago` enum('Efectivo','Tarjeta','Transferencia','Otro') NOT NULL DEFAULT 'Efectivo',
  `tipo_entrega` enum('Tienda','Domicilio') NOT NULL DEFAULT 'Tienda',
  `costo_envio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `direccion_entrega` varchar(255) DEFAULT NULL,
  `barrio_entrega` varchar(100) DEFAULT NULL,
  `ciudad_entrega` varchar(100) DEFAULT NULL,
  `observaciones_entrega` text DEFAULT NULL,
  `observaciones_venta_mayor` text DEFAULT NULL,
  `cambio` decimal(10,2) DEFAULT 0.00,
  `abono` decimal(10,2) DEFAULT 0.00,
  `saldo_pendiente` decimal(10,2) DEFAULT 0.00,
  `estado` enum('Pendiente','Entregado','Cancelado') DEFAULT 'Pendiente',
  `tipo_transferencia` varchar(30) DEFAULT NULL,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_entrega` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ventas`
--

INSERT INTO `ventas` (`id`, `codigo_descriptivo`, `ticket_numero`, `cliente_id`, `vendedor_id`, `total_venta`, `descuento_monto`, `tipo_venta`, `metodo_pago`, `tipo_entrega`, `costo_envio`, `direccion_entrega`, `barrio_entrega`, `ciudad_entrega`, `observaciones_entrega`, `observaciones_venta_mayor`, `cambio`, `abono`, `saldo_pendiente`, `estado`, `tipo_transferencia`, `fecha_venta`, `fecha_entrega`) VALUES
(2, 'VEN-000002', 'FAC-000002', 1, 1, 60000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-02 19:36:53', NULL),
(3, 'VEN-000003', 'FAC-000003', 2, 3, 145000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-11 14:44:03', NULL),
(4, 'VEN-000004', 'FAC-000004', 4, 3, 810000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-15 11:56:57', NULL),
(5, 'VEN-000005', 'FAC-000005', 3, 3, 45000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-15 12:06:15', NULL),
(6, 'VEN-000006', 'FAC-000006', 4, 3, 480000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-15 12:08:20', NULL),
(7, 'VEN-000007', 'FAC-000007', 3, 3, 145000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-15 17:44:31', NULL),
(8, 'VEN-000008', 'FAC-000008', 3, 3, 185000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-16 10:14:49', NULL),
(9, 'VEN-000009', 'FAC-000009', 3, 3, 4669500.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-16 10:26:57', NULL),
(10, 'VEN-000010', 'FAC-000010', 4, 3, 2070000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-16 10:47:57', NULL),
(11, 'VEN-000011', 'FAC-000011', 5, 3, 185000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-17 13:02:09', NULL),
(12, 'VEN-000012', 'FAC-000012', 5, 3, 20000.00, 0.00, 'directa', 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', 'Breve', '2026-05-17 13:13:45', NULL),
(13, 'VEN-000013', 'FAC-000013', 5, 3, 185000.00, 0.00, 'directa', 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', 'Breve', '2026-05-17 13:20:10', NULL),
(14, 'VEN-000014', 'FAC-000014', 5, 3, 290000.00, 0.00, 'directa', 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', 'Bancolombia', '2026-05-17 13:51:45', NULL),
(15, 'VEN-000015', 'FAC-000015', 5, 3, 185000.00, 0.00, 'directa', 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', 'Bancolombia', '2026-05-18 14:54:07', NULL),
(16, 'VEN-000016', 'FAC-000016', 5, 3, 145000.00, 0.00, 'directa', 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', 'Daviplata', '2026-05-20 09:08:29', NULL),
(17, 'VEN-000017', 'FAC-000017', 1, 3, 480000.00, 0.00, 'directa', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-20 09:11:23', NULL),
(18, 'VEN-000018', 'FAC-000018', 5, 3, 45000.00, 0.00, 'directa', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-20 09:14:51', NULL),
(19, 'VEN-000019', 'FAC-000019', 1, 3, 0.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-20 10:17:31', NULL),
(20, 'VEN-000020', 'FAC-000020', 5, 3, 0.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-20 10:27:14', NULL),
(21, 'VEN-000021', 'FAC-000021', 3, 3, 0.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-20 10:47:44', NULL),
(23, 'VEN-000023', 'FAC-000023', 5, 3, 0.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-20 10:59:37', NULL),
(25, 'VEN-000025', 'FAC-000025', 3, 3, 0.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-20 11:06:17', NULL),
(26, 'VEN-000026', 'FAC-000026', 5, 3, 0.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-20 11:39:33', NULL),
(27, 'VEN-000027', 'FAC-000027', 4, 3, 0.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-20 11:49:26', NULL),
(28, 'VEN-000028', 'FAC-000028', 3, 3, 0.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-20 12:00:37', NULL),
(31, 'VEN-000031', 'FAC-000031', 5, 3, 0.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-20 12:05:41', NULL),
(32, 'VEN-000032', 'FAC-000032', 1, 3, 0.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-20 12:12:24', NULL),
(33, 'VEN-000033', 'FAC-000033', 5, 3, 249900.00, 0.00, 'directa', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-20 12:36:20', NULL),
(34, 'VEN-000034', 'FAC-000034', 3, 2, 249900.00, 0.00, 'directa', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-22 00:17:38', NULL),
(35, 'VEN-000035', 'FAC-000035', 5, 2, 249900.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-22 00:27:24', NULL),
(37, 'VEN-000037', 'FAC-000037', 5, 3, 249900.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-23 13:09:09', NULL),
(38, 'VEN-000038', 'FAC-000038', 5, 3, 249900.00, 0.00, 'directa', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-23 13:12:56', NULL),
(39, 'VEN-000039', 'FAC-000039', 6, 3, 249900.00, 0.00, 'directa', 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', 'Nequi', '2026-05-23 13:16:39', NULL),
(40, 'VEN-000040', 'T-20260524212424-434', 6, 3, 249900.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-24 14:24:24', NULL),
(41, 'VEN-000041', 'T-20260524215507-819', 3, 3, 249900.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-24 14:55:07', NULL),
(42, 'VEN-000042', 'T-20260525190940-871', 12, 3, 254900.00, 0.00, 'directa', 'Tarjeta', 'Domicilio', 5000.00, 'Calle 14 B BIS 6 - 38', 'sadsad', 'Sogamoso', 'Puerta Roja', NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-25 12:09:40', NULL),
(43, 'VEN-000043', 'T-20260526155241-769', 3, 3, 249900.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 100.00, 0.00, 0.00, 'Pendiente', NULL, '2026-05-26 08:52:41', NULL),
(44, 'VEN-000044', 'T-20260526235209-569', 12, 3, 85000.00, 0.00, 'directa', 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', 'Nequi', '2026-05-26 16:52:09', NULL),
(45, 'VEN-000045', 'FACT-001', 1, 1, 50000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-06-08 15:13:36', NULL),
(47, 'VEN-000047', 'T-20260613181804-470', 3, 3, 294500.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 5500.00, 0.00, 0.00, 'Pendiente', NULL, '2026-06-13 11:18:04', NULL),
(48, 'VEN-000048', 'T-20260613183959-669', 5, 3, 110000.00, 0.00, 'directa', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-06-13 11:39:59', NULL),
(49, 'VEN-000049', 'T-20260614210913-797', 3, 3, 80000.00, 0.00, 'directa', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-06-14 14:09:13', NULL),
(50, 'VEN-000050', 'T-20260616172452-227', 12, 3, 925000.00, 0.00, 'directa', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-06-16 10:24:52', NULL),
(51, 'VEN-000051', 'M-20260616201731-935', 12, 3, 783750.00, 41250.00, 'mayorista', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, '                                ', 'Separate from Yahoo probably ', 400000.00, 0.00, 0.00, 'Entregado', '', '2026-06-16 13:17:31', '2026-06-23'),
(52, 'VEN-000052', 'M-20260616211720-798', 1, 3, 380000.00, 20000.00, 'mayorista', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, '', 'Venta al por mayor de prueba', 0.00, 2026.00, 0.00, 'Entregado', '', '2026-06-16 14:17:20', NULL),
(53, 'VEN-000053', 'M-20260616212439-208', 6, 3, 807500.00, 42500.00, 'mayorista', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, '', 'Para el Colegio Nacional', 0.00, 2026.00, 0.00, 'Entregado', '', '2026-06-16 14:24:39', NULL),
(54, 'VEN-000054', 'M-20260616223530-516', 3, 3, 807500.00, 42500.00, 'mayorista', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, '', 'Pedido de prueba', 0.00, 410000.00, 397500.00, 'Entregado', '', '2026-06-16 15:35:30', '2026-06-23'),
(55, 'VEN-000055', 'T-20260617013337-349', 5, 3, 960000.00, 0.00, 'directa', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-06-16 18:33:37', NULL),
(56, 'VEN-000056', 'M-20260617013811-446', 3, 3, 2611455.00, 137445.00, 'mayorista', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, '', NULL, 0.00, 2611455.00, 0.00, 'Entregado', '', '2026-06-16 18:38:11', '2026-06-24'),
(57, 'VEN-000057', 'T-20260617015352-828', 1, 3, 35000.00, 0.00, 'directa', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-06-16 18:53:52', NULL),
(58, 'VEN-000058', 'M-20260617015446-731', 4, 3, 559550.00, 29450.00, 'mayorista', 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, '', 'Ped prueba', 0.00, 300000.00, 259550.00, 'Entregado', 'Nequi', '2026-06-16 18:54:46', '2026-06-24'),
(59, 'VEN-000059', 'M-20260617020742-591', 4, 3, 810000.00, 90000.00, 'mayorista', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, '', 'fyukjmf', 0.00, 500000.00, 310000.00, 'Entregado', '', '2026-06-16 19:07:42', '2026-06-24'),
(60, 'VEN-000060', 'T-20260619004638-233', 12, 3, 70000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 30000.00, 0.00, 0.00, 'Pendiente', NULL, '2026-06-18 17:46:38', NULL),
(61, 'VEN-000061', 'M-20260619013345-561', 5, 1, 332500.00, 17500.00, 'mayorista', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, '', 'Pedido para recoger en tienda en horas de la tarde', 0.00, 166250.00, 166250.00, 'Entregado', '', '2026-06-18 18:33:45', '2026-06-26'),
(62, 'VEN-000062', 'M-20260619033237-304', 2, 3, 1530000.00, 170000.00, 'mayorista', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, '', 'Empacar camisetas en caja', 0.00, 1530000.00, 0.00, 'Entregado', '', '2026-06-18 20:32:37', '2026-06-26'),
(63, 'VEN-000063', 'T-20260620040619-820', 1, 3, 150000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Pendiente', NULL, '2026-06-19 21:06:19', NULL),
(64, 'VEN-000064', 'T-20260620161907-282', 12, 3, 50000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Entregado', NULL, '2026-06-20 09:19:07', NULL),
(65, 'VEN-000065', 'T-20260620171159-766', 5, 3, 240000.00, 0.00, 'directa', 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Entregado', 'Nequi', '2026-06-20 10:11:59', NULL),
(66, 'VEN-000066', 'T-20260620174251-366', 3, 3, 150000.00, 0.00, 'directa', 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Entregado', 'Daviplata', '2026-06-20 10:42:51', NULL),
(67, 'VEN-000067', 'T-20260620220243-793', 1, 3, 15000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Entregado', NULL, '2026-06-20 15:02:43', NULL),
(69, 'VEN-000069', 'T-20260621005108-557', 3, 3, 200000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Entregado', NULL, '2026-06-20 17:51:08', NULL),
(70, 'VEN-000070', 'T-20260621010216-563', 4, 3, 550000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Entregado', NULL, '2026-06-20 18:02:16', NULL),
(71, 'VEN-000071', 'M-20260621010356-983', 3, 3, 2374050.00, 124950.00, 'mayorista', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 1300000.00, 1074050.00, 'Entregado', NULL, '2026-06-20 18:03:56', '2026-06-21'),
(72, 'VEN-000072', 'M-20260623174236-823', 6, 3, 427500.00, 22500.00, 'mayorista', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 'Prueba', 0.00, 427500.00, 0.00, 'Entregado', NULL, '2026-06-23 10:42:36', '2026-06-23'),
(73, 'VEN-000073', 'M-20260623182720-690', 3, 3, 1233000.00, 137000.00, 'mayorista', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 'Abono', 0.00, 650000.00, 583000.00, 'Entregado', NULL, '2026-06-23 11:27:20', '2026-06-23'),
(74, NULL, 'T-20260625215806-276', 16, 3, 85000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 5000.00, 0.00, 0.00, 'Entregado', NULL, '2026-06-25 14:58:06', NULL),
(75, 'VEN-000075', 'T-20260625225248-859', 5, 3, 20000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Entregado', NULL, '2026-06-25 15:52:48', NULL),
(76, 'VEN-000076', 'T-20260625232521-595', 3, 3, 594500.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 5500.00, 0.00, 0.00, 'Entregado', NULL, '2026-06-25 16:25:21', NULL),
(77, 'VEN-000077', 'M-20260625233159-894', 14, 3, 475000.00, 25000.00, 'mayorista', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 475000.00, 0.00, 'Entregado', NULL, '2026-06-25 16:31:59', '2026-06-25'),
(78, 'VEN-000078', 'M-20260625233859-870', 2, 3, 1754550.00, 194950.00, 'mayorista', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 'Tallas XL en una caja.', 0.00, 1754796.00, -246.00, 'Entregado', NULL, '2026-06-25 16:38:59', '2026-06-25'),
(79, 'VEN-000079', 'T-20260626055416-943', 5, 3, 58900.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 1100.00, 0.00, 0.00, 'Entregado', NULL, '2026-06-25 22:54:16', NULL),
(80, 'VEN-000080', 'T-20260626220655-442', 18, 3, 85000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'Entregado', NULL, '2026-06-26 15:06:55', NULL),
(81, 'VEN-000081', 'M-20260626224315-332', 17, 3, 337820.00, 17780.00, 'mayorista', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 338000.00, -180.00, 'Entregado', NULL, '2026-06-26 15:43:15', '2026-06-26'),
(82, 'VEN-000082', 'T-20260627050452-736', 19, 3, 425000.00, 0.00, 'directa', 'Efectivo', 'Domicilio', 5000.00, 'Calle 10 #5-20', 'El Rosario', 'Sogamoso', 'Frente al parque, casa de rejas negras y timbrar el el piso 2', NULL, 25000.00, 0.00, 0.00, 'Entregado', NULL, '2026-06-26 22:04:52', NULL),
(83, 'VEN-000083', 'M-20260627053557-354', 19, 3, 2767100.00, 306900.00, 'mayorista', 'Tarjeta', 'Domicilio', 5000.00, 'Calle 10 #5-20', 'El Rosario', 'Sogamoso', NULL, NULL, 0.00, 1400000.00, 1367100.00, 'Entregado', NULL, '2026-06-26 22:35:57', '2026-06-27'),
(84, 'VEN-000084', 'M-20260627065113-276', 17, 3, 209000.00, 11000.00, 'mayorista', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 110000.00, 99000.00, 'Entregado', NULL, '2026-06-26 23:51:13', '2026-06-27'),
(85, 'VEN-000085', 'M-20260627073038-982', 17, 1, 156750.00, 8250.00, 'mayorista', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 90000.00, 66750.00, 'Pendiente', NULL, '2026-06-27 00:30:38', '2026-07-04'),
(86, 'VEN-000086', 'T-20260627074838-299', 3, 3, 20000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 20000.00, 0.00, 'Entregado', NULL, '2026-06-27 00:48:38', '2026-07-04'),
(87, 'VEN-000087', 'T-20260627080401-519', 19, 3, 140000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 140000.00, 0.00, 'Entregado', NULL, '2026-06-27 01:04:01', '2026-07-04'),
(88, 'VEN-000088', 'M-20260627081819-266', 19, 3, 940500.00, 49500.00, 'mayorista', 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 500000.00, 440500.00, 'Pendiente', 'Nequi', '2026-06-27 01:18:19', '2026-07-04'),
(89, 'VEN-000089', 'M-20260627081943-646', 2, 3, 1073750.00, 56250.00, 'mayorista', 'Efectivo', 'Domicilio', 5000.00, NULL, NULL, 'Sogamoso', NULL, NULL, 0.00, 600000.00, 473750.00, 'Entregado', NULL, '2026-06-27 01:19:43', '2026-07-04'),
(90, 'VEN-000090', 'T-20260627215622-776', 20, 3, 1080000.00, 0.00, 'directa', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 20000.00, 1080000.00, 0.00, 'Entregado', NULL, '2026-06-27 14:56:22', '2026-07-04'),
(91, 'VEN-000091', 'M-20260627222122-345', 20, 3, 810000.00, 90000.00, 'mayorista', 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 450000.00, 360000.00, 'Pendiente', NULL, '2026-06-27 15:21:22', '2026-07-04'),
(92, 'VEN-000092', 'M-20260627222415-412', 2, 3, 365750.00, 19250.00, 'mayorista', 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, 370000.00, -4250.00, 'Entregado', NULL, '2026-06-27 15:24:15', '2026-07-04');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vista_saldos_pedidos`
-- (See below for the actual view)
--
CREATE TABLE `vista_saldos_pedidos` (
`id` int(11)
,`total_pedido` decimal(10,2)
,`saldo_pendiente` decimal(33,2)
);

-- --------------------------------------------------------

--
-- Structure for view `vista_saldos_pedidos`
--
DROP TABLE IF EXISTS `vista_saldos_pedidos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_saldos_pedidos`  AS SELECT `p`.`id` AS `id`, `p`.`total_pedido` AS `total_pedido`, `p`.`total_pedido`- ifnull(sum(`pg`.`monto`),0) AS `saldo_pendiente` FROM (`pedidos` `p` left join `pagos` `pg` on(`p`.`id` = `pg`.`id_pg_pedido`)) GROUP BY `p`.`id` ;

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
  ADD KEY `fk_detalle_pedido_productos` (`tipo_prenda_id`);

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
-- Indexes for table `pagos_venta`
--
ALTER TABLE `pagos_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pedidos_cliente` (`cliente_id`),
  ADD KEY `idx_pedidos_estado` (`estado`),
  ADD KEY `idx_vendedor_id` (`vendedor_id`);

--
-- Indexes for table `precios_base_confeccion`
--
ALTER TABLE `precios_base_confeccion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipo_prenda` (`tipo_prenda`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `pagos_venta`
--
ALTER TABLE `pagos_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `precios_base_confeccion`
--
ALTER TABLE `precios_base_confeccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `fk_detalle_pedido_pedidos_rel` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_pedido_prenda` FOREIGN KEY (`tipo_prenda_id`) REFERENCES `precios_base_confeccion` (`id`) ON UPDATE CASCADE;

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
-- Constraints for table `pagos_venta`
--
ALTER TABLE `pagos_venta`
  ADD CONSTRAINT `pagos_venta_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE;

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
