<?php
require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

header('Content-Type: application/json');

$producto_id = (int)($_GET['producto_id'] ?? 0);
if ($producto_id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID inválido']);
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, imagen, orden FROM imagenes_productos WHERE producto_id = ? ORDER BY orden ASC, id ASC");
mysqli_stmt_bind_param($stmt, 'i', $producto_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$imagenes = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo json_encode(['success' => true, 'imagenes' => $imagenes]);
