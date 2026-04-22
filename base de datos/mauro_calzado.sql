-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-04-2026 a las 05:43:41
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
-- Base de datos: `mauro_calzado`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizar_stock` (IN `p_producto_id` INT, IN `p_cantidad` INT, IN `p_operacion` VARCHAR(10))   BEGIN
    -- Declarar variable para stock actual
    DECLARE stock_actual INT;
    
    -- Obtener stock actual
    SELECT stock INTO stock_actual 
    FROM productos 
    WHERE id = p_producto_id;
    
    -- Validar operación
    IF p_operacion = 'restar' THEN
        -- Verificar que hay stock suficiente
        IF stock_actual >= p_cantidad THEN
            UPDATE productos 
            SET stock = stock - p_cantidad,
                ventas = ventas + p_cantidad
            WHERE id = p_producto_id;
        ELSE
            -- Error: stock insuficiente
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stock insuficiente';
        END IF;
        
    ELSEIF p_operacion = 'sumar' THEN
        -- Devolver stock (por cancelación, devolución, etc.)
        UPDATE productos 
        SET stock = stock + p_cantidad
        WHERE id = p_producto_id;
        
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `crear_pedido_desde_carrito` (IN `p_usuario_id` INT, IN `p_sucursal_id` INT, IN `p_subtotal` DECIMAL(10,2), IN `p_costo_envio` DECIMAL(10,2), IN `p_total` DECIMAL(10,2), IN `p_metodo_pago` VARCHAR(50), IN `p_tipo_entrega` VARCHAR(20), IN `p_direccion` TEXT, IN `p_ciudad` VARCHAR(100), IN `p_provincia` VARCHAR(100), IN `p_codigo_postal` VARCHAR(10), IN `p_telefono` VARCHAR(20), IN `p_notas` TEXT, OUT `p_pedido_id` INT, OUT `p_numero_pedido` VARCHAR(20))   BEGIN
    DECLARE v_anio INT;
    DECLARE v_mes INT;
    DECLARE v_contador INT;
    
    -- Generar número de pedido único
    SET v_anio = YEAR(NOW());
    SET v_mes = MONTH(NOW());
    
    -- Obtener contador de pedidos del mes
    SELECT COUNT(*) + 1 INTO v_contador
    FROM pedidos
    WHERE YEAR(fecha_pedido) = v_anio 
    AND MONTH(fecha_pedido) = v_mes;
    
    -- Formato: MC-AAAAMM-XXXX (ej: MC-202511-0001)
    SET p_numero_pedido = CONCAT('MC-', v_anio, LPAD(v_mes, 2, '0'), '-', LPAD(v_contador, 4, '0'));
    
    -- Insertar pedido
    INSERT INTO pedidos (
        usuario_id, 
        sucursal_id,
        numero_pedido, 
        subtotal,
        costo_envio,
        total, 
        metodo_pago,
        tipo_entrega,
        direccion_envio,
        ciudad_envio,
        provincia_envio,
        codigo_postal_envio,
        telefono_contacto,
        notas_cliente,
        estado,
        estado_pago,
        fecha_pedido
    ) VALUES (
        p_usuario_id,
        p_sucursal_id,
        p_numero_pedido,
        p_subtotal,
        p_costo_envio,
        p_total,
        p_metodo_pago,
        p_tipo_entrega,
        p_direccion,
        p_ciudad,
        p_provincia,
        p_codigo_postal,
        p_telefono,
        p_notas,
        'pendiente',
        'pendiente',
        NOW()
    );
    
    SET p_pedido_id = LAST_INSERT_ID();
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `marcar_mensaje_leido` (IN `p_mensaje_id` INT, IN `p_tipo_tabla` VARCHAR(50))   BEGIN
    IF p_tipo_tabla = 'gerente' THEN
        UPDATE mensajes_gerente_admin 
        SET leido_gerente = 1 
        WHERE id = p_mensaje_id;
    ELSEIF p_tipo_tabla = 'cliente' THEN
        UPDATE mensajes_internos 
        SET leido_cliente = 1 
        WHERE id = p_mensaje_id;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bajas_productos`
--

CREATE TABLE `bajas_productos` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL COMMENT 'Cantidad dada de baja',
  `motivo` enum('mal_estado','vencido','dañado','robo','extravío','otro') NOT NULL DEFAULT 'mal_estado',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción detallada del motivo',
  `usuario_id` int(11) NOT NULL COMMENT 'Gerente que dio de baja',
  `fecha_baja` timestamp NOT NULL DEFAULT current_timestamp(),
  `notificado_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `banner_slides`
--

CREATE TABLE `banner_slides` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `subtitulo` varchar(300) DEFAULT NULL,
  `texto_boton` varchar(100) DEFAULT NULL,
  `url_boton` varchar(500) DEFAULT NULL,
  `imagen` varchar(255) NOT NULL,
  `orden` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito`
--

CREATE TABLE `carrito` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT 1,
  `talle` varchar(10) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `fecha_agregado` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `carrito`
--

INSERT INTO `carrito` (`id`, `usuario_id`, `session_id`, `producto_id`, `cantidad`, `talle`, `color`, `fecha_agregado`) VALUES
(5, 19, NULL, 14, 1, NULL, NULL, '2025-11-12 17:47:18'),
(6, 19, NULL, 16, 2, NULL, NULL, '2025-11-12 17:48:01'),
(7, 19, NULL, 22, 1, NULL, NULL, '2025-11-12 17:48:03'),
(10, 4, NULL, 15, 1, NULL, NULL, '2025-11-15 16:15:11'),
(11, 4, NULL, 16, 1, NULL, NULL, '2025-11-15 16:15:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `slug`, `descripcion`, `imagen`, `activo`, `orden`) VALUES
(1, 'Mujer', 'mujer', 'Calzado femenino: elegancia y comodidad', NULL, 1, 1),
(2, 'Hombre', 'hombre', 'Calzado masculino: estilo y resistencia', NULL, 1, 2),
(3, 'Infantil', 'infantil', 'Calzado para niños y niñas', NULL, 1, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cobro_cuotas_credito`
--

CREATE TABLE `cobro_cuotas_credito` (
  `id` int(11) NOT NULL,
  `turno_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `cliente_nombre` varchar(255) NOT NULL COMMENT 'Nombre del cliente',
  `cliente_dni` varchar(20) DEFAULT NULL,
  `monto_cobrado` decimal(10,2) NOT NULL,
  `numero_cuota` int(11) DEFAULT NULL COMMENT 'Número de cuota que está pagando',
  `observaciones` text DEFAULT NULL,
  `fecha_cobro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Disparadores `cobro_cuotas_credito`
--
DELIMITER $$
CREATE TRIGGER `after_cobro_insert` AFTER INSERT ON `cobro_cuotas_credito` FOR EACH ROW BEGIN
    UPDATE turnos_caja 
    SET cobro_cuotas_credito = cobro_cuotas_credito + NEW.monto_cobrado
    WHERE id = NEW.turno_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contacto`
--

CREATE TABLE `contacto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `asunto` varchar(200) NOT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `respondido` tinyint(1) DEFAULT 0,
  `fecha_envio` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `contacto`
--

INSERT INTO `contacto` (`id`, `nombre`, `email`, `telefono`, `asunto`, `mensaje`, `leido`, `respondido`, `fecha_envio`) VALUES
(1, 'frtan', 'Fran@gmail.com', 'sadasd', 'Reclamo', 'dasdafgwefec', 1, 1, '2025-11-15 01:19:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedidos`
--

CREATE TABLE `detalle_pedidos` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `nombre_producto` varchar(200) DEFAULT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `talle` varchar(10) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `detalle_pedidos`
--

INSERT INTO `detalle_pedidos` (`id`, `pedido_id`, `producto_id`, `nombre_producto`, `precio_unitario`, `cantidad`, `talle`, `color`, `subtotal`) VALUES
(1, 1, 16, 'Zapatillas One Foot Hombre', 14940.00, 4, NULL, NULL, 59760.00),
(2, 1, 15, 'Zapatillas One Foot Mujer', 14940.00, 2, NULL, NULL, 29880.00),
(3, 2, 21, 'Ojotas Infantiles', 5000.00, 7, NULL, NULL, 35000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `fecha_agregado` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `favoritos`
--

INSERT INTO `favoritos` (`id`, `usuario_id`, `producto_id`, `fecha_agregado`) VALUES
(4, 19, 14, '2025-11-12 17:47:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gastos_sucursal`
--

CREATE TABLE `gastos_sucursal` (
  `id` int(11) NOT NULL,
  `turno_id` int(11) DEFAULT NULL COMMENT 'Turno asociado (puede ser NULL si es gasto mensual)',
  `sucursal_id` int(11) NOT NULL,
  `concepto` varchar(255) NOT NULL COMMENT 'Descripción del gasto',
  `monto` decimal(10,2) NOT NULL,
  `tipo` enum('servicio','proveedor','otro') NOT NULL DEFAULT 'otro',
  `fecha_gasto` date NOT NULL,
  `descripcion` text DEFAULT NULL COMMENT 'Detalle adicional',
  `comprobante` varchar(255) DEFAULT NULL COMMENT 'Nombre del archivo del comprobante',
  `registrado_por` int(11) DEFAULT NULL COMMENT 'Usuario que registró el gasto',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `gastos_sucursal`
--

INSERT INTO `gastos_sucursal` (`id`, `turno_id`, `sucursal_id`, `concepto`, `monto`, `tipo`, `fecha_gasto`, `descripcion`, `comprobante`, `registrado_por`, `fecha_registro`) VALUES
(1, 5, 2, 'pago agosto', 100000.00, 'servicio', '2025-11-12', '', NULL, 11, '2025-11-12 20:54:25');

--
-- Disparadores `gastos_sucursal`
--
DELIMITER $$
CREATE TRIGGER `after_gasto_insert` AFTER INSERT ON `gastos_sucursal` FOR EACH ROW BEGIN
    IF NEW.turno_id IS NOT NULL THEN
        UPDATE turnos_caja 
        SET gastos_dia = gastos_dia + NEW.monto
        WHERE id = NEW.turno_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `imagenes_productos`
--

CREATE TABLE `imagenes_productos` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `imagen` varchar(255) NOT NULL,
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_gerente_admin`
--

CREATE TABLE `mensajes_gerente_admin` (
  `id` int(11) NOT NULL,
  `gerente_id` int(11) NOT NULL COMMENT 'ID del gerente que envía',
  `admin_id` int(11) DEFAULT NULL COMMENT 'ID del admin que responde',
  `sucursal_id` int(11) NOT NULL COMMENT 'Sucursal del gerente',
  `tipo_mensaje` enum('pedido_mercaderia','transferencia','consulta','reporte','otro') NOT NULL DEFAULT 'consulta',
  `asunto` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `archivo_adjunto` varchar(255) DEFAULT NULL COMMENT 'Nombre del archivo adjunto',
  `respuesta` text DEFAULT NULL,
  `archivo_respuesta` varchar(255) DEFAULT NULL COMMENT 'Archivo adjunto en la respuesta',
  `estado` enum('pendiente','respondido','cerrado') DEFAULT 'pendiente',
  `pedido_relacionado_id` int(11) DEFAULT NULL COMMENT 'Pedido relacionado (opcional)',
  `prioridad` enum('baja','media','alta') DEFAULT 'media',
  `fecha_envio` datetime DEFAULT current_timestamp(),
  `fecha_respuesta` datetime DEFAULT NULL,
  `leido_gerente` tinyint(1) DEFAULT 0 COMMENT 'Si el gerente leyó la respuesta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mensajes_gerente_admin`
--

INSERT INTO `mensajes_gerente_admin` (`id`, `gerente_id`, `admin_id`, `sucursal_id`, `tipo_mensaje`, `asunto`, `mensaje`, `archivo_adjunto`, `respuesta`, `archivo_respuesta`, `estado`, `pedido_relacionado_id`, `prioridad`, `fecha_envio`, `fecha_respuesta`, `leido_gerente`) VALUES
(1, 11, 3, 2, 'pedido_mercaderia', 'solicito campus y ojotas', '100 pares variar talles de 35 a 40', NULL, 'okey', NULL, 'cerrado', NULL, 'alta', '2025-11-15 16:21:59', '2025-11-15 16:23:05', 0);

--
-- Disparadores `mensajes_gerente_admin`
--
DELIMITER $$
CREATE TRIGGER `after_mensaje_gerente_insert` AFTER INSERT ON `mensajes_gerente_admin` FOR EACH ROW BEGIN
    DECLARE nombre_gerente VARCHAR(255);
    DECLARE nombre_sucursal VARCHAR(255);
    
    -- Obtener nombre del gerente
    SELECT CONCAT(nombre, ' ', apellido) INTO nombre_gerente
    FROM usuarios
    WHERE id = NEW.gerente_id;
    
    -- Obtener nombre de la sucursal
    SELECT nombre INTO nombre_sucursal
    FROM sucursales
    WHERE id = NEW.sucursal_id;
    
    -- Crear notificación para admin
    INSERT INTO notificaciones (
        tipo,
        titulo,
        mensaje,
        usuario_id,
        sucursal_id,
        url,
        leida,
        visible_para,
        fecha_creacion
    ) VALUES (
        'mensaje_gerente',
        CONCAT('Mensaje de ', nombre_gerente),
        CONCAT('[', UPPER(REPLACE(NEW.tipo_mensaje, '_', ' ')), '] ', NEW.asunto, ' - ', nombre_sucursal),
        NEW.gerente_id,
        NEW.sucursal_id,
        CONCAT('admin/mensajes.php?tab=gerentes&id=', NEW.id),
        0,
        'admin',
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_mensaje_gerente_update` AFTER UPDATE ON `mensajes_gerente_admin` FOR EACH ROW BEGIN
    -- Si se agregó una respuesta que antes no existía
    IF NEW.respuesta IS NOT NULL AND OLD.respuesta IS NULL THEN
        -- Crear notificación para el gerente con URL CORRECTA
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
            NEW.gerente_id,
            '../gerente/mensajes.php',  -- URL CORREGIDA
            0,
            'gerente',
            NOW()
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_internos`
--

CREATE TABLE `mensajes_internos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `asunto` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `archivo_adjunto` varchar(255) DEFAULT NULL COMMENT 'Archivo adjunto del cliente',
  `respuesta` text DEFAULT NULL,
  `archivo_respuesta` varchar(255) DEFAULT NULL COMMENT 'Archivo adjunto en respuesta',
  `estado` enum('pendiente','respondido','cerrado') DEFAULT 'pendiente',
  `prioridad` enum('baja','media','alta') DEFAULT 'media',
  `pedido_id` int(11) DEFAULT NULL,
  `respondido_por` int(11) DEFAULT NULL,
  `fecha_envio` datetime DEFAULT current_timestamp(),
  `fecha_respuesta` datetime DEFAULT NULL,
  `leido_cliente` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mensajes_internos`
--

INSERT INTO `mensajes_internos` (`id`, `usuario_id`, `asunto`, `mensaje`, `archivo_adjunto`, `respuesta`, `archivo_respuesta`, `estado`, `prioridad`, `pedido_id`, `respondido_por`, `fecha_envio`, `fecha_respuesta`, `leido_cliente`) VALUES
(1, 4, 'cambio', 'cambio prueba', NULL, 'OK', NULL, 'cerrado', 'media', NULL, 3, '2025-11-15 00:54:35', '2025-11-15 16:45:05', 0);

--
-- Disparadores `mensajes_internos`
--
DELIMITER $$
CREATE TRIGGER `after_mensaje_cliente_insert` AFTER INSERT ON `mensajes_internos` FOR EACH ROW BEGIN
    DECLARE nombre_cliente VARCHAR(255);
    
    -- Obtener nombre del cliente
    SELECT CONCAT(nombre, ' ', apellido) INTO nombre_cliente
    FROM usuarios
    WHERE id = NEW.usuario_id;
    
    -- Crear notificación para admin
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
        'mensaje_cliente',
        CONCAT('Mensaje de cliente: ', nombre_cliente),
        NEW.asunto,
        NEW.usuario_id,
        CONCAT('admin/mensajes.php?tab=clientes&id=', NEW.id),
        0,
        'admin',
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_mensaje_cliente_update` AFTER UPDATE ON `mensajes_internos` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `newsletter`
--

CREATE TABLE `newsletter` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_suscripcion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `tipo` enum('pedido','stock_bajo','nuevo_usuario','review','mensaje_cliente','mensaje_gerente','respuesta_admin','cambio_estado','sistema') NOT NULL DEFAULT 'sistema',
  `titulo` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `usuario_id` int(11) DEFAULT NULL COMMENT 'Usuario relacionado (si aplica)',
  `producto_id` int(11) DEFAULT NULL COMMENT 'Producto relacionado (si aplica)',
  `pedido_id` int(11) DEFAULT NULL COMMENT 'Pedido relacionado (si aplica)',
  `sucursal_id` int(11) DEFAULT NULL COMMENT 'Sucursal relacionada (para notif de stock)',
  `url` varchar(255) DEFAULT NULL COMMENT 'Link a la página relacionada',
  `leida` tinyint(1) DEFAULT 0 COMMENT '0=no leída, 1=leída',
  `visible_para` enum('admin','gerente','cliente','ambos') DEFAULT 'admin',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `tipo`, `titulo`, `mensaje`, `usuario_id`, `producto_id`, `pedido_id`, `sucursal_id`, `url`, `leida`, `visible_para`, `fecha_creacion`) VALUES
(1, 'stock_bajo', 'Stock bajo: Tacos Morena Tira única', 'El producto \"Tacos Morena Tira única\" tiene solo 5 unidades en sucursal Centro', NULL, 11, NULL, 1, 'admin/productos.php', 1, 'ambos', '2025-11-06 15:39:03'),
(2, 'nuevo_usuario', 'Nuevo usuario registrado', 'Un nuevo cliente se ha registrado en el sistema', NULL, NULL, NULL, NULL, 'admin/usuarios.php', 1, 'admin', '2025-11-06 15:39:03'),
(4, 'cambio_estado', 'Estado de producto cambiado', 'El producto \'Ojotas Infantiles\' ha sido activado', NULL, NULL, NULL, NULL, NULL, 1, 'ambos', '2025-11-07 03:24:57'),
(5, 'cambio_estado', 'Contraseña reseteada', 'Se reseteó la contraseña del Gerente: Patricia Sánchez', NULL, NULL, NULL, NULL, 'usuarios.php?id=11', 1, 'admin', '2025-11-12 14:13:26'),
(6, 'cambio_estado', 'Usuario activó', 'La cuenta de Fernando Ruiz ha sido activó', NULL, NULL, NULL, NULL, 'usuarios.php?id=14', 1, 'admin', '2025-11-12 14:29:43'),
(7, 'cambio_estado', 'Estado de producto cambiado', 'El producto \'Ojotas Infantiles\' ha sido desactivado', NULL, NULL, NULL, NULL, NULL, 1, 'ambos', '2025-11-12 18:51:33'),
(8, 'cambio_estado', 'Estado de producto cambiado', 'El producto \'Ojotas Infantiles\' ha sido activado', NULL, NULL, NULL, NULL, NULL, 1, 'ambos', '2025-11-12 18:51:37'),
(9, 'cambio_estado', 'Usuario activó', 'La cuenta de Jorge Ramírez ha sido activó', NULL, NULL, NULL, NULL, 'usuarios.php?id=8', 1, 'admin', '2025-11-12 18:55:20'),
(10, 'cambio_estado', 'Contraseña reseteada', 'Se reseteó la contraseña del Cliente: Laura Torres', NULL, NULL, NULL, NULL, 'usuarios.php?id=9', 1, 'admin', '2025-11-12 18:56:39'),
(11, 'cambio_estado', 'Usuario desactivó', 'La cuenta de video prueba ha sido desactivó', NULL, NULL, NULL, NULL, 'usuarios.php?id=19', 1, 'admin', '2025-11-12 21:00:15'),
(12, 'cambio_estado', 'Estado de producto cambiado', 'El producto \'Ojotas Infantiles\' ha sido desactivado', NULL, NULL, NULL, NULL, NULL, 1, 'ambos', '2025-11-12 21:01:22'),
(13, 'cambio_estado', 'Estado de producto cambiado', 'El producto \'Ojotas Infantiles\' ha sido activado', NULL, NULL, NULL, NULL, NULL, 1, 'ambos', '2025-11-12 21:01:29'),
(15, 'cambio_estado', 'Respuesta a tu mensaje', 'Re: solicito campus y ojotas', 11, NULL, NULL, NULL, 'gerente/mensajes.php?id=1', 0, 'gerente', '2025-11-15 19:23:05'),
(16, 'cambio_estado', 'Respuesta a tu mensaje', 'Re: cambio', 4, NULL, NULL, NULL, 'mensajes-internos.php?id=1', 1, 'admin', '2025-11-15 19:34:59'),
(17, 'cambio_estado', 'Estado de producto cambiado', 'El producto \'Ojotas Infantiles\' ha sido desactivado', NULL, NULL, NULL, NULL, NULL, 1, 'ambos', '2025-11-17 21:53:36'),
(18, 'cambio_estado', 'Estado de producto cambiado', 'El producto \'Ojotas Infantiles\' ha sido activado', NULL, NULL, NULL, NULL, NULL, 1, 'ambos', '2025-11-17 21:53:39'),
(19, 'cambio_estado', 'Estado de producto cambiado', 'El producto \'Zapatillas One Foot Infantiles\' ha sido desactivado', NULL, NULL, NULL, NULL, NULL, 1, 'ambos', '2025-11-17 22:05:28'),
(20, 'cambio_estado', 'Estado de producto cambiado', 'El producto \'Ojotas Infantiles\' ha sido desactivado', NULL, NULL, NULL, NULL, NULL, 1, 'ambos', '2025-12-05 00:21:20'),
(21, 'cambio_estado', 'Estado de producto cambiado', 'El producto \'Ojotas Infantiles\' ha sido activado', NULL, NULL, NULL, NULL, NULL, 1, 'ambos', '2025-12-05 00:21:22'),
(22, 'cambio_estado', 'Estado de producto cambiado', 'El producto \'Zapatillas One Foot Infantiles\' ha sido activado', NULL, NULL, NULL, NULL, NULL, 1, 'ambos', '2026-01-22 00:12:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(64) NOT NULL COMMENT 'Token único de recuperación',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_expiracion` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT 0 COMMENT '0=no usado, 1=ya usado',
  `ip_solicitud` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `numero_pedido` varchar(20) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `costo_envio` decimal(10,2) DEFAULT 0.00,
  `estado` enum('pendiente','confirmado','preparando','enviado','entregado','cancelado') DEFAULT 'pendiente',
  `metodo_pago` enum('efectivo','transferencia','tarjeta','mercadopago') NOT NULL,
  `estado_pago` enum('pendiente','pagado','rechazado') DEFAULT 'pendiente',
  `tipo_entrega` enum('retiro','envio') NOT NULL,
  `direccion_envio` text DEFAULT NULL,
  `ciudad_envio` varchar(100) DEFAULT NULL,
  `provincia_envio` varchar(100) DEFAULT NULL,
  `codigo_postal_envio` varchar(10) DEFAULT NULL,
  `telefono_contacto` varchar(20) DEFAULT NULL,
  `notas_cliente` text DEFAULT NULL,
  `fecha_pedido` datetime DEFAULT current_timestamp(),
  `fecha_confirmacion` datetime DEFAULT NULL,
  `fecha_envio` datetime DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `tipo_pedido` enum('cliente_tienda','sucursal_sucursal','sucursal_empresa') DEFAULT 'cliente_tienda' COMMENT 'Tipo de pedido',
  `sucursal_destino_id` int(11) DEFAULT NULL COMMENT 'Para pedidos entre sucursales',
  `notas_gerente` text DEFAULT NULL COMMENT 'Notas internas del gerente',
  `confirmado_por` int(11) DEFAULT NULL COMMENT 'ID del gerente que confirmó',
  `fecha_cancelacion` datetime DEFAULT NULL,
  `motivo_cancelacion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `usuario_id`, `sucursal_id`, `numero_pedido`, `total`, `subtotal`, `descuento`, `costo_envio`, `estado`, `metodo_pago`, `estado_pago`, `tipo_entrega`, `direccion_envio`, `ciudad_envio`, `provincia_envio`, `codigo_postal_envio`, `telefono_contacto`, `notas_cliente`, `fecha_pedido`, `fecha_confirmacion`, `fecha_envio`, `fecha_entrega`, `tipo_pedido`, `sucursal_destino_id`, `notas_gerente`, `confirmado_por`, `fecha_cancelacion`, `motivo_cancelacion`) VALUES
(1, 20, 3, 'MC-202511-0001', 89640.00, 89640.00, 0.00, 0.00, 'entregado', 'efectivo', 'pagado', 'retiro', 'CALLE 17', 'CAPITAL', 'Entre Ríos', '4700', '3834567890', '', '2025-11-15 17:18:23', '2025-11-15 17:18:54', NULL, '2025-12-04 21:21:51', 'cliente_tienda', NULL, NULL, NULL, NULL, NULL),
(2, 20, 2, 'MC-202511-0002', 35000.00, 35000.00, 0.00, 0.00, 'entregado', 'tarjeta', 'pagado', 'retiro', '', '', '', '', '3834567890', '', '2025-11-17 18:23:08', NULL, NULL, '2025-12-04 21:21:44', 'cliente_tienda', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `en_promocion` tinyint(1) DEFAULT 0,
  `descuento_porcentaje` int(11) DEFAULT 0,
  `stock` int(11) DEFAULT 0,
  `colores` text DEFAULT NULL COMMENT 'Stock por color en formato JSON: {"color":cantidad}',
  `talles` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `imagenes_adicionales` text DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL,
  `genero` enum('mujer','hombre','unisex','infantil') DEFAULT NULL,
  `destacado` tinyint(1) DEFAULT 0,
  `vistas` int(11) DEFAULT 0,
  `ventas` int(11) DEFAULT 0,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `descripcion`, `precio`, `en_promocion`, `descuento_porcentaje`, `stock`, `colores`, `talles`, `imagen`, `imagenes_adicionales`, `marca`, `material`, `genero`, `destacado`, `vistas`, `ventas`, `fecha_creacion`, `fecha_actualizacion`, `activo`) VALUES
(11, 1, 'Tacos Morena Tira única', 'Elegantes tacos', 20000.00, 0, 0, 50, '{\"Negro\":16,\"Blanco\":16,\"Gris\":16}', '38,39,40,41,42', 'TacoTira-morena.jpg', NULL, 'Morena', 'Cuero', 'mujer', 0, 1, 0, '2025-10-30 01:46:58', '2026-03-05 21:15:02', 1),
(12, 1, 'Sandalias de Goma', 'Sandalias verano', 15000.00, 0, 0, 50, '{\"Negro\":25,\"Beige\":25}', '35,36,37,38,39,40,41,42', 'SandaliaDeGoma.jpg', NULL, 'Comfort', 'Goma', 'mujer', 0, 0, 0, '2025-10-30 01:46:58', '2025-11-07 18:21:21', 1),
(13, 1, 'Tacos Morena Cruz', 'Tacos cruz', 20000.00, 0, 0, 50, '{\"Negro\":16,\"Blanco\":16,\"Gris\":16}', '38,39,40,41,42', 'TacoXMorena.jpg', NULL, 'Morena', 'Cuero', 'mujer', 0, 0, 0, '2025-10-30 01:46:58', '2025-11-07 18:21:21', 1),
(14, 1, 'Tacos Morena Tres', 'Tacos tres tiras', 20000.00, 0, 0, 50, '{\"Negro\":16,\"Blanco\":16,\"Gris\":16}', '38,39,40,41,42', 'TacoTresMorena.jpg', NULL, 'Morena', 'Cuero', 'mujer', 0, 0, 0, '2025-10-30 01:46:58', '2025-11-07 18:21:21', 1),
(15, 1, 'Zapatillas One Foot Mujer', 'Promo 2x30000', 18000.00, 1, 17, 50, '{\"Negro\":12,\"Blanco\":12,\"Gris\":12,\"Rosa\":12}', '38,39,40,41,42', 'ZapatillasOneFootFm.jpg', NULL, 'One Foot', 'Textil', 'mujer', 1, 1, 0, '2025-10-30 01:46:58', '2026-03-05 21:15:07', 1),
(16, 2, 'Zapatillas One Foot Hombre', 'Promo 2x30000', 18000.00, 1, 17, 50, '{\"Negro\":12,\"Verde\":12,\"Gris\":12,\"Azul\":12}', '37,38,39,40,41,42,43,44', 'ZapatillasOneFootHb.jpg', NULL, 'One Foot', 'Textil', 'hombre', 1, 0, 0, '2025-10-30 01:47:07', '2025-11-07 18:21:21', 1),
(17, 2, 'Zapatillas Bochin', 'Seguridad', 30000.00, 0, 0, 50, '{\"Negro\":16,\"Marrón\":16,\"Gris\":16}', '38,39,40,41,42,43,44', 'ZapatillasBochin.jpg', NULL, 'Bochin', 'Cuero', 'hombre', 0, 0, 0, '2025-10-30 01:47:07', '2025-11-07 18:21:21', 1),
(18, 2, 'Zapatos Stone', 'Casuales', 25000.00, 0, 0, 50, '{\"Negro\":16,\"Marrón\":16,\"Blanco\":16}', '38,39,40,41,42,43,44', 'ZapatoZapatillaStone.jpg', NULL, 'Stone', 'Cuero', 'hombre', 0, 0, 0, '2025-10-30 01:47:07', '2025-11-07 18:21:21', 1),
(19, 2, 'Campus Gamuza', 'Urbanas', 20000.00, 0, 0, 50, '{\"Negro\":16,\"Marrón\":16,\"Gris\":16}', '35,36,37,38,39,40,41,42,43,44', 'CampusGamusaHom.jpg', NULL, 'Campus', 'Gamuza', 'hombre', 0, 1, 0, '2025-10-30 01:47:07', '2025-12-02 01:32:15', 1),
(20, 2, 'Zapatos Golazos', 'Deportivos', 30000.00, 0, 0, 50, '{\"Negro\":16,\"Marrón\":16,\"Gris\":16}', '38,39,40,41,42,43,44', 'ZapatosGolazos.jpg', NULL, 'Golazos', 'Textil', 'hombre', 0, 0, 0, '2025-10-30 01:47:07', '2025-11-07 18:21:21', 1),
(21, 3, 'Ojotas Infantiles', 'Para niños', 5000.00, 0, 0, 50, '{\"Blanco\":12,\"Negro\":12,\"Celeste\":12,\"Rosa\":12}', '29,30,31,32,33,34,35,36,37,38,39,40', 'OjotasInfantiles.jpg', NULL, 'Kids', 'Goma', 'infantil', 0, 1, 0, '2025-10-30 01:47:18', '2026-03-05 21:06:10', 1),
(22, 3, 'Zapatillas One Foot Infantiles', 'Promo 2x25000', 15000.00, 1, 17, 50, '{\"Blanco\":12,\"Negro\":12,\"Celeste\":12,\"Rosa\":12}', '19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36', 'ZapatillasOneFootIn.jpg', NULL, 'One Foot', 'Textil', 'infantil', 1, 1, 0, '2025-10-30 01:47:18', '2026-03-05 21:14:31', 1),
(23, 3, 'New Balance Infantiles', 'Deportivas', 15000.00, 0, 0, 50, '{\"Blanco\":12,\"Negro\":12,\"Celeste\":12,\"Rosa\":12}', '28,29,30,31,32,33,34,35,36,37,38', 'ZapatillasNewBalanceIn.jpg', NULL, 'New Balance', 'Textil', 'infantil', 0, 0, 0, '2025-10-30 01:47:18', '2025-11-07 18:21:21', 1),
(24, 3, 'Mix Botines', 'Abotinadas', 18000.00, 0, 0, 50, '{\"Blanco\":8,\"Negro\":8,\"Celeste\":8,\"Rosa\":8,\"Naranja\":8,\"Rojo\":8}', '24,25,26,27,28,29,30,31,32,33,34,35,36,37,38', 'Mix BotinesMix.jpg', NULL, 'Mix', 'Textil', 'infantil', 0, 1, 0, '2025-10-30 01:47:18', '2025-12-01 23:11:55', 1),
(25, 3, 'Bic Boy Capibara', 'Divertidas', 15000.00, 0, 0, 50, '{\"Blanca\":16,\"Negra\":16,\"Azul\":16}', '24,25,26,27,28,29,30,31,32,33,34,35,36', 'ZapatillasBicBoy.jpg', NULL, 'Bic Boy', 'Textil', 'infantil', 0, 3, 0, '2025-10-30 01:47:18', '2026-03-05 21:11:51', 1);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `productos_completos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `productos_completos` (
`id` int(11)
,`categoria_id` int(11)
,`nombre` varchar(200)
,`descripcion` text
,`precio` decimal(10,2)
,`en_promocion` tinyint(1)
,`descuento_porcentaje` int(11)
,`stock` int(11)
,`colores` text
,`talles` varchar(255)
,`imagen` varchar(255)
,`imagenes_adicionales` text
,`marca` varchar(100)
,`material` varchar(100)
,`genero` enum('mujer','hombre','unisex','infantil')
,`destacado` tinyint(1)
,`vistas` int(11)
,`ventas` int(11)
,`fecha_creacion` datetime
,`fecha_actualizacion` datetime
,`activo` tinyint(1)
,`categoria_nombre` varchar(50)
,`categoria_slug` varchar(50)
,`precio_final` decimal(25,6)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `calificacion` tinyint(4) NOT NULL CHECK (`calificacion` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `aprobada` tinyint(1) DEFAULT 0 COMMENT '0=pendiente, 1=aprobada',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `rol_id` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`rol_id`, `nombre_rol`, `descripcion`, `fecha_creacion`) VALUES
(1, 'Cliente', 'Usuario final que puede comprar productos, gestionar su carrito y ver su historial de pedidos', '2025-11-04 20:53:37'),
(2, 'Gerente', 'Gerente de sucursal con acceso al dashboard de su sucursal específica, puede ver ventas y stock de su ubicación', '2025-11-04 20:53:37'),
(3, 'Admin', 'Administrador/Dueño con acceso total al sistema, puede gestionar todas las sucursales, productos, usuarios y ver estadísticas globales', '2025-11-04 20:53:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock_sucursal`
--

CREATE TABLE `stock_sucursal` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  `cantidad_minima` int(11) DEFAULT 10 COMMENT 'Alerta cuando stock < cantidad_minima',
  `ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `stock_sucursal`
--

INSERT INTO `stock_sucursal` (`id`, `producto_id`, `sucursal_id`, `cantidad`, `cantidad_minima`, `ultima_actualizacion`) VALUES
(1, 11, 1, 5, 10, '2025-11-06 15:39:03'),
(2, 12, 1, 27, 10, '2025-11-06 15:39:03'),
(3, 13, 1, 14, 10, '2025-11-06 15:39:03'),
(4, 14, 1, 28, 10, '2025-11-06 15:39:03'),
(5, 17, 1, 41, 10, '2025-11-06 15:39:03'),
(8, 18, 2, 12, 10, '2025-11-06 15:39:03'),
(9, 19, 2, 22, 10, '2025-11-12 21:18:51'),
(10, 20, 2, 30, 10, '2025-11-17 21:31:12'),
(11, 21, 2, 58, 10, '2025-11-12 20:31:37'),
(12, 23, 2, 17, 10, '2025-11-06 15:39:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursales`
--

CREATE TABLE `sucursales` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `ciudad` varchar(100) NOT NULL,
  `provincia` varchar(100) NOT NULL,
  `codigo_postal` varchar(10) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `horario_lunes_viernes` varchar(100) DEFAULT NULL,
  `horario_sabado` varchar(100) DEFAULT NULL,
  `horario_domingo` varchar(100) DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `horario_apertura_manana` time DEFAULT '09:00:00' COMMENT 'Apertura turno mañana',
  `horario_cierre_manana` time DEFAULT '13:00:00' COMMENT 'Cierre turno mañana',
  `horario_apertura_tarde` time DEFAULT '17:30:00' COMMENT 'Apertura turno tarde',
  `horario_cierre_tarde` time DEFAULT '21:30:00' COMMENT 'Cierre turno tarde',
  `trabaja_sabado` tinyint(1) DEFAULT 1 COMMENT '1=sí, 0=no',
  `trabaja_domingo` tinyint(1) DEFAULT 0 COMMENT '1=sí, 0=no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `sucursales`
--

INSERT INTO `sucursales` (`id`, `nombre`, `direccion`, `ciudad`, `provincia`, `codigo_postal`, `telefono`, `email`, `horario_lunes_viernes`, `horario_sabado`, `horario_domingo`, `latitud`, `longitud`, `activo`, `fecha_creacion`, `horario_apertura_manana`, `horario_cierre_manana`, `horario_apertura_tarde`, `horario_cierre_tarde`, `trabaja_sabado`, `trabaja_domingo`) VALUES
(1, 'Mauro Calzado Catamarca', 'San Martín 234', 'Catamarca Capital', 'Catamarca', 'K4700', '383-4804064', 'maurocalzado@gmail.com', NULL, NULL, NULL, NULL, NULL, 1, '2025-10-30 01:48:57', '09:00:00', '13:00:00', '17:30:00', '21:30:00', 1, 0),
(2, 'Mauro Calzado Chilecito', 'Adolfo Dávila 33', 'Chilecito', 'La Rioja', 'F5360', '383-4804064', 'maurocalzado@gmail.com', NULL, NULL, NULL, NULL, NULL, 1, '2025-10-30 01:48:57', '09:00:00', '13:00:00', '17:30:00', '21:30:00', 1, 0),
(3, 'Mauro Calzado La Rioja', 'Av. San Nicolás 412', 'La Rioja', 'La Rioja', 'F5300', '383-4804064', 'maurocalzado@gmail.com', NULL, NULL, NULL, NULL, NULL, 1, '2025-10-30 01:48:57', '09:00:00', '13:00:00', '17:30:00', '21:30:00', 1, 0),
(4, 'Mauro Calzado Tucumán', 'Av. 24 de Septiembre 182', 'Tucumán', 'Tucumán', 'T4000', '383-4804064', 'maurocalzado@gmail.com', NULL, NULL, NULL, NULL, NULL, 1, '2025-10-30 01:48:57', '09:00:00', '13:00:00', '17:30:00', '21:30:00', 1, 0),
(5, 'Mauro Calzado Salta', 'La Florida 139', 'Salta', 'Salta', 'A4400', '383-4804064', 'maurocalzado@gmail.com', NULL, NULL, NULL, NULL, NULL, 1, '2025-10-30 01:48:57', '09:00:00', '13:00:00', '17:30:00', '21:30:00', 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transferencias_stock`
--

CREATE TABLE `transferencias_stock` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `sucursal_origen_id` int(11) NOT NULL,
  `sucursal_destino_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` text DEFAULT NULL,
  `estado` enum('pendiente','en_transito','recibido','cancelado') DEFAULT 'pendiente',
  `solicitado_por` int(11) NOT NULL COMMENT 'Gerente que solicita',
  `autorizado_por` int(11) DEFAULT NULL COMMENT 'Admin que autoriza',
  `fecha_solicitud` datetime DEFAULT current_timestamp(),
  `fecha_envio` datetime DEFAULT NULL,
  `fecha_recepcion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos_caja`
--

CREATE TABLE `turnos_caja` (
  `id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `gerente_id` int(11) NOT NULL COMMENT 'Usuario que abrió el turno',
  `fecha_apertura` datetime NOT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `turno` enum('manana','tarde') NOT NULL COMMENT 'Turno mañana o tarde',
  `monto_inicial` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Efectivo al abrir caja',
  `efectivo_ventas` decimal(10,2) DEFAULT 0.00 COMMENT 'Total ventas en efectivo',
  `tarjeta_ventas` decimal(10,2) DEFAULT 0.00 COMMENT 'Total ventas con tarjeta',
  `transferencia_ventas` decimal(10,2) DEFAULT 0.00 COMMENT 'Total ventas con transferencia',
  `go_cuotas_ventas` decimal(10,2) DEFAULT 0.00 COMMENT 'Total ventas Go Cuotas',
  `credito_ventas` decimal(10,2) DEFAULT 0.00 COMMENT 'Total ventas a crédito (1ra cuota)',
  `cobro_cuotas_credito` decimal(10,2) DEFAULT 0.00 COMMENT 'Cobro de cuotas de créditos (separado)',
  `gastos_dia` decimal(10,2) DEFAULT 0.00 COMMENT 'Total gastos del día',
  `retiros` decimal(10,2) DEFAULT 0.00 COMMENT 'Retiros para depósitos/pagos',
  `depositos_banco` decimal(10,2) DEFAULT 0.00 COMMENT 'Depósitos realizados',
  `pares_vendidos` int(11) DEFAULT 0 COMMENT 'Cantidad de pares vendidos',
  `ingreso_pares` int(11) DEFAULT 0 COMMENT 'Pares que ingresaron al stock',
  `salida_pares` int(11) DEFAULT 0 COMMENT 'Pares enviados a otras sucursales',
  `efectivo_cierre` decimal(10,2) DEFAULT NULL COMMENT 'Efectivo contado al cerrar',
  `venta_total_dia` decimal(10,2) DEFAULT 0.00 COMMENT 'Total de ventas del día',
  `diferencia_caja` decimal(10,2) DEFAULT 0.00 COMMENT 'Diferencia entre esperado y contado',
  `notas_apertura` text DEFAULT NULL,
  `notas_cierre` text DEFAULT NULL,
  `estado` enum('abierto','cerrado') DEFAULT 'abierto',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `turnos_caja`
--

INSERT INTO `turnos_caja` (`id`, `sucursal_id`, `gerente_id`, `fecha_apertura`, `fecha_cierre`, `turno`, `monto_inicial`, `efectivo_ventas`, `tarjeta_ventas`, `transferencia_ventas`, `go_cuotas_ventas`, `credito_ventas`, `cobro_cuotas_credito`, `gastos_dia`, `retiros`, `depositos_banco`, `pares_vendidos`, `ingreso_pares`, `salida_pares`, `efectivo_cierre`, `venta_total_dia`, `diferencia_caja`, `notas_apertura`, `notas_cierre`, `estado`, `fecha_creacion`) VALUES
(1, 2, 11, '2025-11-12 11:15:32', '2025-11-12 11:19:31', 'manana', 10.00, 5000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, 0, 0, 5010.00, 5000.00, 0.00, 'cambio prueba', 'primera caja de prueba ', 'cerrado', '2025-11-12 14:15:32'),
(2, 2, 11, '2025-11-12 11:20:51', '2025-11-12 15:37:25', 'tarde', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0.00, 0.00, 0.00, '', '', 'cerrado', '2025-11-12 14:20:51'),
(3, 2, 11, '2025-11-12 15:38:45', '2025-11-12 15:48:30', 'manana', 10.00, 120000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4, 0, 0, 120010.00, 120000.00, 0.00, 'me demore por el trafico', '', 'cerrado', '2025-11-12 18:38:45'),
(4, 2, 11, '2025-11-12 17:28:26', '2025-11-12 17:33:28', 'manana', 10.00, 5000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, 0, 0, 5010.00, 5000.00, 0.00, '', '', 'cerrado', '2025-11-12 20:28:26'),
(5, 2, 11, '2025-11-12 17:52:26', '2025-11-12 17:58:04', 'manana', 1000.00, 150000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 100000.00, 0.00, 0.00, 5, 0, 0, 51000.00, 150000.00, 0.00, 'tarde por trafico', '', 'cerrado', '2025-11-12 20:52:26'),
(6, 2, 11, '2025-11-12 18:17:24', '2025-11-12 18:20:51', 'manana', 10000.00, 100000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5, 0, 0, 110000.00, 100000.00, 0.00, '', '', 'cerrado', '2025-11-12 21:17:24'),
(7, 2, 11, '2025-11-17 18:28:17', '2025-11-17 18:36:55', 'tarde', 10.00, 30000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, 0, 0, 30010.00, 30000.00, 0.00, 'sdjkfajsfna', '', 'cerrado', '2025-11-17 21:28:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL DEFAULT 1,
  `sucursal_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `dni` varchar(10) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `codigo_postal` varchar(10) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `verificado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `rol_id`, `sucursal_id`, `nombre`, `apellido`, `email`, `password`, `telefono`, `dni`, `direccion`, `ciudad`, `provincia`, `codigo_postal`, `fecha_registro`, `ultimo_acceso`, `activo`, `verificado`) VALUES
(3, 3, NULL, 'francisco', 'barrionuevo', 'fran2@example.com', '$2y$10$7eR1gUKH6QkW3h13XRf5qu37WPYXuTedaOSMgcrIpYflDsl1./qDK', '', NULL, NULL, NULL, NULL, NULL, '2025-10-17 13:53:12', '2026-04-22 00:08:54', 1, 0),
(4, 1, NULL, 'cliente', 'nuevo', 'cliente.nuevo@gmail.com', '$2y$10$pznQdnqQ54GtMtvPjL/sfOEY/xG/04e2w5ROYMtMXwrkelnE27.Ka', '3834678854', '43141074', '', '', '', '', '2025-11-04 18:49:09', '2026-01-26 20:43:41', 1, 0),
(5, 1, NULL, 'Ana', 'Martínez', 'ana.martinez@prueba.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3834111111', '30111111', NULL, NULL, NULL, NULL, '2025-11-11 00:57:21', NULL, 1, 0),
(6, 1, NULL, 'Carlos', 'López', 'carlos.lopez@prueba.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3834222222', '30222222', NULL, NULL, NULL, NULL, '2025-10-27 00:57:21', NULL, 1, 0),
(7, 1, NULL, 'María', 'Fernández', 'maria.fernandez@prueba.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3834333333', '30333333', NULL, NULL, NULL, NULL, '2025-10-12 00:57:21', NULL, 1, 0),
(8, 1, NULL, 'Jorge', 'Ramírez', 'jorge.ramirez@prueba.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3834444444', '30444444', NULL, NULL, NULL, NULL, '2025-09-27 00:57:21', NULL, 1, 0),
(9, 1, NULL, 'Laura', 'Torres', 'laura.torres@prueba.com', '$2y$10$qfQPvX3OR.m8Bxke3M6MsO2lhpix9HYt.pnxeKeXuAwVpwafmnNWu', '3834555555', '30555555', NULL, NULL, NULL, NULL, '2025-09-12 00:57:21', NULL, 1, 0),
(10, 2, 1, 'Roberto', 'González', 'roberto.gonzalez@prueba.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3834666666', '25666666', NULL, NULL, NULL, NULL, '2025-08-13 00:57:21', NULL, 1, 0),
(11, 2, 2, 'Patricia', 'Sánchez', 'patricia.sanchez@prueba.com', '$2y$10$3So7lSRuAw5d5HRhjS/zjOmeSTOESUtr5LoqBVAfbr2OaBqQW5FxW', '3834777777', '25777777', NULL, NULL, NULL, NULL, '2025-07-14 00:57:21', '2026-04-08 00:15:39', 1, 0),
(12, 2, 3, 'Miguel', 'Díaz', 'miguel.diaz@prueba.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3834888888', '25888888', NULL, NULL, NULL, NULL, '2025-06-14 00:57:21', NULL, 1, 0),
(13, 2, 4, 'Claudia', 'Pérez', 'claudia.perez@prueba.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3834999999', '25999999', NULL, NULL, NULL, NULL, '2025-05-15 00:57:21', NULL, 1, 0),
(14, 2, 5, 'Fernando', 'Ruiz', 'fernando.ruiz@prueba.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3834000000', '25000000', NULL, NULL, NULL, NULL, '2025-04-25 00:57:21', NULL, 1, 0),
(15, 3, NULL, 'Administrador', 'Principal', 'admin@prueba.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3834123123', '20123123', NULL, NULL, NULL, NULL, '2024-11-11 00:57:21', NULL, 1, 0),
(16, 3, NULL, 'Admin', 'Secundario', 'admin2@prueba.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3834456456', '20456456', NULL, NULL, NULL, NULL, '2025-01-15 00:57:21', NULL, 1, 0),
(19, 1, NULL, 'video', 'prueba', 'videodeprueba@gmail.com', '$2y$10$FQojYEdNHitxjQ5/xXrEwOhlPneME4eIIhBClY9OoRAsTUIG5dELO', '3834567890', '12345678', NULL, NULL, NULL, NULL, '2025-11-12 17:46:48', '2025-11-12 17:47:05', 0, 0),
(20, 1, NULL, 'cliente', 'nuevo', 'nuevo1@gmail.com', '$2y$10$OsUcMUB5HmHq23XGKzmAoeJOjEUBsaU/Vmc0F3WWuKgRGR/ku4x9m', '3834567890', '12345679', '', '', '', '', '2025-11-12 18:15:04', '2026-03-05 21:25:54', 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas_diarias`
--

CREATE TABLE `ventas_diarias` (
  `id` int(11) NOT NULL,
  `turno_id` int(11) NOT NULL COMMENT 'Turno al que pertenece',
  `sucursal_id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL COMMENT 'NULL si es venta manual sin producto en BD',
  `producto_nombre` varchar(255) NOT NULL COMMENT 'Nombre del producto',
  `talle` varchar(10) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','tarjeta','transferencia','go_cuotas','credito') NOT NULL,
  `tipo_venta` enum('mostrador','online') NOT NULL DEFAULT 'mostrador',
  `es_primera_cuota` tinyint(1) DEFAULT 0 COMMENT '1 si es primera cuota de crédito',
  `fecha_venta` datetime DEFAULT current_timestamp(),
  `notas` text DEFAULT NULL COMMENT 'Observaciones de la venta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `ventas_diarias`
--

INSERT INTO `ventas_diarias` (`id`, `turno_id`, `sucursal_id`, `producto_id`, `producto_nombre`, `talle`, `color`, `cantidad`, `precio_unitario`, `subtotal`, `metodo_pago`, `tipo_venta`, `es_primera_cuota`, `fecha_venta`, `notas`) VALUES
(1, 1, 2, NULL, 'ojotas ', '40', 'negro', 1, 5000.00, 5000.00, 'efectivo', 'mostrador', 0, '2025-11-12 11:16:53', 'venta de prueba'),
(2, 3, 2, 20, 'Zapatos Golazos', '40', 'Negro', 4, 30000.00, 120000.00, 'efectivo', 'mostrador', 0, '2025-11-12 15:45:29', NULL),
(3, 4, 2, 21, 'Ojotas Infantiles', '40', NULL, 1, 5000.00, 5000.00, 'efectivo', 'mostrador', 0, '2025-11-12 17:31:37', NULL),
(4, 5, 2, 20, 'Zapatos Golazos', '38', 'Marrón', 5, 30000.00, 150000.00, 'efectivo', 'mostrador', 0, '2025-11-12 17:57:14', NULL),
(5, 6, 2, 19, 'Campus Gamuza', '44', 'Negro', 5, 20000.00, 100000.00, 'efectivo', 'mostrador', 0, '2025-11-12 18:18:51', NULL),
(6, 7, 2, 20, 'Zapatos Golazos', '44', NULL, 1, 30000.00, 30000.00, 'efectivo', 'mostrador', 0, '2025-11-17 18:31:12', NULL);

--
-- Disparadores `ventas_diarias`
--
DELIMITER $$
CREATE TRIGGER `after_venta_insert` AFTER INSERT ON `ventas_diarias` FOR EACH ROW BEGIN
    -- Actualizar el total según el método de pago
    UPDATE turnos_caja 
    SET 
        efectivo_ventas = efectivo_ventas + IF(NEW.metodo_pago = 'efectivo', NEW.subtotal, 0),
        tarjeta_ventas = tarjeta_ventas + IF(NEW.metodo_pago = 'tarjeta', NEW.subtotal, 0),
        transferencia_ventas = transferencia_ventas + IF(NEW.metodo_pago = 'transferencia', NEW.subtotal, 0),
        go_cuotas_ventas = go_cuotas_ventas + IF(NEW.metodo_pago = 'go_cuotas', NEW.subtotal, 0),
        credito_ventas = credito_ventas + IF(NEW.metodo_pago = 'credito', NEW.subtotal, 0),
        venta_total_dia = venta_total_dia + NEW.subtotal,
        pares_vendidos = pares_vendidos + NEW.cantidad
    WHERE id = NEW.turno_id;
    
    -- Actualizar stock en stock_sucursal si el producto existe
    IF NEW.producto_id IS NOT NULL THEN
        UPDATE stock_sucursal 
        SET cantidad = cantidad - NEW.cantidad,
            ultima_actualizacion = NOW()
        WHERE producto_id = NEW.producto_id 
        AND sucursal_id = NEW.sucursal_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_mensajes_gerente_pendientes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_mensajes_gerente_pendientes` (
`gerente_id` int(11)
,`gerente_nombre` varchar(201)
,`sucursal_nombre` varchar(100)
,`sucursal_id` int(11)
,`mensajes_pendientes` bigint(21)
,`respuestas_sin_leer` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `productos_completos`
--
DROP TABLE IF EXISTS `productos_completos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `productos_completos`  AS SELECT `p`.`id` AS `id`, `p`.`categoria_id` AS `categoria_id`, `p`.`nombre` AS `nombre`, `p`.`descripcion` AS `descripcion`, `p`.`precio` AS `precio`, `p`.`en_promocion` AS `en_promocion`, `p`.`descuento_porcentaje` AS `descuento_porcentaje`, `p`.`stock` AS `stock`, `p`.`colores` AS `colores`, `p`.`talles` AS `talles`, `p`.`imagen` AS `imagen`, `p`.`imagenes_adicionales` AS `imagenes_adicionales`, `p`.`marca` AS `marca`, `p`.`material` AS `material`, `p`.`genero` AS `genero`, `p`.`destacado` AS `destacado`, `p`.`vistas` AS `vistas`, `p`.`ventas` AS `ventas`, `p`.`fecha_creacion` AS `fecha_creacion`, `p`.`fecha_actualizacion` AS `fecha_actualizacion`, `p`.`activo` AS `activo`, `c`.`nombre` AS `categoria_nombre`, `c`.`slug` AS `categoria_slug`, CASE WHEN `p`.`en_promocion` = 1 THEN `p`.`precio`- `p`.`precio` * `p`.`descuento_porcentaje` / 100 ELSE `p`.`precio` END AS `precio_final` FROM (`productos` `p` join `categorias` `c` on(`p`.`categoria_id` = `c`.`id`)) WHERE `p`.`activo` = 1 ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_mensajes_gerente_pendientes`
--
DROP TABLE IF EXISTS `vista_mensajes_gerente_pendientes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_mensajes_gerente_pendientes`  AS SELECT `g`.`id` AS `gerente_id`, concat(`g`.`nombre`,' ',`g`.`apellido`) AS `gerente_nombre`, `s`.`nombre` AS `sucursal_nombre`, `s`.`id` AS `sucursal_id`, count(`m`.`id`) AS `mensajes_pendientes`, count(case when `m`.`estado` = 'respondido' and `m`.`leido_gerente` = 0 then 1 end) AS `respuestas_sin_leer` FROM ((`usuarios` `g` join `sucursales` `s` on(`g`.`sucursal_id` = `s`.`id`)) left join `mensajes_gerente_admin` `m` on(`g`.`id` = `m`.`gerente_id`)) WHERE `g`.`rol_id` = 2 GROUP BY `g`.`id`, `s`.`id` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bajas_productos`
--
ALTER TABLE `bajas_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_producto` (`producto_id`),
  ADD KEY `idx_sucursal` (`sucursal_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_fecha` (`fecha_baja`),
  ADD KEY `idx_motivo` (`motivo`),
  ADD KEY `idx_notificado` (`notificado_admin`);

--
-- Indices de la tabla `banner_slides`
--
ALTER TABLE `banner_slides`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_session` (`session_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indices de la tabla `cobro_cuotas_credito`
--
ALTER TABLE `cobro_cuotas_credito`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_turno` (`turno_id`),
  ADD KEY `idx_sucursal` (`sucursal_id`),
  ADD KEY `idx_fecha` (`fecha_cobro`);

--
-- Indices de la tabla `contacto`
--
ALTER TABLE `contacto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_leido` (`leido`),
  ADD KEY `idx_fecha` (`fecha_envio`);

--
-- Indices de la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `idx_pedido` (`pedido_id`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorito` (`usuario_id`,`producto_id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `idx_usuario` (`usuario_id`);

--
-- Indices de la tabla `gastos_sucursal`
--
ALTER TABLE `gastos_sucursal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_turno` (`turno_id`),
  ADD KEY `idx_sucursal` (`sucursal_id`),
  ADD KEY `idx_fecha_gasto` (`fecha_gasto`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `fk_gasto_usuario` (`registrado_por`);

--
-- Indices de la tabla `imagenes_productos`
--
ALTER TABLE `imagenes_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_producto` (`producto_id`);

--
-- Indices de la tabla `mensajes_gerente_admin`
--
ALTER TABLE `mensajes_gerente_admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gerente` (`gerente_id`),
  ADD KEY `idx_sucursal` (`sucursal_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_tipo` (`tipo_mensaje`),
  ADD KEY `idx_fecha` (`fecha_envio`),
  ADD KEY `fk_msg_admin` (`admin_id`),
  ADD KEY `fk_msg_pedido` (`pedido_relacionado_id`),
  ADD KEY `idx_estado_gerente` (`estado`,`gerente_id`),
  ADD KEY `idx_estado_sucursal` (`estado`,`sucursal_id`);

--
-- Indices de la tabla `mensajes_internos`
--
ALTER TABLE `mensajes_internos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha` (`fecha_envio`),
  ADD KEY `fk_mensaje_pedido` (`pedido_id`),
  ADD KEY `fk_mensaje_respondido` (`respondido_por`),
  ADD KEY `idx_estado_usuario` (`estado`,`usuario_id`),
  ADD KEY `idx_fecha_estado` (`fecha_envio`,`estado`);

--
-- Indices de la tabla `newsletter`
--
ALTER TABLE `newsletter`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_leida` (`leida`),
  ADD KEY `idx_visible` (`visible_para`),
  ADD KEY `idx_fecha` (`fecha_creacion`),
  ADD KEY `idx_notif_usuario` (`usuario_id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `token` (`token`),
  ADD KEY `fecha_expiracion` (`fecha_expiracion`),
  ADD KEY `idx_token_valido` (`token`,`usado`,`fecha_expiracion`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_pedido` (`numero_pedido`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha` (`fecha_pedido`),
  ADD KEY `idx_usuario_fecha` (`usuario_id`,`fecha_pedido`),
  ADD KEY `idx_pedido_sucursal` (`sucursal_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_categoria` (`categoria_id`),
  ADD KEY `idx_precio` (`precio`),
  ADD KEY `idx_promocion` (`en_promocion`),
  ADD KEY `idx_destacado` (`destacado`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_categoria_precio` (`categoria_id`,`precio`),
  ADD KEY `idx_genero` (`genero`),
  ADD KEY `idx_stock` (`stock`);
ALTER TABLE `productos` ADD FULLTEXT KEY `idx_busqueda` (`nombre`,`descripcion`,`marca`);

--
-- Indices de la tabla `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_producto` (`producto_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_aprobada` (`aprobada`),
  ADD KEY `idx_fecha` (`fecha_creacion`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`rol_id`),
  ADD UNIQUE KEY `nombre_rol` (`nombre_rol`),
  ADD KEY `idx_nombre_rol` (`nombre_rol`);

--
-- Indices de la tabla `stock_sucursal`
--
ALTER TABLE `stock_sucursal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_producto_sucursal` (`producto_id`,`sucursal_id`),
  ADD KEY `idx_producto` (`producto_id`),
  ADD KEY `idx_sucursal` (`sucursal_id`),
  ADD KEY `idx_cantidad` (`cantidad`);

--
-- Indices de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_ciudad` (`ciudad`);

--
-- Indices de la tabla `transferencias_stock`
--
ALTER TABLE `transferencias_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_producto` (`producto_id`),
  ADD KEY `idx_origen` (`sucursal_origen_id`),
  ADD KEY `idx_destino` (`sucursal_destino_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `fk_trans_solicitante` (`solicitado_por`),
  ADD KEY `fk_trans_autorizador` (`autorizado_por`);

--
-- Indices de la tabla `turnos_caja`
--
ALTER TABLE `turnos_caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sucursal` (`sucursal_id`),
  ADD KEY `idx_gerente` (`gerente_id`),
  ADD KEY `idx_fecha_apertura` (`fecha_apertura`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `idx_dni` (`dni`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_rol_id` (`rol_id`),
  ADD KEY `idx_sucursal_id` (`sucursal_id`);

--
-- Indices de la tabla `ventas_diarias`
--
ALTER TABLE `ventas_diarias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_turno` (`turno_id`),
  ADD KEY `idx_sucursal` (`sucursal_id`),
  ADD KEY `idx_producto` (`producto_id`),
  ADD KEY `idx_fecha` (`fecha_venta`),
  ADD KEY `idx_metodo_pago` (`metodo_pago`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bajas_productos`
--
ALTER TABLE `bajas_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `banner_slides`
--
ALTER TABLE `banner_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `cobro_cuotas_credito`
--
ALTER TABLE `cobro_cuotas_credito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contacto`
--
ALTER TABLE `contacto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `gastos_sucursal`
--
ALTER TABLE `gastos_sucursal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `imagenes_productos`
--
ALTER TABLE `imagenes_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mensajes_gerente_admin`
--
ALTER TABLE `mensajes_gerente_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `mensajes_internos`
--
ALTER TABLE `mensajes_internos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `newsletter`
--
ALTER TABLE `newsletter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `rol_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `stock_sucursal`
--
ALTER TABLE `stock_sucursal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `transferencias_stock`
--
ALTER TABLE `transferencias_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `turnos_caja`
--
ALTER TABLE `turnos_caja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `ventas_diarias`
--
ALTER TABLE `ventas_diarias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bajas_productos`
--
ALTER TABLE `bajas_productos`
  ADD CONSTRAINT `bajas_productos_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bajas_productos_ibfk_2` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bajas_productos_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carrito_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cobro_cuotas_credito`
--
ALTER TABLE `cobro_cuotas_credito`
  ADD CONSTRAINT `fk_cobro_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cobro_turno` FOREIGN KEY (`turno_id`) REFERENCES `turnos_caja` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  ADD CONSTRAINT `detalle_pedidos_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_pedidos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `gastos_sucursal`
--
ALTER TABLE `gastos_sucursal`
  ADD CONSTRAINT `fk_gasto_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gasto_turno` FOREIGN KEY (`turno_id`) REFERENCES `turnos_caja` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_gasto_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `imagenes_productos`
--
ALTER TABLE `imagenes_productos`
  ADD CONSTRAINT `imagenes_productos_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mensajes_gerente_admin`
--
ALTER TABLE `mensajes_gerente_admin`
  ADD CONSTRAINT `fk_msg_admin` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_msg_gerente` FOREIGN KEY (`gerente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_pedido` FOREIGN KEY (`pedido_relacionado_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_msg_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mensajes_internos`
--
ALTER TABLE `mensajes_internos`
  ADD CONSTRAINT `fk_mensaje_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mensaje_respondido` FOREIGN KEY (`respondido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mensaje_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedido_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `stock_sucursal`
--
ALTER TABLE `stock_sucursal`
  ADD CONSTRAINT `stock_sucursal_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_sucursal_ibfk_2` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `transferencias_stock`
--
ALTER TABLE `transferencias_stock`
  ADD CONSTRAINT `fk_trans_autorizador` FOREIGN KEY (`autorizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_trans_destino` FOREIGN KEY (`sucursal_destino_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_trans_origen` FOREIGN KEY (`sucursal_origen_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_trans_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_trans_solicitante` FOREIGN KEY (`solicitado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `turnos_caja`
--
ALTER TABLE `turnos_caja`
  ADD CONSTRAINT `fk_turno_gerente` FOREIGN KEY (`gerente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_turno_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`rol_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_sucursales` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `ventas_diarias`
--
ALTER TABLE `ventas_diarias`
  ADD CONSTRAINT `fk_venta_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_venta_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_venta_turno` FOREIGN KEY (`turno_id`) REFERENCES `turnos_caja` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
