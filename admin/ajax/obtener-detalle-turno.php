<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-admin.php');

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID inválido']);
    exit;
}

// Turno principal
$stmt = mysqli_prepare($conn,
    "SELECT tc.*,
            s.nombre AS sucursal_nombre,
            CONCAT(u.nombre, ' ', u.apellido) AS gerente_nombre
     FROM turnos_caja tc
     LEFT JOIN sucursales s ON tc.sucursal_id = s.id
     LEFT JOIN usuarios   u ON tc.gerente_id  = u.id
     WHERE tc.id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$turno = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$turno) {
    echo json_encode(['success' => false, 'mensaje' => 'Turno no encontrado']);
    exit;
}

// Ventas
$stmt_v = mysqli_prepare($conn,
    "SELECT producto_nombre, talle, color, cantidad, precio_unitario, subtotal, metodo_pago, tipo_venta, fecha_venta, notas
     FROM ventas_diarias WHERE turno_id = ? ORDER BY fecha_venta ASC");
mysqli_stmt_bind_param($stmt_v, 'i', $id);
mysqli_stmt_execute($stmt_v);
$ventas = mysqli_fetch_all(mysqli_stmt_get_result($stmt_v), MYSQLI_ASSOC);

// Gastos
$stmt_g = mysqli_prepare($conn,
    "SELECT concepto, monto, tipo, descripcion, fecha_gasto
     FROM gastos_sucursal WHERE turno_id = ? ORDER BY fecha_gasto ASC");
mysqli_stmt_bind_param($stmt_g, 'i', $id);
mysqli_stmt_execute($stmt_g);
$gastos = mysqli_fetch_all(mysqli_stmt_get_result($stmt_g), MYSQLI_ASSOC);

// Cobros cuotas
$stmt_c = mysqli_prepare($conn,
    "SELECT cliente_nombre, cliente_dni, monto_cobrado, numero_cuota, observaciones, fecha_cobro
     FROM cobro_cuotas_credito WHERE turno_id = ? ORDER BY fecha_cobro ASC");
mysqli_stmt_bind_param($stmt_c, 'i', $id);
mysqli_stmt_execute($stmt_c);
$cobros = mysqli_fetch_all(mysqli_stmt_get_result($stmt_c), MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'turno'   => $turno,
    'ventas'  => $ventas,
    'gastos'  => $gastos,
    'cobros'  => $cobros,
]);
