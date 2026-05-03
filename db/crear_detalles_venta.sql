-- CREAR TABLA DE DETALLES DE VENTA
-- Esta tabla guarda qué productos se vendieron en cada venta

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
  CONSTRAINT `fk_detalles_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_detalles_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
