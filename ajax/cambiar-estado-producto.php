<?php
/**
 * AJAX - CAMBIAR ESTADO DE PRODUCTO
 * Activa o desactiva un producto (soft delete)
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
// VALIDAR PARÁMETROS
// ============================================================================
if (empty($_POST['id']) || !isset($_POST['activo'])) {
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
    exit;
}

$producto_id = intval($_POST['id']);
$activo = intval($_POST['activo']);

// Validar que activo sea 0 o 1
if ($activo !== 0 && $activo !== 1) {
    echo json_encode(['success' => false, 'message' => 'Estado no válido']);
    exit;
}

// ============================================================================
// VERIFICAR QUE EL PRODUCTO EXISTE
// ============================================================================
$query_check = "SELECT nombre FROM productos WHERE id = ?";
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

// ============================================================================
// ACTUALIZAR ESTADO DEL PRODUCTO
// ============================================================================
$query = "UPDATE productos SET activo = ?, fecha_actualizacion = NOW() WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ii', $activo, $producto_id);

if (mysqli_stmt_execute($stmt)) {
    $estado_texto = $activo ? 'activado' : 'desactivado';
    
    // Registrar en notificaciones
    $query_notif = "INSERT INTO notificaciones (tipo, titulo, mensaje, visible_para, fecha_creacion) 
                    VALUES ('producto_estado', 'Estado de producto cambiado', ?, 'ambos', NOW())";
    $stmt_notif = mysqli_prepare($conn, $query_notif);
    $mensaje_notif = "El producto '$nombre_producto' ha sido $estado_texto";
    mysqli_stmt_bind_param($stmt_notif, 's', $mensaje_notif);
    mysqli_stmt_execute($stmt_notif);
    
    echo json_encode([
        'success' => true,
        'message' => "Producto $estado_texto exitosamente"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cambiar el estado del producto'
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
