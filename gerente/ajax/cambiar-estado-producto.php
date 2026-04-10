<?php
/**
 * AJAX - CAMBIAR ESTADO PRODUCTO (GERENTE)
 * Permite al gerente activar/desactivar productos
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
if (empty($_POST['id']) || !isset($_POST['activo'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$producto_id = intval($_POST['id']);
$activo = intval($_POST['activo']);

if ($activo !== 0 && $activo !== 1) {
    echo json_encode(['success' => false, 'message' => 'Estado no válido']);
    exit;
}

// ============================================================================
// VERIFICAR QUE EL PRODUCTO EXISTE
// ============================================================================
$query = "SELECT nombre FROM productos WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $producto_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    exit;
}

$producto = mysqli_fetch_assoc($result);
$nombre_producto = $producto['nombre'];

// ============================================================================
// ACTUALIZAR ESTADO
// ============================================================================
$query_update = "UPDATE productos SET activo = ?, fecha_actualizacion = NOW() WHERE id = ?";
$stmt_update = mysqli_prepare($conn, $query_update);
mysqli_stmt_bind_param($stmt_update, 'ii', $activo, $producto_id);

if (mysqli_stmt_execute($stmt_update)) {
    $estado_texto = $activo ? 'activado' : 'desactivado';
    
    // ============================================================================
    // NOTIFICAR AL ADMIN
    // ============================================================================
    $usuario_nombre = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'];
    $sucursal_id = obtenerSucursalGerente();
    
    // Obtener nombre de sucursal
    $query_suc = "SELECT nombre FROM sucursales WHERE id = ?";
    $stmt_suc = mysqli_prepare($conn, $query_suc);
    mysqli_stmt_bind_param($stmt_suc, 'i', $sucursal_id);
    mysqli_stmt_execute($stmt_suc);
    $sucursal_nombre = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_suc))['nombre'];
    
    $mensaje_notif = "El gerente {$usuario_nombre} ({$sucursal_nombre}) ha {$estado_texto} el producto \"{$nombre_producto}\"";
    
    $query_notif = "INSERT INTO notificaciones (tipo, titulo, mensaje, producto_id, sucursal_id, visible_para, fecha_creacion) 
                    VALUES ('', 'Estado de producto cambiado', ?, ?, ?, 'admin', NOW())";
    $stmt_notif = mysqli_prepare($conn, $query_notif);
    mysqli_stmt_bind_param($stmt_notif, 'sii', $mensaje_notif, $producto_id, $sucursal_id);
    mysqli_stmt_execute($stmt_notif);
    
    echo json_encode([
        'success' => true,
        'message' => "Producto {$estado_texto} correctamente"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cambiar el estado: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
