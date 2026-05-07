<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente.php');
header('Content-Type: application/json');

$sucursal_id = obtenerSucursalGerente();
$producto_id = (int)($_GET['producto_id'] ?? 0);
$talle       = limpiarDato($_GET['talle'] ?? '');
$color       = limpiarDato($_GET['color'] ?? '');

if (!$producto_id) { echo json_encode(['stock' => 0, 'fuente' => 'ninguna']); exit; }

// Buscar en detalle (color+talle exacto)
if ($talle !== '' && $color !== '') {
    try {
        $sql = "SELECT cantidad FROM stock_sucursal_detalle WHERE producto_id=? AND sucursal_id=? AND talle=? AND color=?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iiss", $producto_id, $sucursal_id, $talle, $color);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if ($row !== false && $row !== null) {
                echo json_encode(['stock' => (int)$row['cantidad'], 'fuente' => 'detalle']);
                exit;
            }
        }
    } catch (Exception $e) { /* tabla aún no existe, caer a fallback */ }
}

// Fallback: stock total de la sucursal
$sql = "SELECT cantidad FROM stock_sucursal WHERE producto_id=? AND sucursal_id=?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $producto_id, $sucursal_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    echo json_encode(['stock' => $row ? (int)$row['cantidad'] : 0, 'fuente' => 'total']);
} else {
    echo json_encode(['stock' => 0, 'fuente' => 'error']);
}
mysqli_close($conn);
?>
