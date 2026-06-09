# 📋 Guía de Implementación: Venta Mayorista con Abono

## ✅ Cambios Realizados

### 1. **Base de Datos** (`db/alter_tables_abono.sql`)
Se agregaron los siguientes campos:

**Tabla `pedidos`:**
- `vendedor_id` (INT) - ID del vendedor que hace la venta
- `abono` (DECIMAL) - Monto inicial pagado
- `saldo_pendiente` (DECIMAL) - Dinero restante para la entrega

**Tabla `detalle_pedido`:**
- `color` (VARCHAR) - Color del producto (ej: Azul, Rojo)
- `talla` (VARCHAR) - Talla del producto (ej: S, M, L)

### 2. **Vistas** (`views/linea_confeccion.php`)
- Formulario de compra mayorista mejorado
- Campos de color y talla en la tabla
- Sección de abono con cálculo automático de saldo pendiente
- Muestra el vendedor logueado

### 3. **JavaScript** (`public/js/linea_confeccion.js`)
- Captura color y talla de cada producto
- Descuento automático por volumen (10+= 5%, 20+=10%)
- Cálculo de saldo pendiente = Total - Abono
- Envía datos completos (incluyendo color y talla)

### 4. **Controlador** (`controllers/procesar_venta.php`)
- Detecta si es venta mayorista con abono
- Calcula fecha de entrega (15 días después)
- Crea automáticamente un registro en `pedidos` con:
  - `abono` pagado
  - `saldo_pendiente` para la entrega
  - `fecha_entrega` (15 días después)
  - `vendedor_id` del que realizó la venta
- Guarda `color` y `talla` en `detalle_pedido`

## 🗄️ PASO 1: Actualizar la Base de Datos

1. Abre **phpMyAdmin** (http://localhost/phpmyadmin)
2. Selecciona la BD `unideportes`
3. Ve a la pestaña **SQL**
4. Copia y pega el contenido de `db/alter_tables_abono.sql`
5. Haz clic en **Ejecutar**

### Script SQL a ejecutar:
```sql
ALTER TABLE `pedidos` ADD COLUMN `vendedor_id` INT(11) DEFAULT 1 AFTER `cliente_id`;
ALTER TABLE `pedidos` ADD COLUMN `abono` DECIMAL(10,2) DEFAULT 0.00 AFTER `total_pedido`;
ALTER TABLE `pedidos` ADD COLUMN `saldo_pendiente` DECIMAL(10,2) DEFAULT 0.00 AFTER `abono`;

ALTER TABLE `detalle_pedido` ADD COLUMN `color` VARCHAR(50) DEFAULT NULL AFTER `cantidad`;
ALTER TABLE `detalle_pedido` ADD COLUMN `talla` VARCHAR(10) DEFAULT NULL AFTER `color`;

ALTER TABLE `pedidos` ADD INDEX `idx_vendedor_id` (`vendedor_id`);

UPDATE `pedidos` SET `saldo_pendiente` = `total_pedido` WHERE `saldo_pendiente` = 0.00 AND `abono` = 0.00;
```

## 🎯 PASO 2: Usar la Pantalla Mayorista

1. **Login** como vendedor o admin
2. En el **Panel del Vendedor**, haz clic en **"Venta Mayorista"**
3. Selecciona o crea cliente
4. Busca productos y añade:
   - Cantidad
   - **Color** (ej: Azul, Rojo)
   - **Talla** (ej: S, M, L, XL)
5. El sistema calcula automáticamente:
   - Descuento por volumen (10+ = 5%, 20+ = 10%)
   - Saldo pendiente = Total - Abono
6. Ingresa **Abono** (pago inicial)
7. El **Saldo Pendiente** se calcula automáticamente
8. Procesa la venta

## 📊 Resultado

- **Venta registrada** en tabla `ventas` (para punto de venta inmediato)
- **Pedido creado** en tabla `pedidos` (para producción) con:
  - Estado inicial: "En Corte"
  - Fecha de entrega: 15 días después
  - Abono pagado y saldo pendiente registrado
  - Color y talla en detalle_pedido
  - Vendedor que hizo la venta

## 🔍 Verificación en Producción

1. En el **Panel Admin**, ve a **"Línea de Confección"**
2. Verás el pedido con estado "En Corte"
3. Puedes cambiar el estado a:
   - En Costura
   - Terminado
   - Entregado (aquí se cobra el saldo pendiente)

## 📝 Notas Importantes

- **Abono = 0**: Si no ingresa abono, se considera venta al contado (sin pedido)
- **Abono > 0**: Se crea automáticamente un pedido con los datos de color y talla
- **Fecha entrega**: Se calcula automáticamente 15 días después
- **Vendedor**: Se registra quién hace la venta para auditoría
- **Color y Talla**: Se guardan en producción para las instrucciones de confección
