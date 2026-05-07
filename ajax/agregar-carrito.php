<?php
/**
 * AJAX/AGREGAR-CARRITO.PHP
 * Agrega un producto al carrito de sesión.
 * Clave de carrito: "{id}|{talle}|{color}" para soportar
 * múltiples talles/colores del mismo producto.
 */

require_once('../includes/config.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['id'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

// Verificar login
if (!estaLogueado()) {
    echo json_encode([
        'success'        => false,
        'mensaje'        => 'Crea tu cuenta para añadir productos',
        'requiere_login' => true,
        'redirect'       => 'login.php'
    ]);
    exit;
}

$producto_id   = intval($data['id']);
$cantidad      = max(1, intval($data['cantidad'] ?? 1));
$talle         = isset($data['talle'])  ? limpiarDato($data['talle'])  : '';
$color         = isset($data['color'])  ? limpiarDato($data['color'])  : '';

// Verificar que el producto existe y tiene stock
$stmt = mysqli_prepare($conn,
    "SELECT id, nombre, precio, imagen, stock, en_promocion, descuento_porcentaje,
     CASE WHEN en_promocion = 1 THEN precio - (precio * descuento_porcentaje / 100) ELSE precio END AS precio_final
     FROM productos WHERE id = ? AND activo = 1"
);
mysqli_stmt_bind_param($stmt, "i", $producto_id);
mysqli_stmt_execute($stmt);
$producto_db = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$producto_db) {
    echo json_encode(['success' => false, 'mensaje' => 'Producto no encontrado']);
    exit;
}
if ($producto_db['stock'] < $cantidad) {
    echo json_encode(['success' => false, 'mensaje' => 'Stock insuficiente']);
    exit;
}

// Inicializar carrito
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Clave compuesta para soportar mismo producto con distinto talle/color
$item_key = "{$producto_id}|{$talle}|{$color}";

if (isset($_SESSION['carrito'][$item_key])) {
    $_SESSION['carrito'][$item_key]['cantidad'] += $cantidad;
} else {
    $_SESSION['carrito'][$item_key] = [
        'id'       => $producto_id,
        'nombre'   => $producto_db['nombre'],
        'precio'   => floatval($producto_db['precio_final']),
        'imagen'   => $producto_db['imagen'],
        'cantidad' => $cantidad,
        'talle'    => $talle !== '' ? $talle : null,
        'color'    => $color !== '' ? $color : null,
    ];
}

// Guardar/actualizar en BD (simplificado: un registro por producto)
$usuario_id = $_SESSION['usuario_id'];
$stmt_check = mysqli_prepare($conn,
    "SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?"
);
mysqli_stmt_bind_param($stmt_check, "ii", $usuario_id, $producto_id);
mysqli_stmt_execute($stmt_check);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_check));
mysqli_stmt_close($stmt_check);

if ($row) {
    $nueva_cantidad = $row['cantidad'] + $cantidad;
    $stmt_upd = mysqli_prepare($conn,
        "UPDATE carrito SET cantidad = ?, fecha_agregado = NOW() WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt_upd, "ii", $nueva_cantidad, $row['id']);
    mysqli_stmt_execute($stmt_upd);
    mysqli_stmt_close($stmt_upd);
} else {
    $stmt_ins = mysqli_prepare($conn,
        "INSERT INTO carrito (usuario_id, producto_id, cantidad, fecha_agregado) VALUES (?, ?, ?, NOW())"
    );
    mysqli_stmt_bind_param($stmt_ins, "iii", $usuario_id, $producto_id, $cantidad);
    mysqli_stmt_execute($stmt_ins);
    mysqli_stmt_close($stmt_ins);
}

$cantidad_total = array_sum(array_column($_SESSION['carrito'], 'cantidad'));

echo json_encode([
    'success'        => true,
    'mensaje'        => 'Producto agregado al carrito',
    'cantidad_total' => $cantidad_total,
]);
?>
