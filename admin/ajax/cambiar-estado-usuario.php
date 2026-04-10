<?php
/**
 * AJAX: CAMBIAR ESTADO DE USUARIO (Activar/Desactivar)
 * 
 * Cambia el estado activo de un usuario
 * Solo accesible por administradores
 */

require_once('../../includes/config.php');
require_once('../../includes/verificar-admin.php');

header('Content-Type: application/json');

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Recibir datos
$usuario_id = intval($_POST['usuario_id'] ?? 0);
$nuevo_estado = intval($_POST['nuevo_estado'] ?? 0); // 0 = Inactivo, 1 = Activo

// ============================================================================
// VALIDACIONES
// ============================================================================

if ($usuario_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de usuario inválido'
    ]);
    exit;
}

if (!in_array($nuevo_estado, [0, 1])) {
    echo json_encode([
        'success' => false,
        'message' => 'Estado inválido'
    ]);
    exit;
}

// Verificar que el usuario existe y obtener sus datos
$stmt = mysqli_prepare($conn, "SELECT id, nombre, apellido, rol_id, activo FROM usuarios WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $usuario_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no encontrado'
    ]);
    mysqli_stmt_close($stmt);
    exit;
}

$usuario = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Protección: No permitir que un admin se desactive a sí mismo
if ($usuario_id == $_SESSION['usuario_id'] && $nuevo_estado == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No puedes desactivar tu propia cuenta'
    ]);
    exit;
}

// Verificar si ya está en ese estado
if ($usuario['activo'] == $nuevo_estado) {
    $estado_texto = $nuevo_estado == 1 ? 'activo' : 'inactivo';
    echo json_encode([
        'success' => false,
        'message' => "El usuario ya está {$estado_texto}"
    ]);
    exit;
}

// ============================================================================
// CAMBIAR ESTADO
// ============================================================================

$query = "UPDATE usuarios SET activo = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $nuevo_estado, $usuario_id);

if (mysqli_stmt_execute($stmt)) {
    $accion = $nuevo_estado == 1 ? 'activado' : 'desactivado';
    $accion_titulo = $nuevo_estado == 1 ? 'activó' : 'desactivó';
    
    // Crear notificación
    $titulo_notif = "Usuario {$accion_titulo}";
    $mensaje_notif = "La cuenta de {$usuario['nombre']} {$usuario['apellido']} ha sido {$accion_titulo}";
    $url_notif = "usuarios.php?id={$usuario_id}";
    
    $stmt_notif = mysqli_prepare($conn, 
        "INSERT INTO notificaciones (tipo, titulo, mensaje, url, visible_para) 
         VALUES ('sistema', ?, ?, ?, 'admin')"
    );
    mysqli_stmt_bind_param($stmt_notif, "sss", $titulo_notif, $mensaje_notif, $url_notif);
    mysqli_stmt_execute($stmt_notif);
    mysqli_stmt_close($stmt_notif);
    
    echo json_encode([
        'success' => true,
        'message' => "Usuario {$accion} correctamente",
        'nuevo_estado' => $nuevo_estado
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cambiar el estado: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
