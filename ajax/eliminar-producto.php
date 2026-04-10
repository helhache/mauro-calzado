<?php
/**
 * AJAX - ELIMINAR PRODUCTO
 * Elimina permanentemente un producto de la base de datos
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// ============================================================================
// VALIDAR ID DEL PRODUCTO
// ============================================================================
if (empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de producto no válido']);
    exit;
}

$producto_id = intval($_POST['id']);

// ============================================================================
// OBTENER INFORMACIÓN DEL PRODUCTO ANTES DE ELIMINAR
// ============================================================================
$query_check = "SELECT nombre, imagen FROM productos WHERE id = ?";
$stmt_check = mysqli_prepare($conn, $query_check);
mysqli_stmt_bind_param($stmt_check, 'i', $producto_id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    exit;
}

$producto = mysqli_fetch_assoc($result_check);
$nombre_producto = $producto['nombre'];
$imagen_producto = $producto['imagen'];

// ============================================================================
// VERIFICAR SI HAY PEDIDOS ASOCIADOS
// ============================================================================
$query_pedidos = "SELECT COUNT(*) as total FROM detalle_pedidos WHERE producto_id = ?";
$stmt_pedidos = mysqli_prepare($conn, $query_pedidos);
mysqli_stmt_bind_param($stmt_pedidos, 'i', $producto_id);
mysqli_stmt_execute($stmt_pedidos);
$result_pedidos = mysqli_stmt_get_result($stmt_pedidos);
$pedidos_count = mysqli_fetch_assoc($result_pedidos)['total'];

// Si hay pedidos, mejor desactivar en lugar de eliminar
if ($pedidos_count > 0) {
    $query_desactivar = "UPDATE productos SET activo = 0, fecha_actualizacion = NOW() WHERE id = ?";
    $stmt_desactivar = mysqli_prepare($conn, $query_desactivar);
    mysqli_stmt_bind_param($stmt_desactivar, 'i', $producto_id);
    
    if (mysqli_stmt_execute($stmt_desactivar)) {
        echo json_encode([
            'success' => true,
            'message' => 'El producto tiene pedidos asociados y fue desactivado en su lugar'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al desactivar el producto'
        ]);
    }
    exit;
}

// ============================================================================
// ELIMINAR PRODUCTO
// ============================================================================
$query = "DELETE FROM productos WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $producto_id);

if (mysqli_stmt_execute($stmt)) {
    // Eliminar imagen física del servidor
    if ($imagen_producto && file_exists('../img/productos/' . $imagen_producto)) {
        unlink('../img/productos/' . $imagen_producto);
    }
    
    // Registrar en notificaciones
    $query_notif = "INSERT INTO notificaciones (tipo, titulo, mensaje, visible_para, fecha_creacion) 
                    VALUES ('producto_eliminado', 'Producto eliminado', ?, 'ambos', NOW())";
    $stmt_notif = mysqli_prepare($conn, $query_notif);
    $mensaje_notif = "El producto '$nombre_producto' ha sido eliminado permanentemente";
    mysqli_stmt_bind_param($stmt_notif, 's', $mensaje_notif);
    mysqli_stmt_execute($stmt_notif);
    
    echo json_encode([
        'success' => true,
        'message' => 'Producto eliminado exitosamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar el producto: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
