<?php
/**
 * ADMIN/AJAX/ACTUALIZAR-TRANSFERENCIA.PHP
 * Permite al admin cambiar el estado de una transferencia de stock
 *
 * Estados: pendiente -> en_transito -> recibido (actualiza stock)
 *          pendiente/en_transito -> cancelado
 */

require_once('../../includes/config.php');
require_once('../../includes/verificar-admin.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$transferencia_id = isset($data['id'])     ? intval($data['id'])            : 0;
$nuevo_estado     = isset($data['estado']) ? limpiarDato($data['estado'])   : '';

$estados_validos = ['en_transito', 'recibido', 'cancelado'];

if ($transferencia_id <= 0 || !in_array($nuevo_estado, $estados_validos, true)) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

// Obtener transferencia actual
$stmt = mysqli_prepare($conn,
    "SELECT t.*, p.nombre AS producto_nombre
     FROM transferencias_stock t
     INNER JOIN productos p ON t.producto_id = p.id
     WHERE t.id = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $transferencia_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$t = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$t) {
    echo json_encode(['success' => false, 'mensaje' => 'Transferencia no encontrada']);
    exit;
}

// Validar transición de estado
$estado_actual = $t['estado'];
$transiciones_validas = [
    'pendiente'   => ['en_transito', 'cancelado'],
    'en_transito' => ['recibido', 'cancelado'],
];

if (!isset($transiciones_validas[$estado_actual]) || !in_array($nuevo_estado, $transiciones_validas[$estado_actual], true)) {
    echo json_encode(['success' => false, 'mensaje' => "No se puede pasar de '$estado_actual' a '$nuevo_estado'"]);
    exit;
}

$admin_id = $_SESSION['usuario_id'];

mysqli_begin_transaction($conn);

try {
    if ($nuevo_estado === 'en_transito') {
        // Descontar stock del origen
        $stmt = mysqli_prepare($conn,
            "UPDATE stock_sucursal SET cantidad = cantidad - ?
             WHERE producto_id = ? AND sucursal_id = ? AND cantidad >= ?"
        );
        mysqli_stmt_bind_param($stmt, 'iiii', $t['cantidad'], $t['producto_id'], $t['sucursal_origen_id'], $t['cantidad']);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) === 0) {
            throw new Exception("Stock insuficiente en la sucursal origen para realizar el envío");
        }
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn,
            "UPDATE transferencias_stock SET estado = 'en_transito', autorizado_por = ?, fecha_envio = NOW() WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'ii', $admin_id, $transferencia_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

    } elseif ($nuevo_estado === 'recibido') {
        // Sumar stock al destino (insertar si no existe)
        $stmt = mysqli_prepare($conn,
            "INSERT INTO stock_sucursal (producto_id, sucursal_id, cantidad)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)"
        );
        mysqli_stmt_bind_param($stmt, 'iii', $t['producto_id'], $t['sucursal_destino_id'], $t['cantidad']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Actualizar stock total del producto
        $stmt = mysqli_prepare($conn,
            "UPDATE productos SET stock = (SELECT SUM(cantidad) FROM stock_sucursal WHERE producto_id = ?) WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'ii', $t['producto_id'], $t['producto_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn,
            "UPDATE transferencias_stock SET estado = 'recibido', fecha_recepcion = NOW() WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'i', $transferencia_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

    } elseif ($nuevo_estado === 'cancelado') {
        // Si ya salió (en_transito), devolver stock al origen
        if ($estado_actual === 'en_transito') {
            $stmt = mysqli_prepare($conn,
                "UPDATE stock_sucursal SET cantidad = cantidad + ?
                 WHERE producto_id = ? AND sucursal_id = ?"
            );
            mysqli_stmt_bind_param($stmt, 'iii', $t['cantidad'], $t['producto_id'], $t['sucursal_origen_id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        $stmt = mysqli_prepare($conn,
            "UPDATE transferencias_stock SET estado = 'cancelado' WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'i', $transferencia_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    mysqli_commit($conn);

    $labels = ['en_transito' => 'En tránsito', 'recibido' => 'Recibido', 'cancelado' => 'Cancelado'];
    echo json_encode(['success' => true, 'mensaje' => "Transferencia marcada como: {$labels[$nuevo_estado]}"]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}
