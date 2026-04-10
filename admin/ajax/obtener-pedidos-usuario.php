<?php
/**
 * ADMIN/AJAX/OBTENER-PEDIDOS-USUARIO.PHP
 * Devuelve el historial de pedidos de un usuario (JSON)
 */

require_once('../../includes/config.php');
require_once('../../includes/verificar-admin.php');

header('Content-Type: application/json');

$usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : 0;

if ($usuario_id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de usuario inválido']);
    exit;
}

$stmt = mysqli_prepare($conn,
    "SELECT p.id, p.numero_pedido, p.total, p.estado, p.estado_pago,
            p.metodo_pago, p.tipo_entrega, p.fecha_pedido,
            s.nombre AS sucursal_nombre
     FROM pedidos p
     LEFT JOIN sucursales s ON p.sucursal_id = s.id
     WHERE p.usuario_id = ?
     ORDER BY p.fecha_pedido DESC"
);
mysqli_stmt_bind_param($stmt, 'i', $usuario_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pedidos = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Para cada pedido, obtener detalle
foreach ($pedidos as &$pedido) {
    $pedido_id = $pedido['id'];
    $stmt2 = mysqli_prepare($conn,
        "SELECT nombre_producto, cantidad, precio_unitario, talle, color, subtotal
         FROM detalle_pedidos WHERE pedido_id = ?"
    );
    mysqli_stmt_bind_param($stmt2, 'i', $pedido_id);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    $pedido['detalle'] = mysqli_fetch_all($res2, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt2);
}

echo json_encode(['success' => true, 'pedidos' => $pedidos]);
