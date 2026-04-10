<?php
/**
 * AJAX/AGREGAR-FAVORITO.PHP (VERSIÓN CORREGIDA)
 * 
 * CORRECCIONES:
 * 1. Agregar y eliminar favoritos correctamente
 * 2. Validación de usuario logueado
 * 3. Respuestas JSON mejoradas
 */

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

// Leer datos JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validar datos
if (empty($data['producto_id'])) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Producto no especificado'
    ]);
    exit;
}

// CORRECCIÓN: Verificar si el usuario está logueado
if (!estaLogueado()) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Debes iniciar sesión para usar favoritos',
        'requiere_login' => true
    ]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$producto_id = intval($data['producto_id']);
$accion = $data['accion'] ?? 'agregar';

// Verificar que el producto existe
$stmt_check = mysqli_prepare($conn, "SELECT id FROM productos WHERE id = ? AND activo = 1");
mysqli_stmt_bind_param($stmt_check, "i", $producto_id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) == 0) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Producto no encontrado'
    ]);
    exit;
}

// Verificar si ya está en favoritos
$stmt = mysqli_prepare($conn, 
    "SELECT id FROM favoritos WHERE usuario_id = ? AND producto_id = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $usuario_id, $producto_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$existe = mysqli_fetch_assoc($result);

if ($accion === 'eliminar' || $existe) {
    // CORRECCIÓN: Eliminar de favoritos
    $stmt_delete = mysqli_prepare($conn,
        "DELETE FROM favoritos WHERE usuario_id = ? AND producto_id = ?"
    );
    mysqli_stmt_bind_param($stmt_delete, "ii", $usuario_id, $producto_id);
    
    if (mysqli_stmt_execute($stmt_delete)) {
        echo json_encode([
            'success' => true,
            'mensaje' => 'Eliminado de favoritos',
            'accion' => 'eliminado'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error al eliminar de favoritos'
        ]);
    }
} else {
    // CORRECCIÓN: Agregar a favoritos
    $stmt_insert = mysqli_prepare($conn,
        "INSERT INTO favoritos (usuario_id, producto_id, fecha_agregado) 
         VALUES (?, ?, NOW())"
    );
    mysqli_stmt_bind_param($stmt_insert, "ii", $usuario_id, $producto_id);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        echo json_encode([
            'success' => true,
            'mensaje' => 'Agregado a favoritos',
            'accion' => 'agregado'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error al agregar a favoritos'
        ]);
    }
}
?>
