<?php
/**
 * AJAX: CREAR NUEVO USUARIO
 * 
 * Crea un nuevo usuario en el sistema con validación completa
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
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$dni = trim($_POST['dni'] ?? '');
$rol_id = intval($_POST['rol_id'] ?? 1);
$sucursal_id = !empty($_POST['sucursal_id']) ? intval($_POST['sucursal_id']) : NULL;
$password = $_POST['password'] ?? '';

// ============================================================================
// VALIDACIONES
// ============================================================================

$errores = [];

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

if (empty($password)) {
    $errores[] = "La contraseña es obligatoria";
} elseif (strlen($password) < 6) {
    $errores[] = "La contraseña debe tener al menos 6 caracteres";
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
if ($rol_id != 2 && !empty($sucursal_id)) {
    $sucursal_id = NULL;
}

// Verificar si el email ya existe
$stmt = mysqli_prepare($conn, "SELECT id FROM usuarios WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $errores[] = "Ya existe un usuario con ese email";
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
// CREAR USUARIO
// ============================================================================

// Hashear contraseña
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Preparar query
if ($rol_id == 2) {
    // Gerente con sucursal
    $query = "INSERT INTO usuarios 
              (rol_id, sucursal_id, nombre, apellido, email, password, telefono, dni, activo, fecha_registro) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iissssss", $rol_id, $sucursal_id, $nombre, $apellido, $email, $password_hash, $telefono, $dni);
} else {
    // Cliente o Admin sin sucursal
    $query = "INSERT INTO usuarios 
              (rol_id, nombre, apellido, email, password, telefono, dni, activo, fecha_registro) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issssss", $rol_id, $nombre, $apellido, $email, $password_hash, $telefono, $dni);
}

if (mysqli_stmt_execute($stmt)) {
    $nuevo_id = mysqli_insert_id($conn);
    
    // Obtener el nombre del rol
    $stmt_rol = mysqli_prepare($conn, "SELECT nombre_rol FROM roles WHERE rol_id = ?");
    mysqli_stmt_bind_param($stmt_rol, "i", $rol_id);
    mysqli_stmt_execute($stmt_rol);
    $result_rol = mysqli_stmt_get_result($stmt_rol);
    $rol_data = mysqli_fetch_assoc($result_rol);
    $nombre_rol = $rol_data['nombre_rol'] ?? 'Usuario';
    mysqli_stmt_close($stmt_rol);
    
    // Crear notificación
    $titulo_notif = "Nuevo usuario registrado";
    $mensaje_notif = "Se ha creado un nuevo {$nombre_rol}: {$nombre} {$apellido}";
    $url_notif = "usuarios.php?id={$nuevo_id}";
    
    $stmt_notif = mysqli_prepare($conn, 
        "INSERT INTO notificaciones (tipo, titulo, mensaje, url, visible_para) 
         VALUES ('nuevo_usuario', ?, ?, ?, 'admin')"
    );
    mysqli_stmt_bind_param($stmt_notif, "sss", $titulo_notif, $mensaje_notif, $url_notif);
    mysqli_stmt_execute($stmt_notif);
    mysqli_stmt_close($stmt_notif);
    
    echo json_encode([
        'success' => true,
        'message' => "Usuario creado exitosamente",
        'usuario_id' => $nuevo_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear el usuario: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
