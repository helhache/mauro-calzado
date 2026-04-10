<?php
/**
 * AJAX/GUARDAR-REVIEW.PHP
 * Guarda una nueva reseña de producto (pendiente de aprobación admin)
 */

require_once('../includes/config.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

// Solo clientes logueados pueden dejar reseña
if (!estaLogueado() || !esCliente()) {
    echo json_encode(['success' => false, 'mensaje' => 'Debes iniciar sesión como cliente para dejar una reseña']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$producto_id  = isset($data['producto_id'])  ? intval($data['producto_id'])  : 0;
$calificacion = isset($data['calificacion']) ? intval($data['calificacion']) : 0;
$comentario   = isset($data['comentario'])   ? limpiarDato($data['comentario']) : '';

if ($producto_id <= 0 || $calificacion < 1 || $calificacion > 5) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Verificar que el producto existe
$stmt = mysqli_prepare($conn, "SELECT id FROM productos WHERE id = ? AND activo = 1");
mysqli_stmt_bind_param($stmt, 'i', $producto_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) === 0) {
    echo json_encode(['success' => false, 'mensaje' => 'Producto no encontrado']);
    exit;
}
mysqli_stmt_close($stmt);

// Verificar si ya dejó una reseña
$stmt = mysqli_prepare($conn, "SELECT id FROM reviews WHERE producto_id = ? AND usuario_id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $producto_id, $usuario_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    echo json_encode(['success' => false, 'mensaje' => 'Ya dejaste una reseña para este producto']);
    exit;
}
mysqli_stmt_close($stmt);

// Insertar reseña (aprobada = 0, pendiente de moderación)
$stmt = mysqli_prepare($conn,
    "INSERT INTO reviews (producto_id, usuario_id, calificacion, comentario, aprobada) VALUES (?, ?, ?, ?, 0)"
);
mysqli_stmt_bind_param($stmt, 'iiis', $producto_id, $usuario_id, $calificacion, $comentario);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'mensaje' => 'Reseña enviada correctamente. Será visible una vez que sea aprobada por el administrador.']);
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Error al guardar la reseña']);
}

mysqli_stmt_close($stmt);
