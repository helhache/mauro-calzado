<?php
/**
 * AJAX/AGREGAR-CARRITO.PHP (VERSIÓN CORREGIDA)
 * 
 * CORRECCIONES:
 * 1. Validar si el usuario está logueado
 * 2. Mostrar mensaje apropiado si no está registrado
 * 3. Mejoras en las respuestas JSON
 */

// Incluir configuración
require_once('../includes/config.php');

// Configurar header para JSON
header('Content-Type: application/json');

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Método no permitido'
    ]);
    exit;
}

// Leer datos JSON del body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validar datos recibidos
if (empty($data['id']) || empty($data['nombre']) || !isset($data['precio'])) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Datos incompletos'
    ]);
    exit;
}

// CORRECCIÓN: Verificar si el usuario está logueado
if (!estaLogueado()) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Crea tu cuenta para añadir productos',
        'requiere_login' => true,
        'redirect' => 'login.php'
    ]);
    exit;
}

// Limpiar y validar datos
$producto_id = intval($data['id']);
$producto_nombre = limpiarDato($data['nombre']);
$producto_precio = floatval($data['precio']);
$producto_imagen = limpiarDato($data['imagen'] ?? '');
$cantidad = isset($data['cantidad']) ? intval($data['cantidad']) : 1;

// Validar que el producto existe en la BD
$stmt = mysqli_prepare($conn, "SELECT id, nombre, precio, stock FROM productos WHERE id = ? AND activo = 1");
mysqli_stmt_bind_param($stmt, "i", $producto_id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (!$producto = mysqli_fetch_assoc($resultado)) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Producto no encontrado'
    ]);
    exit;
}

// Verificar stock disponible
if ($producto['stock'] < $cantidad) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Stock insuficiente'
    ]);
    exit;
}

// Inicializar carrito en sesión si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Agregar o actualizar producto en carrito
if (isset($_SESSION['carrito'][$producto_id])) {
    // Producto ya existe, aumentar cantidad
    $_SESSION['carrito'][$producto_id]['cantidad'] += $cantidad;
} else {
    // Producto nuevo, agregarlo
    $_SESSION['carrito'][$producto_id] = [
        'id' => $producto_id,
        'nombre' => $producto_nombre,
        'precio' => $producto_precio,
        'imagen' => $producto_imagen,
        'cantidad' => $cantidad,
        'talle' => null,
        'color' => null
    ];
}

// Si el usuario está logueado, también guardar en BD
$usuario_id = $_SESSION['usuario_id'];

// Verificar si ya existe en BD
$stmt_check = mysqli_prepare($conn, 
    "SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?"
);
mysqli_stmt_bind_param($stmt_check, "ii", $usuario_id, $producto_id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if ($row = mysqli_fetch_assoc($result_check)) {
    // Ya existe, actualizar cantidad
    $nueva_cantidad = $row['cantidad'] + $cantidad;
    $stmt_update = mysqli_prepare($conn,
        "UPDATE carrito SET cantidad = ?, fecha_agregado = NOW() WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt_update, "ii", $nueva_cantidad, $row['id']);
    mysqli_stmt_execute($stmt_update);
} else {
    // No existe, insertar
    $stmt_insert = mysqli_prepare($conn,
        "INSERT INTO carrito (usuario_id, producto_id, cantidad, fecha_agregado) 
         VALUES (?, ?, ?, NOW())"
    );
    mysqli_stmt_bind_param($stmt_insert, "iii", $usuario_id, $producto_id, $cantidad);
    mysqli_stmt_execute($stmt_insert);
}

// Calcular cantidad total de productos
$cantidad_total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $cantidad_total += $item['cantidad'];
}

// CORRECCIÓN: Respuesta exitosa mejorada
echo json_encode([
    'success' => true,
    'mensaje' => 'Producto agregado al carrito ✓',
    'cantidad_total' => $cantidad_total,
    'carrito' => $_SESSION['carrito']
]);
?>
