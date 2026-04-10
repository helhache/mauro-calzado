<?php
/**
 * AJAX - ACTIVAR/DESACTIVAR PROMOCIÓN (GERENTE)
 * Permite al gerente gestionar promociones y notifica al admin
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
if (empty($_POST['producto_id']) || !isset($_POST['estado_actual'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$producto_id = intval($_POST['producto_id']);
$estado_actual = intval($_POST['estado_actual']);
$motivo = limpiarDato($_POST['motivo'] ?? '');

// ============================================================================
// OBTENER DATOS DEL PRODUCTO
// ============================================================================
$query = "SELECT nombre, precio, en_promocion, descuento_porcentaje FROM productos WHERE id = ?";
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
$precio = $producto['precio'];

// ============================================================================
// DETERMINAR ACCIÓN
// ============================================================================
$nuevo_estado = $estado_actual == 1 ? 0 : 1;
$descuento_porcentaje = 0;

if ($nuevo_estado == 1) {
    // Activar promoción - validar descuento
    if (empty($_POST['descuento_porcentaje'])) {
        echo json_encode(['success' => false, 'message' => 'Debes especificar el porcentaje de descuento']);
        exit;
    }
    
    $descuento_porcentaje = intval($_POST['descuento_porcentaje']);
    
    if ($descuento_porcentaje < 1 || $descuento_porcentaje > 100) {
        echo json_encode(['success' => false, 'message' => 'El descuento debe estar entre 1% y 100%']);
        exit;
    }
}

// ============================================================================
// ACTUALIZAR PROMOCIÓN
// ============================================================================
$query_update = "UPDATE productos 
                 SET en_promocion = ?, descuento_porcentaje = ?, fecha_actualizacion = NOW() 
                 WHERE id = ?";
$stmt_update = mysqli_prepare($conn, $query_update);
mysqli_stmt_bind_param($stmt_update, 'iii', $nuevo_estado, $descuento_porcentaje, $producto_id);

if (mysqli_stmt_execute($stmt_update)) {
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
    
    if ($nuevo_estado == 1) {
        // Promoción activada
        $precio_final = $precio * (1 - $descuento_porcentaje / 100);
        $mensaje_notif = "🏷️ PROMOCIÓN ACTIVADA: El gerente {$usuario_nombre} ({$sucursal_nombre}) activó una promoción de {$descuento_porcentaje}% en \"{$nombre_producto}\". Precio original: $" . 
                         number_format($precio, 2) . ", Precio con descuento: $" . number_format($precio_final, 2);
    } else {
        // Promoción desactivada
        $mensaje_notif = "El gerente {$usuario_nombre} ({$sucursal_nombre}) desactivó la promoción de \"{$nombre_producto}\"";
    }
    
    if (!empty($motivo)) {
        $mensaje_notif .= ". Motivo: " . $motivo;
    }
    
    $query_notif = "INSERT INTO notificaciones (tipo, titulo, mensaje, producto_id, sucursal_id, visible_para, fecha_creacion) 
                    VALUES ('', ?, ?, ?, ?, 'admin', NOW())";
    $titulo_notif = $nuevo_estado == 1 ? 'Promoción activada' : 'Promoción desactivada';
    $stmt_notif = mysqli_prepare($conn, $query_notif);
    mysqli_stmt_bind_param($stmt_notif, 'ssii', $titulo_notif, $mensaje_notif, $producto_id, $sucursal_id);
    mysqli_stmt_execute($stmt_notif);
    
    $mensaje_respuesta = $nuevo_estado == 1 
        ? "Promoción activada correctamente ({$descuento_porcentaje}% de descuento). El administrador ha sido notificado."
        : "Promoción desactivada correctamente. El administrador ha sido notificado.";
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje_respuesta,
        'estado_nuevo' => $nuevo_estado
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar la promoción: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
