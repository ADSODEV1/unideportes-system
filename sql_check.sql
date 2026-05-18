-- Check if column exists and add it if it doesn't
SELECT IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME='clientes' AND COLUMN_NAME='estado' AND TABLE_SCHEMA='unideportes'
    ),
    'COLUMN EXISTS',
    'COLUMN NOT FOUND'
) as status;

-- Add column if it doesn't exist
ALTER TABLE clientes ADD COLUMN estado ENUM('activo', 'inactivo') DEFAULT 'activo';

-- Verify the column exists
SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME='clientes' AND COLUMN_NAME='estado' AND TABLE_SCHEMA='unideportes';
