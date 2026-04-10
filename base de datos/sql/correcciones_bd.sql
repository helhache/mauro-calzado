-- ============================================================================
-- SCRIPT DE CORRECCIONES DE BASE DE DATOS
-- Proyecto: Mauro Calzado
-- Fecha: 2026-02-02
-- ============================================================================
-- INSTRUCCIONES:
-- 1. Hacer backup de la base de datos ANTES de ejecutar
-- 2. Ejecutar este script en phpMyAdmin o desde línea de comandos
-- 3. Verificar que no haya errores
-- ============================================================================

USE mauro_calzado;

-- ============================================================================
-- 1. CORREGIR COLLATION INCONSISTENTE
-- Unificar todo a utf8mb4_spanish_ci (mejor para ordenamiento en español)
-- ============================================================================

ALTER TABLE carrito CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
ALTER TABLE categorias CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
ALTER TABLE contacto CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
ALTER TABLE detalle_pedidos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
ALTER TABLE favoritos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
ALTER TABLE imagenes_productos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
ALTER TABLE mensajes_internos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
ALTER TABLE mensajes_gerente_admin CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
ALTER TABLE newsletter CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
ALTER TABLE pedidos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
ALTER TABLE productos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
ALTER TABLE sucursales CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;

-- ============================================================================
-- 2. CORREGIR ENUM DE NOTIFICACIONES
-- El campo 'tipo' tiene valores vacíos que violan el ENUM
-- ============================================================================

-- Primero, modificar el ENUM para incluir un valor genérico
ALTER TABLE notificaciones
MODIFY COLUMN tipo ENUM(
    'pedido',
    'stock_bajo',
    'nuevo_usuario',
    'review',
    'mensaje_cliente',
    'mensaje_gerente',
    'respuesta_admin',
    'cambio_estado',
    'sistema'
) NOT NULL DEFAULT 'sistema';

-- Actualizar los registros con tipo vacío
UPDATE notificaciones
SET tipo = 'cambio_estado'
WHERE tipo = '' OR tipo IS NULL;

-- ============================================================================
-- 3. CORREGIR TRIGGER DE MENSAJES_INTERNOS
-- El visible_para estaba mal (decía 'admin' cuando debería ser 'cliente')
-- ============================================================================

DROP TRIGGER IF EXISTS after_mensaje_cliente_update;

DELIMITER $$
CREATE TRIGGER `after_mensaje_cliente_update` AFTER UPDATE ON `mensajes_internos` FOR EACH ROW
BEGIN
    -- Si se agregó una respuesta que antes no existía
    IF NEW.respuesta IS NOT NULL AND OLD.respuesta IS NULL THEN
        -- Crear notificación para el CLIENTE (corregido)
        INSERT INTO notificaciones (
            tipo,
            titulo,
            mensaje,
            usuario_id,
            url,
            leida,
            visible_para,
            fecha_creacion
        ) VALUES (
            'respuesta_admin',
            'Respuesta a tu mensaje',
            CONCAT('Re: ', NEW.asunto),
            NEW.usuario_id,
            'mensajes-internos.php',
            0,
            'cliente',  -- CORREGIDO: era 'admin', ahora es 'cliente'
            NOW()
        );
    END IF;
END$$
DELIMITER ;

-- ============================================================================
-- 4. AGREGAR visible_para = 'cliente' AL ENUM DE NOTIFICACIONES
-- ============================================================================

ALTER TABLE notificaciones
MODIFY COLUMN visible_para ENUM('admin', 'gerente', 'cliente', 'ambos') DEFAULT 'admin';

-- ============================================================================
-- 5. AGREGAR FOREIGN KEY FALTANTE EN PEDIDOS.SUCURSAL_ID
-- ============================================================================

-- Primero verificar que no haya valores huérfanos
-- Si hay pedidos con sucursal_id que no existe, esto fallará
-- En ese caso, primero corregir los datos

ALTER TABLE pedidos
ADD CONSTRAINT fk_pedido_sucursal
FOREIGN KEY (sucursal_id) REFERENCES sucursales(id)
ON DELETE SET NULL ON UPDATE CASCADE;

-- ============================================================================
-- 6. AGREGAR ÍNDICES FALTANTES PARA MEJORAR RENDIMIENTO
-- ============================================================================

-- Índice en pedidos.sucursal_id (ahora que tiene FK)
CREATE INDEX idx_pedido_sucursal ON pedidos(sucursal_id);

-- Índice en notificaciones.usuario_id
CREATE INDEX idx_notif_usuario ON notificaciones(usuario_id);

-- ============================================================================
-- 7. CORREGIR NOTIFICACIONES CON visible_para INCORRECTO
-- ============================================================================

-- Las notificaciones de respuesta al cliente deben ser visibles para el cliente
UPDATE notificaciones
SET visible_para = 'cliente'
WHERE tipo = 'respuesta_admin'
AND url LIKE '%mensajes-internos.php%';

-- Las notificaciones de respuesta al gerente deben ser visibles para el gerente
UPDATE notificaciones
SET visible_para = 'gerente'
WHERE tipo = 'respuesta_admin'
AND url LIKE '%gerente/mensajes.php%';

-- ============================================================================
-- 8. LIMPIAR DATOS DE PRUEBA (OPCIONAL - DESCOMENTAR SI ES NECESARIO)
-- ============================================================================

-- ADVERTENCIA: Solo ejecutar si querés limpiar datos de prueba
-- Esto eliminará carritos abandonados de más de 30 días

-- DELETE FROM carrito WHERE fecha_agregado < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- ============================================================================
-- VERIFICACIONES POST-SCRIPT
-- ============================================================================

-- Verificar que no hay tipos vacíos en notificaciones
SELECT COUNT(*) as notif_sin_tipo FROM notificaciones WHERE tipo = '' OR tipo IS NULL;

-- Verificar collation unificado
SELECT TABLE_NAME, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'mauro_calzado';

-- Verificar foreign keys
SELECT
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'mauro_calzado'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- ============================================================================
-- FIN DEL SCRIPT
-- ============================================================================
