<?php
/**
 * GERENTE/AJAX/SOLICITAR-TRANSFERENCIA.PHP
 * Crea una solicitud de transferencia de stock entre sucursales
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

$producto_id        = isset($data['producto_id'])        ? intval($data['producto_id'])        : 0;
$sucursal_destino   = isset($data['sucursal_destino_id']) ? intval($data['sucursal_destino_id']) : 0;
$cantidad           = isset($data['cantidad'])            ? intval($data['cantidad'])            : 0;
$motivo             = isset($data['motivo'])              ? limpiarDato($data['motivo'])         : '';

$sucursal_origen = obtenerSucursalGerente();
$gerente_id      = $_SESSION['usuario_id'];

if ($producto_id <= 0 || $sucursal_destino <= 0 || $cantidad <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

if ($sucursal_origen === $sucursal_destino) {
    echo json_encode(['success' => false, 'mensaje' => 'El origen y destino no pueden ser la misma sucursal']);
    exit;
}

// Verificar stock disponible en sucursal origen
$stmt = mysqli_prepare($conn,
    "SELECT cantidad FROM stock_sucursal WHERE producto_id = ? AND sucursal_id = ?"
);
mysqli_stmt_bind_param($stmt, 'ii', $producto_id, $sucursal_origen);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stock_row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$stock_row) {
    echo json_encode(['success' => false, 'mensaje' => 'Este producto no tiene stock registrado en tu sucursal']);
    exit;
}

if ($stock_row['cantidad'] < $cantidad) {
    echo json_encode(['success' => false, 'mensaje' => "Stock insuficiente. Disponible: {$stock_row['cantidad']} unidades"]);
    exit;
}

// Verificar que la sucursal destino existe
$stmt = mysqli_prepare($conn, "SELECT id FROM sucursales WHERE id = ? AND activo = 1");
mysqli_stmt_bind_param($stmt, 'i', $sucursal_destino);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) === 0) {
    echo json_encode(['success' => false, 'mensaje' => 'Sucursal destino no válida']);
    exit;
}
mysqli_stmt_close($stmt);

// Insertar solicitud de transferencia
$stmt = mysqli_prepare($conn,
    "INSERT INTO transferencias_stock
     (producto_id, sucursal_origen_id, sucursal_destino_id, cantidad, motivo, estado, solicitado_por)
     VALUES (?, ?, ?, ?, ?, 'pendiente', ?)"
);
mysqli_stmt_bind_param($stmt, 'iiiisi', $producto_id, $sucursal_origen, $sucursal_destino, $cantidad, $motivo, $gerente_id);

if (mysqli_stmt_execute($stmt)) {
    // Notificación al admin
    $stmt_noti = mysqli_prepare($conn,
        "INSERT INTO notificaciones (tipo, titulo, mensaje, visible_para)
         VALUES ('sistema', 'Nueva solicitud de transferencia', 'Un gerente ha solicitado una transferencia de stock entre sucursales.', 'admin')"
    );
    mysqli_stmt_execute($stmt_noti);
    mysqli_stmt_close($stmt_noti);

    echo json_encode(['success' => true, 'mensaje' => 'Solicitud enviada correctamente. El administrador debe aprobarla.']);
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Error al enviar la solicitud']);
}

mysqli_stmt_close($stmt);
