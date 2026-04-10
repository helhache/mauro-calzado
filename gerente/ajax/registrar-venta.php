<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente.php');
header('Content-Type: application/json');

$sucursal_id = obtenerSucursalGerente();
$turno_id = (int)($_POST['turno_id'] ?? 0);

$tipo_venta    = limpiarDato($_POST['tipo_venta'] ?? '');
$metodo_pago   = limpiarDato($_POST['metodo_pago'] ?? '');
$producto_id   = !empty($_POST['producto_id']) ? (int)$_POST['producto_id'] : null;
$producto_nombre = limpiarDato($_POST['producto_nombre'] ?? '');
$cantidad        = (int)($_POST['cantidad'] ?? 0);
$precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
$subtotal        = $cantidad * $precio_unitario;
$talle           = !empty($_POST['talle']) ? limpiarDato($_POST['talle']) : null;
$color           = !empty($_POST['color']) ? limpiarDato($_POST['color']) : null;
$notas           = !empty($_POST['notas']) ? limpiarDato($_POST['notas']) : null;
$es_primera_cuota = ($metodo_pago == 'credito') ? 1 : 0;

// Si hay producto_id, verificar stock
if ($producto_id) {
    $sql = "SELECT cantidad FROM stock_sucursal WHERE producto_id = ? AND sucursal_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $producto_id, $sucursal_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if (!$result || $result['cantidad'] < $cantidad) {
        echo json_encode([
            'success' => false, 
            'message' => 'Stock insuficiente. Disponible: ' . ($result['cantidad'] ?? 0) . ' pares'
        ]);
        mysqli_close($conn);
        exit;
    }
}

// Insertar la venta
$sql = "INSERT INTO ventas_diarias (
        turno_id, sucursal_id, producto_id, producto_nombre, talle, color, cantidad, 
        precio_unitario, subtotal, metodo_pago, tipo_venta, es_primera_cuota, notas
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iiisssiddssss", 
    $turno_id, $sucursal_id, $producto_id, $producto_nombre, $talle, $color, $cantidad,
    $precio_unitario, $subtotal, $metodo_pago, $tipo_venta, $es_primera_cuota, $notas
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
}
mysqli_close($conn);
?>
