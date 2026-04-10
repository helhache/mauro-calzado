<?php
/**
 * AJAX - ACTUALIZAR STOCK (GERENTE)
 * Permite al gerente actualizar el stock de su sucursal
 */

require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente-admin.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// ============================================================================
// VALIDAR DATOS
// ============================================================================
if (empty($_POST['producto_id']) || empty($_POST['sucursal_id']) || !isset($_POST['cantidad']) || empty($_POST['operacion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$producto_id = intval($_POST['producto_id']);
$sucursal_id = intval($_POST['sucursal_id']);
$cantidad = intval($_POST['cantidad']);
$operacion = limpiarDato($_POST['operacion']);
$nota = limpiarDato($_POST['nota'] ?? '');

// Validar que el gerente solo pueda editar su sucursal
if (!esAdmin()) {
    $sucursal_gerente = obtenerSucursalGerente();
    if ($sucursal_id != $sucursal_gerente) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar esta sucursal']);
        exit;
    }
}

if ($cantidad < 0) {
    echo json_encode(['success' => false, 'message' => 'La cantidad no puede ser negativa']);
    exit;
}

// ============================================================================
// OBTENER STOCK ACTUAL
// ============================================================================
$query = "SELECT ss.cantidad, ss.cantidad_minima, p.nombre
          FROM stock_sucursal ss
          INNER JOIN productos p ON ss.producto_id = p.id
          WHERE ss.producto_id = ? AND ss.sucursal_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ii', $producto_id, $sucursal_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Registro de stock no encontrado']);
    exit;
}

$stock_data = mysqli_fetch_assoc($result);
$stock_actual   = $stock_data['cantidad'];
$cantidad_minima = $stock_data['cantidad_minima'] ?? 10;
$nombre_producto = $stock_data['nombre'];

// ============================================================================
// CALCULAR NUEVO STOCK SEGÚN OPERACIÓN
// ============================================================================
$nuevo_stock = 0;

if ($operacion === 'agregar') {
    $nuevo_stock = $stock_actual + $cantidad;
} elseif ($operacion === 'reemplazar') {
    $nuevo_stock = $cantidad;
} else {
    echo json_encode(['success' => false, 'message' => 'Operación no válida']);
    exit;
}

// ============================================================================
// ACTUALIZAR STOCK
// ============================================================================
$query_update = "UPDATE stock_sucursal 
                 SET cantidad = ?, ultima_actualizacion = NOW() 
                 WHERE producto_id = ? AND sucursal_id = ?";
$stmt_update = mysqli_prepare($conn, $query_update);
mysqli_stmt_bind_param($stmt_update, 'iii', $nuevo_stock, $producto_id, $sucursal_id);

if (mysqli_stmt_execute($stmt_update)) {
    // ============================================================================
    // ACTUALIZAR STOCK TOTAL DEL PRODUCTO
    // ============================================================================
    $query_total = "SELECT SUM(cantidad) as total FROM stock_sucursal WHERE producto_id = ?";
    $stmt_total = mysqli_prepare($conn, $query_total);
    mysqli_stmt_bind_param($stmt_total, 'i', $producto_id);
    mysqli_stmt_execute($stmt_total);
    $stock_total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_total))['total'] ?? 0;
    
    $query_update_prod = "UPDATE productos SET stock = ? WHERE id = ?";
    $stmt_update_prod = mysqli_prepare($conn, $query_update_prod);
    mysqli_stmt_bind_param($stmt_update_prod, 'ii', $stock_total, $producto_id);
    mysqli_stmt_execute($stmt_update_prod);
    
    // ============================================================================
    // REGISTRAR EN NOTIFICACIONES SI ES NECESARIO
    // ============================================================================
    $usuario_nombre = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'];
    
    // Obtener nombre de sucursal
    $query_suc = "SELECT nombre FROM sucursales WHERE id = ?";
    $stmt_suc = mysqli_prepare($conn, $query_suc);
    mysqli_stmt_bind_param($stmt_suc, 'i', $sucursal_id);
    mysqli_stmt_execute($stmt_suc);
    $sucursal_nombre = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_suc))['nombre'];
    
    if ($operacion === 'agregar') {
        $mensaje_notif = "El gerente {$usuario_nombre} agregó {$cantidad} unidades de \"{$nombre_producto}\" en {$sucursal_nombre}. Stock anterior: {$stock_actual}, Stock nuevo: {$nuevo_stock}";
    } else {
        $mensaje_notif = "El gerente {$usuario_nombre} actualizó el stock de \"{$nombre_producto}\" en {$sucursal_nombre} de {$stock_actual} a {$nuevo_stock} unidades (inventario)";
    }
    
    if (!empty($nota)) {
        $mensaje_notif .= ". Nota: " . $nota;
    }
    
    $query_notif = "INSERT INTO notificaciones (tipo, titulo, mensaje, producto_id, sucursal_id, visible_para, fecha_creacion)
                    VALUES ('sistema', 'Actualización de stock', ?, ?, ?, 'admin', NOW())";
    $stmt_notif = mysqli_prepare($conn, $query_notif);
    mysqli_stmt_bind_param($stmt_notif, 'sii', $mensaje_notif, $producto_id, $sucursal_id);
    mysqli_stmt_execute($stmt_notif);

    // Notificación de stock bajo si corresponde
    if ($nuevo_stock < $cantidad_minima) {
        $titulo_bajo   = "Stock bajo: {$nombre_producto}";
        $mensaje_bajo  = "El stock de \"{$nombre_producto}\" en {$sucursal_nombre} es de {$nuevo_stock} unidades (mínimo configurado: {$cantidad_minima})";
        $url_bajo      = "productos.php?stock_bajo=1";
        $stmt_bajo = mysqli_prepare($conn,
            "INSERT INTO notificaciones (tipo, titulo, mensaje, producto_id, sucursal_id, url, visible_para, fecha_creacion)
             VALUES ('stock_bajo', ?, ?, ?, ?, ?, 'admin', NOW())"
        );
        mysqli_stmt_bind_param($stmt_bajo, 'ssiis', $titulo_bajo, $mensaje_bajo, $producto_id, $sucursal_id, $url_bajo);
        mysqli_stmt_execute($stmt_bajo);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Stock actualizado correctamente',
        'stock_nuevo' => $nuevo_stock
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar el stock: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
