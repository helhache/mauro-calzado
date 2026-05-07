<?php
/**
 * Marcar notificaciones como leídas para el rol del usuario en sesión
 * Se llama automáticamente al abrir el dropdown de la campana
 */
require_once('../includes/config.php');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$rol_id = $_SESSION['rol_id'] ?? 0;

// rol_id: 1=cliente, 2=gerente, 3=admin (ajustar según tu BD)
if ($rol_id == 3) {
    $visible = ['admin', 'ambos'];
} elseif ($rol_id == 2) {
    $visible = ['gerente', 'ambos'];
} else {
    echo json_encode(['success' => false, 'message' => 'Rol no autorizado']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($visible), '?'));
$sql = "UPDATE notificaciones SET leida = 1 WHERE visible_para IN ($placeholders) AND leida = 0";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($visible)), ...$visible);
    mysqli_stmt_execute($stmt);
    echo json_encode(['success' => true, 'updated' => mysqli_stmt_affected_rows($stmt)]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
?>
