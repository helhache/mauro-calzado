<?php
/**
 * AJAX: Obtener colores y talles de un producto para el modal de carrito (público)
 */
require_once('../includes/config.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Solicitud inválida']);
    exit;
}

$id = intval($_GET['id']);

$stmt = mysqli_prepare($conn,
    "SELECT id, nombre, precio, imagen, colores, talles, en_promocion, descuento_porcentaje,
     CASE WHEN en_promocion = 1 THEN precio - (precio * descuento_porcentaje / 100) ELSE precio END AS precio_final
     FROM productos WHERE id = ? AND activo = 1 AND stock > 0"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$producto = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$producto) {
    echo json_encode(['success' => false, 'mensaje' => 'Producto no encontrado']);
    exit;
}

// Parsear colores: soporta formato CSV y JSON
$colores = [];
if (!empty($producto['colores'])) {
    $decoded = json_decode($producto['colores'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $colores = array_keys($decoded);
    } else {
        $colores = array_filter(array_map('trim', explode(',', $producto['colores'])));
    }
}

// Parsear talles: siempre CSV
$talles = [];
if (!empty($producto['talles'])) {
    $talles = array_filter(array_map('trim', explode(',', $producto['talles'])));
}

echo json_encode([
    'success'   => true,
    'id'        => (int)$producto['id'],
    'nombre'    => $producto['nombre'],
    'precio'    => floatval($producto['precio_final']),
    'imagen'    => $producto['imagen'],
    'colores'   => array_values($colores),
    'talles'    => array_values($talles),
]);
?>
