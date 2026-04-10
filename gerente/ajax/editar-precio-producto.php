<?php
/**
 * AJAX - EDITAR PRECIO PRODUCTO (GERENTE)
 * Permite al gerente cambiar el precio y notifica al admin
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
if (empty($_POST['producto_id']) || empty($_POST['precio_nuevo'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$producto_id = intval($_POST['producto_id']);
$precio_nuevo = floatval($_POST['precio_nuevo']);
$motivo = limpiarDato($_POST['motivo'] ?? '');

if ($precio_nuevo <= 0) {
    echo json_encode(['success' => false, 'message' => 'El precio debe ser mayor a 0']);
    exit;
}

// ============================================================================
// OBTENER PRECIO ACTUAL Y NOMBRE DEL PRODUCTO
// ============================================================================
$query = "SELECT nombre, precio FROM productos WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $producto_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    exit;
}

$producto = mysqli_fetch_assoc($result);
$precio_anterior = $producto['precio'];
$nombre_producto = $producto['nombre'];

// ============================================================================
// ACTUALIZAR PRECIO
// ============================================================================
$query_update = "UPDATE productos SET precio = ?, fecha_actualizacion = NOW() WHERE id = ?";
$stmt_update = mysqli_prepare($conn, $query_update);
mysqli_stmt_bind_param($stmt_update, 'di', $precio_nuevo, $producto_id);

if (mysqli_stmt_execute($stmt_update)) {
    // ============================================================================
    // NOTIFICAR AL ADMIN
    // ============================================================================
    $usuario_id = $_SESSION['usuario_id'];
    $usuario_nombre = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'];
    $sucursal_id = obtenerSucursalGerente();
    
    // Obtener nombre de sucursal
    $query_suc = "SELECT nombre FROM sucursales WHERE id = ?";
    $stmt_suc = mysqli_prepare($conn, $query_suc);
    mysqli_stmt_bind_param($stmt_suc, 'i', $sucursal_id);
    mysqli_stmt_execute($stmt_suc);
    $sucursal_nombre = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_suc))['nombre'];
    
    $cambio_porcentaje = (($precio_nuevo - $precio_anterior) / $precio_anterior) * 100;
    $direccion_cambio = $cambio_porcentaje > 0 ? 'aumentó' : 'redujo';
    $cambio_abs = abs($cambio_porcentaje);
    
    $mensaje_notif = "El gerente {$usuario_nombre} ({$sucursal_nombre}) {$direccion_cambio} el precio de \"{$nombre_producto}\" de $" . 
                     number_format($precio_anterior, 2) . " a $" . number_format($precio_nuevo, 2) . 
                     " (" . number_format($cambio_abs, 1) . "%)";
    
    if (!empty($motivo)) {
        $mensaje_notif .= ". Motivo: " . $motivo;
    }
    
    $query_notif = "INSERT INTO notificaciones (tipo, titulo, mensaje, producto_id, sucursal_id, visible_para, fecha_creacion) 
                    VALUES ('', 'Cambio de precio', ?, ?, ?, 'admin', NOW())";
    $stmt_notif = mysqli_prepare($conn, $query_notif);
    mysqli_stmt_bind_param($stmt_notif, 'sii', $mensaje_notif, $producto_id, $sucursal_id);
    mysqli_stmt_execute($stmt_notif);
    
    echo json_encode([
        'success' => true,
        'message' => 'Precio actualizado correctamente. El administrador ha sido notificado.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar el precio: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
