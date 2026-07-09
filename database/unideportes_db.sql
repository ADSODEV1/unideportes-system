-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-07-2026 a las 19:16:21
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `unideportes`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_sync_cartera` (IN `p_id` INT)   UPDATE detalle_pedido dp
INNER JOIN pedidos p ON p.id = dp.pedido_id
LEFT JOIN (SELECT id_pg_pedido, SUM(monto) AS total_pagado FROM pagos GROUP BY id_pg_pedido) pg ON pg.id_pg_pedido = dp.pedido_id
SET 
    dp.total_pedido = COALESCE((SELECT SUM(dp2.cantidad * dp2.precio_unitario) FROM detalle_pedido dp2 WHERE dp2.pedido_id = dp.pedido_id), p.total_pedido, 0),
    dp.abono_pedido = COALESCE(p.abono, 0),
    dp.pagos_registrados = COALESCE(pg.total_pagado, 0),
    dp.saldo_pendiente = GREATEST(0, COALESCE((SELECT SUM(dp3.cantidad * dp3.precio_unitario) FROM detalle_pedido dp3 WHERE dp3.pedido_id = dp.pedido_id), p.total_pedido, 0) - (COALESCE(p.abono, 0) + COALESCE(pg.total_pagado, 0))),
    dp.estado_cartera = CASE WHEN GREATEST(0, COALESCE((SELECT SUM(dp4.cantidad * dp4.precio_unitario) FROM detalle_pedido dp4 WHERE dp4.pedido_id = dp.pedido_id), p.total_pedido, 0) - (COALESCE(p.abono, 0) + COALESCE(pg.total_pagado, 0))) <= 0 THEN 'Pagado' ELSE 'Por Pagar' END
WHERE dp.pedido_id = p_id$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_sync_detalle_pedido_cartera` (IN `p_pedido_id` INT)   BEGIN
    proc: BEGIN
        IF p_pedido_id IS NULL OR p_pedido_id <= 0 THEN
            LEAVE proc;
        END IF;

        UPDATE detalle_pedido dp
        INNER JOIN pedidos p ON p.id = dp.pedido_id
        LEFT JOIN (
            SELECT id_pg_pedido, SUM(monto) AS total_pagado
            FROM pagos
            GROUP BY id_pg_pedido
        ) pg ON pg.id_pg_pedido = dp.pedido_id
        SET
            dp.total_pedido = COALESCE(
                (SELECT SUM(dp2.cantidad * dp2.precio_unitario)
                 FROM detalle_pedido dp2
                 WHERE dp2.pedido_id = dp.pedido_id),
                p.total_pedido,
                0
            ),
            dp.abono_pedido = COALESCE(p.abono, 0),
            dp.pagos_registrados = COALESCE(pg.total_pagado, 0),
            dp.saldo_pendiente = GREATEST(
                0,
                COALESCE(
                    (SELECT SUM(dp3.cantidad * dp3.precio_unitario)
                     FROM detalle_pedido dp3
                     WHERE dp3.pedido_id = dp.pedido_id),
                    p.total_pedido,
                    0
                ) - (COALESCE(p.abono, 0) + COALESCE(pg.total_pagado, 0))
            ),
            dp.estado_cartera = CASE
                WHEN GREATEST(
                    0,
                    COALESCE(
                        (SELECT SUM(dp4.cantidad * dp4.precio_unitario)
                         FROM detalle_pedido dp4
                         WHERE dp4.pedido_id = dp.pedido_id),
                        p.total_pedido,
                        0
                    ) - (COALESCE(p.abono, 0) + COALESCE(pg.total_pagado, 0))
                ) <= 0 THEN 'Pagado'
                ELSE 'Por Pagar'
            END
        WHERE dp.pedido_id = p_pedido_id;
    END;
END$$

DELIMITER ;

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
-- Estructura de tabla para la tabla `detalle_pedido`
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
  `comentario_vendedor` varchar(500) DEFAULT NULL,
  `total_pedido` decimal(10,2) DEFAULT 0.00 COMMENT 'Total del pedido padre',
  `abono_pedido` decimal(10,2) DEFAULT 0.00 COMMENT 'Abono registrado en pedido',
  `pagos_registrados` decimal(10,2) DEFAULT 0.00 COMMENT 'Pagos posteriores registrados',
  `saldo_pendiente` decimal(10,2) DEFAULT 0.00 COMMENT 'Saldo pendiente del pedido padre',
  `estado_cartera` varchar(20) DEFAULT 'Por Pagar' COMMENT 'Estado de cartera: Pagado o Por Pagar'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `color`, `talla`, `comentario_vendedor`, `total_pedido`, `abono_pedido`, `pagos_registrados`, `saldo_pendiente`, `estado_cartera`) VALUES
(1, 1, 1, 3, 25000.00, 'Azul', 'M', 'Despachar en empaque original', 160000.00, 0.00, 1100150.00, 0.00, 'Pagado'),
(2, 1, 2, 1, 85000.00, 'Negro', 'L', 'Cliente solicita revisar costuras antes de enviar', 160000.00, 0.00, 1100150.00, 0.00, 'Pagado'),
(3, 2, 2, 2, 85000.00, 'Blanco', 'S', NULL, 290000.00, 0.00, 4500000.00, 0.00, 'Pagado'),
(4, 2, 3, 10, 12000.00, 'Rojo', 'Única', 'Pedido al por mayor para dotación', 290000.00, 0.00, 4500000.00, 0.00, 'Pagado'),
(5, 4, 11, 15, 120000.00, 'roja', 'm', 'prueba', 1800000.00, 900000.00, 0.00, 900000.00, 'Por Pagar'),
(6, 6, 19, 25, 45000.00, 'Azul', 'M', '', 1125000.00, 500000.02, 1012500.00, 0.00, 'Pagado'),
(7, 7, 24, 50, 95000.00, 'Verde Pasto', 'M', '', 4750000.00, 1895000.00, 1895000.00, 960000.00, 'Por Pagar'),
(8, 8, 24, 50, 95000.00, 'Verde Pasto', 'M', '', 4750000.00, 1895000.00, 2395000.00, 1880000.00, 'Por Pagar'),
(9, 9, 25, 1000, 5.00, 'Azul', '90-60-90', '', 5000.00, 1.00, 1.00, 4998.00, 'Por Pagar');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
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
-- Volcado de datos para la tabla `detalle_venta`
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
(22, 44, 15, 1, 85000.00, 85000.00, NULL, NULL, NULL),
(23, 45, 1, 2, 25000.00, 0.00, NULL, NULL, NULL),
(25, 47, 16, 5, 58900.00, 294500.00, 'Azul', 'M', 'Cuello mas pequeño v'),
(26, 48, 17, 1, 55000.00, 55000.00, 'Azul', 'XL', NULL),
(27, 48, 18, 1, 55000.00, 55000.00, 'Azul', 'M', NULL),
(28, 0, 11, 45, 120000.00, 5400000.00, NULL, 'L', 'Tela poliester'),
(29, 49, 11, 45, 120000.00, 5400000.00, NULL, 'L', 'Tela Poliester'),
(30, 50, 3, 6, 249900.00, 1499400.00, NULL, 'M', NULL);

--
-- Disparadores `detalle_venta`
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
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_pg_pedido` int(11) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id_pago`, `id_pg_pedido`, `monto`, `fecha`) VALUES
(1, 1, 500000.00, '2026-05-25 12:43:41'),
(2, 2, 3000000.00, '2026-05-25 12:43:41'),
(3, 3, 360000.00, '2026-05-25 12:43:41'),
(4, 3, 360000.00, '2026-05-26 15:44:45'),
(5, 2, 1500000.00, '2026-05-26 16:13:56'),
(6, 1, 600000.00, '2026-06-08 10:20:17'),
(8, 1, 150.00, '2026-06-10 14:21:26'),
(9, 5, 500000.00, '2026-06-13 11:08:44'),
(10, 5, 625000.00, '2026-06-13 11:22:34'),
(11, 6, 500000.02, '2026-06-23 23:12:56'),
(12, 7, 1895000.00, '2026-06-25 19:31:38'),
(13, 8, 1895000.00, '2026-06-25 19:48:10'),
(14, 6, 512499.98, '2026-06-26 14:34:15'),
(15, 8, 500000.00, '2026-06-26 14:40:41'),
(16, 9, 1.00, '2026-06-27 16:36:51');

--
-- Disparadores `pagos`
--
DELIMITER $$
CREATE TRIGGER `trg_pagos_ad_sync_cartera` AFTER DELETE ON `pagos` FOR EACH ROW BEGIN
             CALL sp_sync_detalle_pedido_cartera(OLD.id_pg_pedido);
         END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pagos_ai_sync_cartera` AFTER INSERT ON `pagos` FOR EACH ROW CALL sp_sync_cartera(NEW.id_pg_pedido)
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pagos_au_sync_cartera` AFTER UPDATE ON `pagos` FOR EACH ROW BEGIN
             CALL sp_sync_detalle_pedido_cartera(NEW.id_pg_pedido);
             IF OLD.id_pg_pedido <> NEW.id_pg_pedido THEN
                 CALL sp_sync_detalle_pedido_cartera(OLD.id_pg_pedido);
             END IF;
         END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_venta`
--

CREATE TABLE `pagos_venta` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('Efectivo','Tarjeta','Transferencia') DEFAULT 'Efectivo',
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos_venta`
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
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `cliente_id`, `detalle`, `descripcion`, `cantidad`, `total_pedido`, `estado`, `tipo_entrega`, `direccion_entrega`, `barrio_entrega`, `ciudad_entrega`, `observaciones_entrega`, `fecha_entrega`, `created_at`, `vendedor_id`, `abono`, `saldo_pendiente`) VALUES
(1, 2, '22 Uniformes de Fútbol - Inter de Sogamoso', 'Camiseta dry-fit con escudo bordado, pantaloneta y medias. Tallas: 10 M, 12 L.', 22, 1100000.00, 'Entregado', 'Tienda', NULL, NULL, NULL, NULL, '2026-06-15', '2026-05-25 17:43:41', NULL, NULL, NULL),
(2, 6, '50 Chaquetas Universitarias - Prom Lorena', 'Chaqueta impermeable con forro térmico y logo personalizado en la espalda.', 50, 4500000.00, 'Entregado', 'Tienda', NULL, NULL, NULL, NULL, '2026-06-10', '2026-05-25 17:43:41', NULL, NULL, NULL),
(3, 3, '12 Conjuntos de Baloncesto sobre medida', 'Camisilla y pantaloneta holgada con números estampados.', 12, 720000.00, 'Entregado', 'Tienda', NULL, NULL, NULL, NULL, '2026-06-28', '2026-05-25 17:43:41', NULL, NULL, NULL),
(4, 5, 'Pedido de confección mayorista', NULL, 1, 0.00, '', 'Domicilio', 'Calle 3 este 9 20', 'rosario', 'Sogamoso', 'casa', '2026-06-25', '2026-06-10 20:08:36', NULL, 900000.00, 0.00),
(5, 12, '25 uniformes tipo Inter niño', 'Tela algodon 100', 25, 1125000.00, 'Entregado', 'Tienda', NULL, NULL, NULL, NULL, '2026-06-25', '2026-06-13 16:08:44', NULL, NULL, NULL),
(6, 5, 'Uniformes tipo inter', 'Uniformes tipo inter x25 [Azul / M] | Obs: Tela 100% algodon', 25, 1012500.00, 'Terminado', 'Tienda', NULL, NULL, 'Sogamoso', NULL, '2026-07-07', '2026-06-24 04:12:56', 1, 500000.02, 0.00),
(7, 5, 'Sudadera colegio Tapitas', 'Sudadera colegio Tapitas x50 [Verde Pasto / M]', 50, 4275000.00, 'En Corte', 'Tienda', NULL, NULL, 'Sogamoso', NULL, '2026-07-11', '2026-06-26 00:31:38', 1, 1895000.00, 2380000.00),
(8, 5, 'Sudadera colegio Tapitas', 'Sudadera colegio Tapitas x50 [Verde Pasto / M]', 50, 4275000.00, 'En Corte', 'Tienda', NULL, NULL, 'Sogamoso', NULL, '2026-07-11', '2026-06-26 00:48:10', 1, 1895000.00, 1880000.00),
(9, 1, 'Traje de paño', 'Traje de paño x1000 [Azul / 90-60-90] | Obs: Pruebas', 1000, 4500.00, 'En Corte', 'Tienda', NULL, NULL, 'Sogamoso', NULL, '2026-07-12', '2026-06-27 21:36:51', 1, 1.00, 4499.00);

--
-- Disparadores `pedidos`
--
DELIMITER $$
CREATE TRIGGER `trg_pedidos_ai_sync_cartera` AFTER INSERT ON `pedidos` FOR EACH ROW BEGIN
             CALL sp_sync_detalle_pedido_cartera(NEW.id);
         END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pedidos_au_sync_cartera` AFTER UPDATE ON `pedidos` FOR EACH ROW BEGIN
             CALL sp_sync_detalle_pedido_cartera(NEW.id);
         END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `precios_base_confeccion`
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
-- Volcado de datos para la tabla `precios_base_confeccion`
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
-- Estructura de tabla para la tabla `productos`
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
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo_descriptivo`, `nombre`, `referencia`, `categoria`, `color`, `material`, `genero`, `estado`, `descripcion`, `talla`, `stock`, `unidad`, `precio`, `created_at`) VALUES
(1, 'PROD-0001', 'Camiseta Polo Azul', 'REF-001', 'Camisetas', NULL, NULL, NULL, 'activo', NULL, 'S', 8, 'Unidad', 20000.00, '2026-05-12 00:39:13'),
(2, 'PROD-0002', 'Pantaloneta Roja', 'REF-002', 'Pantalonetas', NULL, NULL, NULL, 'activo', NULL, 'S', 6, 'Unidad', 0.00, '2026-05-12 00:39:13'),
(3, 'PROD-0003', 'Camiseta Selección Colombia 2024', 'COL-HOME-01', 'Selección', NULL, NULL, NULL, 'activo', NULL, 'M', 0, 'Unidad', 249900.00, '2026-05-12 00:39:13'),
(4, 'PROD-0004', 'Camiseta Selección Colombia Visitante', 'COL-AWAY-02', 'Selección', NULL, NULL, NULL, 'activo', NULL, 'L', 10, 'Unidad', 249900.00, '2026-05-12 00:39:13'),
(5, 'PROD-0005', 'Balón Adidas Al Rihla Pro', 'BALL-QA22', 'Balones', NULL, NULL, NULL, 'activo', NULL, '5', 17, 'Unidad', 185000.00, '2026-05-12 00:39:13'),
(6, 'PROD-0006', 'Tenis Running UltraBoost', 'RUN-UB-22', 'Calzado', NULL, NULL, NULL, 'activo', NULL, '40', 20, 'Unidad', 650000.00, '2026-05-12 00:39:13'),
(7, 'PROD-0007', 'Sudadera Entrenamiento Negra', 'SUD-TR-05', 'Sudaderas', NULL, NULL, NULL, 'activo', NULL, 'S', 17, 'Unidad', 145000.00, '2026-05-12 00:39:13'),
(8, 'PROD-0008', 'Gorra Unideportes Classic', 'ACC-CAP-01', 'Accesorios', NULL, NULL, NULL, 'activo', NULL, 'Única', 30, 'Unidad', 45000.00, '2026-05-12 00:39:13'),
(9, 'PROD-0009', 'Guayos Predator Edge', 'GYO-AD-P', 'Calzado', NULL, NULL, NULL, 'activo', NULL, '41', 19, 'Unidad', 480000.00, '2026-05-12 00:39:13'),
(10, 'PROD-0010', 'Canilleras de Protección', 'PRO-CAN-02', 'Accesorios', NULL, NULL, NULL, 'activo', NULL, 'M', 29, 'Unidad', 35000.00, '2026-05-12 00:39:13'),
(11, NULL, 'Chaqueta Rompevientos Unideportes', 'CHA-ROM-01', 'Chaqueta', '', 'Poliester', 'Unisex', 'activo', '', 'L', 5, 'Unidad', 120000.00, '2026-05-26 01:16:38'),
(12, NULL, 'Medias Ciclismo Negras', 'MED-CIC-02', NULL, NULL, NULL, NULL, 'activo', NULL, 'Única', 40, 'Unidad', 15000.00, '2026-05-26 01:16:38'),
(13, NULL, 'Maletín Deportivo Gym', 'MAL-GYM-05', NULL, NULL, NULL, NULL, 'activo', NULL, 'Única', 8, 'Unidad', 85000.00, '2026-05-26 01:16:38'),
(14, NULL, 'Tula Deportiva Impermeable', 'TUL-IMP-09', NULL, NULL, NULL, NULL, 'activo', NULL, 'Única', 25, 'Unidad', 25000.00, '2026-05-26 01:16:38'),
(15, NULL, 'Camiseta Cuello V', 'CAMCUE-M-547', 'Camisetas', NULL, NULL, NULL, 'activo', NULL, 'M', 48, 'Unidad', 85000.00, '2026-05-26 03:37:04'),
(16, NULL, 'Camiseta Polo', 'CAMPOL-M-431', 'Camisetas', 'Azul', 'Poliester', 'Hombre', 'activo', 'Camiseta polo sport hombre', 'M', 5, 'Unidad', 58900.00, '2026-06-08 17:06:20'),
(17, NULL, 'Buso', 'BUSOXX-XL-374', 'Sudaderas', 'Azul', 'Nylon', 'Unisex', 'activo', 'Buso marca Unideportes diseño sport', 'XL', 17, 'Unidad', 55000.00, '2026-06-13 16:35:20'),
(18, NULL, 'Buso', 'BUSOXX-M-792', 'Sudaderas', 'Azul', 'Nylon', 'Unisex', 'activo', 'Buso Unideportes Sport', 'M', 17, 'Unidad', 55000.00, '2026-06-13 16:39:14'),
(19, NULL, 'Uniformes tipo inter', 'MAY-20260624061256-8411', 'Confeccion', 'Azul', 'No definido', 'Unisex', 'inactivo', 'Producto generado automaticamente desde pedido mayorista', 'M', 0, 'Unidad', 45000.00, '2026-06-24 04:12:56'),
(20, NULL, 'Camisa Licrada', 'CAMLIC-M-803', 'Camisetas', 'Negro', 'Poliester', 'Unisex', 'activo', 'Bordes mas detallados y sellos', 'M', 20, 'Unidad', 54000.00, '2026-06-25 00:57:44'),
(21, NULL, 'Camisa Licrada', 'CAMLIC-M-119', 'Camisetas', 'Negro', 'Poliester', 'Unisex', 'activo', 'Bordes mas detallados y sellos', 'M', 20, 'Unidad', 54000.00, '2026-06-25 01:13:09'),
(23, NULL, 'Short Deportivo', 'SHODEP-M-771', 'Pantalonetas', 'Verde', 'Poliester', 'Mujer', 'activo', 'Tener en cuenta las costuras', 'M', 20, 'Unidad', 25000.00, '2026-06-25 01:44:21'),
(24, NULL, 'Sudadera colegio Tapitas', 'MAY-20260626023138-4143', 'Confeccion', 'Verde Pasto', 'No definido', 'Unisex', 'inactivo', 'Producto generado automaticamente desde pedido mayorista', 'M', 0, 'Unidad', 95000.00, '2026-06-26 00:31:38'),
(25, NULL, 'Traje de paño', 'MAY-20260627233651-2310', 'Confeccion', 'Azul', 'No definido', 'Unisex', 'inactivo', 'Producto generado automaticamente desde pedido mayorista', '90-60-90', 0, 'Unidad', 5.00, '2026-06-27 21:36:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `soporte_comentarios`
--

CREATE TABLE `soporte_comentarios` (
  `id_comentario` int(10) UNSIGNED NOT NULL,
  `id_ticket` int(10) UNSIGNED NOT NULL,
  `autor` varchar(120) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `soporte_tickets`
--

CREATE TABLE `soporte_tickets` (
  `id_ticket` int(10) UNSIGNED NOT NULL,
  `asunto` varchar(180) NOT NULL,
  `prioridad` enum('Crítica','Alta','Media','Baja') NOT NULL DEFAULT 'Media',
  `estado` enum('Abierto','En Proceso','Resuelto','Cerrado') NOT NULL DEFAULT 'Abierto',
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `vendedor` varchar(120) NOT NULL,
  `comentario_solucion` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `soporte_tickets`
--

INSERT INTO `soporte_tickets` (`id_ticket`, `asunto`, `prioridad`, `estado`, `fecha`, `vendedor`, `comentario_solucion`, `updated_at`) VALUES
(1, 'Error en panel pedidos', 'Crítica', 'Abierto', '2026-06-26 11:16:38', 'admin', 'Corregir espacios de llenado', '2026-06-26 11:18:35'),
(2, 'No puedo imprimir facturas', 'Media', 'En Proceso', '2026-06-26 11:26:54', 'Pablo', 'Corregir impresión de facturas', '2026-06-26 11:27:27'),
(3, 'Error al calcular precios', 'Alta', 'Resuelto', '2026-06-26 16:11:01', 'admin', 'Solucionar calculo de precios en realizar venta', '2026-06-26 16:12:20');

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

INSERT INTO `usuarios` (`id`, `name`, `lastname`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'Admin', 'Principal', 'admin', '$2y$10$HE/tgmC2aCXngKpB.cgW/.jIpBz7J2/Xg3AFH9qQmZUMIvSHa2a3u', 'admin@unideportes.com', 'admin', '2026-05-12 00:39:11'),
(2, 'Joel', 'Castro', 'joel_dev', '$2y$10$COcs9nq1Z1PkNbUYgFQrl.EIYQ3dHUfUTP8NN4DARbAgnJmN0.Kry', 'joel@unideportes.com', 'admin', '2026-05-12 00:39:11'),
(3, 'Pablo', 'Rios', 'Pablo', '$2y$10$veezt7buzSJZHg8favNTcOUPTx44nB8/eEZfp5NI.Y6abZwDLIIAi', 'pablourbe@unideportes.com', 'colaborador', '2026-05-12 00:39:11'),
(5, 'Jonathan', 'Suarez', 'JonathanS', '$2y$10$COcs9nq1Z1PkNbUYgFQrl.EIYQ3dHUfUTP8NN4DARbAgnJmN0.Kry', 'jaysuarezap@gmail.com', 'vendedor', '2026-05-12 00:39:11'),
(8, 'Administrador Dos', 'Central', 'admin_sena', '$2y$10$M9rWvXexamplehashforadminpassworddontchange', 'admin2@unideportes.com', 'admin', '2026-05-26 18:50:40'),
(9, 'Vendedor Nuevo', 'Caja', 'vendedor02', '$2y$10$M9rWvXexamplehashforvendedorpassworddontchange', 'ventas2@unideportes.com', 'vendedor', '2026-05-26 18:50:40'),
(10, 'Pablo Andrés', 'Rodríguez', 'pablo.vendedor', '$2y$10$AEcONR3ri2oTNrUGctSTN.R7sjukFK.lvzFGI09bggjAn0cYlWGye', 'pablo@unideportes.com', 'vendedor', '2026-06-26 19:04:09'),
(11, 'Felipe', 'Rodríguez', 'feliperodriguez', '$2y$10$cDphsadrdveSTEMVuOL9z.a0wgDHOf.iaGNJ9I4nxa.S6lfao9tXO', 'felipe@gmail.com', 'vendedor', '2026-06-26 21:21:26');

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

INSERT INTO `ventas` (`id`, `codigo_descriptivo`, `ticket_numero`, `cliente_id`, `vendedor_id`, `total_venta`, `metodo_pago`, `tipo_entrega`, `costo_envio`, `direccion_entrega`, `barrio_entrega`, `ciudad_entrega`, `observaciones_entrega`, `cambio`, `tipo_transferencia`, `fecha_venta`) VALUES
(1, NULL, 'T-20260626223330-561', 5, 1, 5400000.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-06-26 15:33:30'),
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
(44, NULL, 'T-20260526235209-569', 12, 3, 85000.00, 'Transferencia', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, 'Nequi', '2026-05-26 16:52:09'),
(45, NULL, 'FACT-001', 1, 1, 50000.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-06-08 15:13:36'),
(47, NULL, 'T-20260613181804-470', 3, 3, 294500.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 5500.00, NULL, '2026-06-13 11:18:04'),
(48, NULL, 'T-20260613183959-669', 5, 3, 110000.00, 'Tarjeta', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-06-13 11:39:59'),
(49, NULL, 'T-20260626225841-463', 5, 1, 5400000.00, 'Efectivo', 'Tienda', 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, '2026-06-26 15:58:41'),
(50, NULL, 'T-20260627233427-119', 5, 1, 1504400.00, 'Tarjeta', 'Domicilio', 5000.00, 'Cl. 3 Este #9-1', 'El Rosario', 'Sogamoso', 'Pruebas', 0.00, NULL, '2026-06-27 16:34:27');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_saldos_pedidos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_saldos_pedidos` (
`id` int(11)
,`total_pedido` decimal(10,2)
,`saldo_pendiente` decimal(33,2)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_saldos_pedidos`
--
DROP TABLE IF EXISTS `vista_saldos_pedidos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_saldos_pedidos`  AS SELECT `p`.`id` AS `id`, `p`.`total_pedido` AS `total_pedido`, `p`.`total_pedido`- ifnull(sum(`pg`.`monto`),0) AS `saldo_pendiente` FROM (`pedidos` `p` left join `pagos` `pg` on(`p`.`id` = `pg`.`id_pg_pedido`)) GROUP BY `p`.`id` ;

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
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detalle_pedido_pedidos` (`pedido_id`),
  ADD KEY `fk_detalle_pedido_productos` (`producto_id`);

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
-- Indices de la tabla `pagos_venta`
--
ALTER TABLE `pagos_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pedidos_cliente` (`cliente_id`),
  ADD KEY `idx_pedidos_estado` (`estado`),
  ADD KEY `idx_vendedor_id` (`vendedor_id`);

--
-- Indices de la tabla `precios_base_confeccion`
--
ALTER TABLE `precios_base_confeccion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipo_prenda` (`tipo_prenda`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `referencia` (`referencia`);

--
-- Indices de la tabla `soporte_comentarios`
--
ALTER TABLE `soporte_comentarios`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `idx_ticket` (`id_ticket`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indices de la tabla `soporte_tickets`
--
ALTER TABLE `soporte_tickets`
  ADD PRIMARY KEY (`id_ticket`),
  ADD KEY `idx_prioridad` (`prioridad`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha` (`fecha`);

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
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `soporte_comentarios`
--
ALTER TABLE `soporte_comentarios`
  MODIFY `id_comentario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `soporte_tickets`
--
ALTER TABLE `soporte_tickets`
  MODIFY `id_ticket` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `soporte_comentarios`
--
ALTER TABLE `soporte_comentarios`
  ADD CONSTRAINT `soporte_comentarios_ibfk_1` FOREIGN KEY (`id_ticket`) REFERENCES `soporte_tickets` (`id_ticket`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
