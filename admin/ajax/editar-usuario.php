<?php
/**
 * AJAX: EDITAR USUARIO
 * 
 * Actualiza los datos de un usuario existente
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

// Recibir y limpiar datos
$usuario_id = intval($_POST['usuario_id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$dni = trim($_POST['dni'] ?? '');
$rol_id = intval($_POST['rol_id'] ?? 1);
$sucursal_id = !empty($_POST['sucursal_id']) ? intval($_POST['sucursal_id']) : NULL;
$direccion = trim($_POST['direccion'] ?? '');
$ciudad = trim($_POST['ciudad'] ?? '');
$provincia = trim($_POST['provincia'] ?? '');
$codigo_postal = trim($_POST['codigo_postal'] ?? '');

// ============================================================================
// VALIDACIONES
// ============================================================================

$errores = [];

// Validar ID
if ($usuario_id <= 0) {
    $errores[] = "ID de usuario inválido";
}

// Validar campos obligatorios
if (empty($nombre)) {
    $errores[] = "El nombre es obligatorio";
}

if (empty($apellido)) {
    $errores[] = "El apellido es obligatorio";
}

if (empty($email)) {
    $errores[] = "El email es obligatorio";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "El email no es válido";
}

// Validar rol
if (!in_array($rol_id, [1, 2, 3])) {
    $errores[] = "Rol inválido";
}

// Si es gerente, debe tener sucursal asignada
if ($rol_id == 2 && empty($sucursal_id)) {
    $errores[] = "Los gerentes deben tener una sucursal asignada";
}

// Si NO es gerente, no debe tener sucursal
if ($rol_id != 2) {
    $sucursal_id = NULL;
}

// Verificar que el usuario existe
$stmt_check = mysqli_prepare($conn, "SELECT id, rol_id FROM usuarios WHERE id = ?");
mysqli_stmt_bind_param($stmt_check, "i", $usuario_id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no encontrado'
    ]);
    mysqli_stmt_close($stmt_check);
    exit;
}

$usuario_actual = mysqli_fetch_assoc($result_check);
mysqli_stmt_close($stmt_check);

// Verificar si el email ya existe en otro usuario
$stmt = mysqli_prepare($conn, "SELECT id FROM usuarios WHERE email = ? AND id != ?");
mysqli_stmt_bind_param($stmt, "si", $email, $usuario_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $errores[] = "Ya existe otro usuario con ese email";
}
mysqli_stmt_close($stmt);

// Si hay errores, retornar
if (!empty($errores)) {
    echo json_encode([
        'success' => false,
        'message' => implode('. ', $errores)
    ]);
    exit;
}

// ============================================================================
// ACTUALIZAR USUARIO
// ============================================================================

$query = "UPDATE usuarios SET 
          rol_id = ?,
          sucursal_id = ?,
          nombre = ?,
          apellido = ?,
          email = ?,
          telefono = ?,
          dni = ?,
          direccion = ?,
          ciudad = ?,
          provincia = ?,
          codigo_postal = ?
          WHERE id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iisssssssssi",
    $rol_id, $sucursal_id, $nombre, $apellido, $email, $telefono, $dni,
    $direccion, $ciudad, $provincia, $codigo_postal, $usuario_id
);

if (mysqli_stmt_execute($stmt)) {
    // Verificar si cambió el rol
    $cambio_rol = ($usuario_actual['rol_id'] != $rol_id);
    
    if ($cambio_rol) {
        // Mapear nombres de roles
        $roles_nombres = [ROL_CLIENTE => 'Cliente', ROL_GERENTE => 'Gerente', ROL_ADMIN => 'Administrador'];
        $rol_anterior = $roles_nombres[$usuario_actual['rol_id']] ?? 'Desconocido';
        $rol_nuevo    = $roles_nombres[$rol_id] ?? 'Desconocido';
        
        // Crear notificación de cambio de rol
        $titulo_notif = "Cambio de rol de usuario";
        $mensaje_notif = "El usuario {$nombre} {$apellido} cambió de {$rol_anterior} a {$rol_nuevo}";
        $url_notif = "usuarios.php?id={$usuario_id}";
        
        $stmt_notif = mysqli_prepare($conn, 
            "INSERT INTO notificaciones (tipo, titulo, mensaje, url, visible_para) 
             VALUES ('sistema', ?, ?, ?, 'admin')"
        );
        mysqli_stmt_bind_param($stmt_notif, "sss", $titulo_notif, $mensaje_notif, $url_notif);
        mysqli_stmt_execute($stmt_notif);
        mysqli_stmt_close($stmt_notif);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuario actualizado exitosamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar el usuario: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
