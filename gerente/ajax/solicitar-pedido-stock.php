<?php
/**
 * AJAX - SOLICITAR PEDIDO DE STOCK (recibir pares de otra sucursal)
 * Crea una transferencia donde MI sucursal es el DESTINO.
 * El gerente pide pares desde cualquier sucursal hacia la suya.
 */

require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$producto_id      = isset($data['producto_id'])      ? intval($data['producto_id'])      : 0;
$sucursal_origen  = isset($data['sucursal_origen_id']) ? intval($data['sucursal_origen_id']) : 0;
$cantidad         = isset($data['cantidad'])          ? intval($data['cantidad'])          : 0;
$motivo           = isset($data['motivo'])            ? limpiarDato($data['motivo'])       : '';

$sucursal_destino = obtenerSucursalGerente();
$gerente_id       = $_SESSION['usuario_id'];

if ($producto_id <= 0 || $sucursal_origen <= 0 || $cantidad <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

if ($sucursal_origen === $sucursal_destino) {
    echo json_encode(['success' => false, 'mensaje' => 'El origen y destino no pueden ser la misma sucursal']);
    exit;
}

// Verificar que el producto existe
$stmt = mysqli_prepare($conn, "SELECT nombre FROM productos WHERE id = ? AND activo = 1");
mysqli_stmt_bind_param($stmt, 'i', $producto_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$prod = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$prod) {
    echo json_encode(['success' => false, 'mensaje' => 'Producto no encontrado']);
    exit;
}

// Verificar que la sucursal origen existe
$stmt = mysqli_prepare($conn, "SELECT id FROM sucursales WHERE id = ? AND activo = 1");
mysqli_stmt_bind_param($stmt, 'i', $sucursal_origen);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) === 0) {
    echo json_encode(['success' => false, 'mensaje' => 'Sucursal origen no válida']);
    exit;
}
mysqli_stmt_close($stmt);

// Insertar solicitud: origen = sucursal elegida, destino = MI sucursal
$stmt = mysqli_prepare($conn,
    "INSERT INTO transferencias_stock
     (producto_id, sucursal_origen_id, sucursal_destino_id, cantidad, motivo, estado, solicitado_por)
     VALUES (?, ?, ?, ?, ?, 'pendiente', ?)"
);
mysqli_stmt_bind_param($stmt, 'iiiisi',
    $producto_id, $sucursal_origen, $sucursal_destino, $cantidad, $motivo, $gerente_id
);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    // Notificación al admin
    $stmt_noti = mysqli_prepare($conn,
        "INSERT INTO notificaciones (tipo, titulo, mensaje, visible_para)
         VALUES ('sistema', 'Solicitud de stock entre sucursales',
                 'Un gerente solicitó el envío de pares de otra sucursal a la suya. Verificar en Transferencias.',
                 'admin')"
    );
    mysqli_stmt_execute($stmt_noti);
    mysqli_stmt_close($stmt_noti);

    echo json_encode(['success' => true, 'mensaje' => 'Solicitud enviada. El administrador debe aprobarla y coordinar el envío.']);
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Error al registrar la solicitud: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
