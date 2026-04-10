<?php
/**
 * AJAX - DAR DE BAJA PRODUCTO (GERENTE)
 * Registra bajas de productos y notifica al admin
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
if (empty($_POST['producto_id']) || empty($_POST['sucursal_id']) || 
    empty($_POST['cantidad']) || empty($_POST['motivo']) || empty($_POST['descripcion'])) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit;
}

$producto_id = intval($_POST['producto_id']);
$sucursal_id = intval($_POST['sucursal_id']);
$cantidad = intval($_POST['cantidad']);
$motivo = limpiarDato($_POST['motivo']);
$descripcion = limpiarDato($_POST['descripcion']);
$usuario_id = $_SESSION['usuario_id'];

// Validar que el gerente solo pueda dar de baja en su sucursal
if (!esAdmin()) {
    $sucursal_gerente = obtenerSucursalGerente();
    if ($sucursal_id != $sucursal_gerente) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para esta sucursal']);
        exit;
    }
}

if ($cantidad <= 0) {
    echo json_encode(['success' => false, 'message' => 'La cantidad debe ser mayor a 0']);
    exit;
}

// Validar motivos permitidos
$motivos_validos = ['mal_estado', 'vencido', 'dañado', 'robo', 'extravío', 'otro'];
if (!in_array($motivo, $motivos_validos)) {
    echo json_encode(['success' => false, 'message' => 'Motivo no válido']);
    exit;
}

// ============================================================================
// VERIFICAR STOCK DISPONIBLE
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
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado en esta sucursal']);
    exit;
}

$stock_data      = mysqli_fetch_assoc($result);
$stock_actual    = $stock_data['cantidad'];
$cantidad_minima = $stock_data['cantidad_minima'] ?? 10;
$nombre_producto = $stock_data['nombre'];

if ($cantidad > $stock_actual) {
    echo json_encode(['success' => false, 'message' => 'No hay suficiente stock disponible']);
    exit;
}

// ============================================================================
// INICIAR TRANSACCIÓN
// ============================================================================
mysqli_begin_transaction($conn);

try {
    // 1. Registrar la baja en la tabla bajas_productos
    $query_baja = "INSERT INTO bajas_productos (producto_id, sucursal_id, cantidad, motivo, descripcion, usuario_id, fecha_baja, notificado_admin) 
                   VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)";
    $stmt_baja = mysqli_prepare($conn, $query_baja);
    mysqli_stmt_bind_param($stmt_baja, 'iiissi', $producto_id, $sucursal_id, $cantidad, $motivo, $descripcion, $usuario_id);
    mysqli_stmt_execute($stmt_baja);
    $baja_id = mysqli_insert_id($conn);
    
    // 2. Restar del stock de la sucursal
    $nuevo_stock = $stock_actual - $cantidad;
    $query_update_stock = "UPDATE stock_sucursal 
                           SET cantidad = ?, ultima_actualizacion = NOW() 
                           WHERE producto_id = ? AND sucursal_id = ?";
    $stmt_update = mysqli_prepare($conn, $query_update_stock);
    mysqli_stmt_bind_param($stmt_update, 'iii', $nuevo_stock, $producto_id, $sucursal_id);
    mysqli_stmt_execute($stmt_update);
    
    // 3. Actualizar stock total del producto
    $query_total = "SELECT SUM(cantidad) as total FROM stock_sucursal WHERE producto_id = ?";
    $stmt_total = mysqli_prepare($conn, $query_total);
    mysqli_stmt_bind_param($stmt_total, 'i', $producto_id);
    mysqli_stmt_execute($stmt_total);
    $stock_total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_total))['total'] ?? 0;
    
    $query_update_prod = "UPDATE productos SET stock = ? WHERE id = ?";
    $stmt_update_prod = mysqli_prepare($conn, $query_update_prod);
    mysqli_stmt_bind_param($stmt_update_prod, 'ii', $stock_total, $producto_id);
    mysqli_stmt_execute($stmt_update_prod);
    
    // 4. Crear notificación para el admin
    $usuario_nombre = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'];
    
    // Obtener nombre de sucursal
    $query_suc = "SELECT nombre FROM sucursales WHERE id = ?";
    $stmt_suc = mysqli_prepare($conn, $query_suc);
    mysqli_stmt_bind_param($stmt_suc, 'i', $sucursal_id);
    mysqli_stmt_execute($stmt_suc);
    $sucursal_nombre = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_suc))['nombre'];
    
    $motivo_texto = str_replace('_', ' ', $motivo);
    $mensaje_notif = "⚠️ BAJA DE PRODUCTO: El gerente {$usuario_nombre} ({$sucursal_nombre}) dio de baja {$cantidad} unidades de \"{$nombre_producto}\" por motivo: {$motivo_texto}. Descripción: {$descripcion}";
    
    $query_notif = "INSERT INTO notificaciones (tipo, titulo, mensaje, producto_id, sucursal_id, visible_para, fecha_creacion)
                    VALUES ('sistema', 'Baja de producto', ?, ?, ?, 'admin', NOW())";
    $stmt_notif = mysqli_prepare($conn, $query_notif);
    mysqli_stmt_bind_param($stmt_notif, 'sii', $mensaje_notif, $producto_id, $sucursal_id);
    mysqli_stmt_execute($stmt_notif);

    // Notificación de stock bajo si el stock resultante queda por debajo del mínimo
    if ($nuevo_stock < $cantidad_minima) {
        $titulo_bajo  = "Stock bajo: {$nombre_producto}";
        $mensaje_bajo = "Tras la baja registrada, el stock de \"{$nombre_producto}\" en {$sucursal_nombre} quedó en {$nuevo_stock} unidades (mínimo: {$cantidad_minima})";
        $url_bajo     = "productos.php?stock_bajo=1";
        $stmt_bajo = mysqli_prepare($conn,
            "INSERT INTO notificaciones (tipo, titulo, mensaje, producto_id, sucursal_id, url, visible_para, fecha_creacion)
             VALUES ('stock_bajo', ?, ?, ?, ?, ?, 'admin', NOW())"
        );
        mysqli_stmt_bind_param($stmt_bajo, 'ssiis', $titulo_bajo, $mensaje_bajo, $producto_id, $sucursal_id, $url_bajo);
        mysqli_stmt_execute($stmt_bajo);
    }
    
    // 5. Marcar la baja como notificada
    $query_mark = "UPDATE bajas_productos SET notificado_admin = 1 WHERE id = ?";
    $stmt_mark = mysqli_prepare($conn, $query_mark);
    mysqli_stmt_bind_param($stmt_mark, 'i', $baja_id);
    mysqli_stmt_execute($stmt_mark);
    
    // Confirmar transacción
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Baja registrada correctamente. Se restaron ' . $cantidad . ' unidades del stock. El administrador ha sido notificado.',
        'stock_nuevo' => $nuevo_stock
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'message' => 'Error al registrar la baja: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
