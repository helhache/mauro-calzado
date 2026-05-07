<?php
/**
 * AJAX - CONFIRMAR RECEPCIÓN DE TRANSFERENCIA
 * El gerente confirma que recibió los pares.
 * - Actualiza transferencia a 'recibido'
 * - Agrega el producto a stock_sucursal si no existe, o suma si ya existe
 */

require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$transferencia_id = isset($_POST['transferencia_id']) ? intval($_POST['transferencia_id']) : 0;
$sucursal_id      = obtenerSucursalGerente();

if ($transferencia_id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de transferencia inválido']);
    exit;
}

// Verificar que la transferencia existe, está en_transito y el destino es MI sucursal
$stmt = mysqli_prepare($conn,
    "SELECT t.id, t.producto_id, t.cantidad, t.estado, t.sucursal_destino_id, p.nombre AS producto_nombre
     FROM transferencias_stock t
     INNER JOIN productos p ON t.producto_id = p.id
     WHERE t.id = ? AND t.sucursal_destino_id = ? AND t.estado = 'en_transito'"
);
mysqli_stmt_bind_param($stmt, 'ii', $transferencia_id, $sucursal_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$trans = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$trans) {
    echo json_encode(['success' => false, 'mensaje' => 'Transferencia no encontrada o no está en tránsito hacia tu sucursal']);
    exit;
}

$producto_id = $trans['producto_id'];
$cantidad    = $trans['cantidad'];

// 1) Marcar como recibida
$stmt = mysqli_prepare($conn,
    "UPDATE transferencias_stock SET estado = 'recibido', fecha_recepcion = NOW() WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $transferencia_id);
if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar la transferencia']);
    exit;
}
mysqli_stmt_close($stmt);

// 2) Agregar stock en MI sucursal (INSERT si no existe, UPDATE si existe)
$stmt = mysqli_prepare($conn,
    "SELECT id, cantidad FROM stock_sucursal WHERE producto_id = ? AND sucursal_id = ?"
);
mysqli_stmt_bind_param($stmt, 'ii', $producto_id, $sucursal_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$stock_actual = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if ($stock_actual) {
    // Ya existe: sumar
    $nuevo_stock = $stock_actual['cantidad'] + $cantidad;
    $stmt = mysqli_prepare($conn,
        "UPDATE stock_sucursal SET cantidad = ?, ultima_actualizacion = NOW()
         WHERE producto_id = ? AND sucursal_id = ?"
    );
    mysqli_stmt_bind_param($stmt, 'iii', $nuevo_stock, $producto_id, $sucursal_id);
} else {
    // No existe: crear con stock mínimo 10 por defecto
    $stmt = mysqli_prepare($conn,
        "INSERT INTO stock_sucursal (producto_id, sucursal_id, cantidad, cantidad_minima, ultima_actualizacion)
         VALUES (?, ?, ?, 10, NOW())"
    );
    mysqli_stmt_bind_param($stmt, 'iii', $producto_id, $sucursal_id, $cantidad);
    $nuevo_stock = $cantidad;
}

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => false, 'mensaje' => 'Transferencia marcada como recibida pero error al actualizar stock: ' . mysqli_error($conn)]);
    exit;
}
mysqli_stmt_close($stmt);

// 3) Actualizar stock total en tabla productos
$stmt = mysqli_prepare($conn,
    "UPDATE productos SET stock = (SELECT SUM(cantidad) FROM stock_sucursal WHERE producto_id = ?) WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, 'ii', $producto_id, $producto_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// 4) Notificación al admin
$sucursal_nombre = $_SESSION['sucursal_nombre'] ?? 'sucursal';
$stmt_noti = mysqli_prepare($conn,
    "INSERT INTO notificaciones (tipo, titulo, mensaje, producto_id, sucursal_id, visible_para)
     VALUES ('sistema', 'Stock recibido por transferencia', ?, ?, ?, 'admin')"
);
$msg_noti = "Se recibieron {$cantidad} unidades de \"{$trans['producto_nombre']}\" en la sucursal. Stock actualizado a {$nuevo_stock}.";
mysqli_stmt_bind_param($stmt_noti, 'sii', $msg_noti, $producto_id, $sucursal_id);
mysqli_stmt_execute($stmt_noti);
mysqli_stmt_close($stmt_noti);

echo json_encode([
    'success'     => true,
    'mensaje'     => "Recepción confirmada. Se agregaron {$cantidad} unidades al stock. Stock actual: {$nuevo_stock}.",
    'nuevo_stock' => $nuevo_stock
]);

mysqli_close($conn);
?>
