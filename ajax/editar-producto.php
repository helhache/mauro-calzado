<?php
/**
 * AJAX - EDITAR PRODUCTO
 * Procesa el formulario de edición de productos
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// ============================================================================
// VALIDAR ID DEL PRODUCTO
// ============================================================================
if (empty($_POST['producto_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de producto no válido']);
    exit;
}

$producto_id = intval($_POST['producto_id']);

// Verificar que el producto existe
$query_check = "SELECT id, imagen FROM productos WHERE id = ?";
$stmt_check = mysqli_prepare($conn, $query_check);
mysqli_stmt_bind_param($stmt_check, 'i', $producto_id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    exit;
}

$producto_actual = mysqli_fetch_assoc($result_check);
$imagen_actual = $producto_actual['imagen'];

// ============================================================================
// VALIDAR CAMPOS OBLIGATORIOS
// ============================================================================
$errores = [];

if (empty($_POST['nombre'])) {
    $errores[] = 'El nombre del producto es obligatorio';
}

if (empty($_POST['precio']) || !is_numeric($_POST['precio'])) {
    $errores[] = 'El precio debe ser un número válido';
}

if (empty($_POST['genero'])) {
    $errores[] = 'Debes seleccionar una categoría';
}

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errores)]);
    exit;
}

// ============================================================================
// PROCESAR DATOS DEL FORMULARIO
// ============================================================================
$nombre = limpiarDato($_POST['nombre']);
$descripcion = limpiarDato($_POST['descripcion'] ?? '');
$precio = floatval($_POST['precio']);
$genero = limpiarDato($_POST['genero']);
$marca = limpiarDato($_POST['marca'] ?? '');
$material = limpiarDato($_POST['material'] ?? '');
$destacado = isset($_POST['destacado']) ? 1 : 0;
$activo = isset($_POST['activo']) ? 1 : 0;
$en_promocion = isset($_POST['en_promocion']) ? 1 : 0;
$descuento_porcentaje = $en_promocion ? intval($_POST['descuento_porcentaje'] ?? 0) : 0;

// Procesar colores con stock (viene en formato JSON)
$colores_json = $_POST['colores_json'] ?? '{}';
$colores_array = json_decode($colores_json, true);
$stock_total = array_sum($colores_array); // Calcular stock total

// Procesar talles
$talles = limpiarDato($_POST['talles'] ?? '');

// Mapeo de género a categoria_id
$categoria_map = [
    'mujer' => 1,
    'hombre' => 2,
    'infantil' => 3
];
$categoria_id = $categoria_map[$genero] ?? 1;

// ============================================================================
// PROCESAR IMAGEN (SI SE SUBIÓ UNA NUEVA)
// ============================================================================
$nombre_imagen = $imagen_actual; // Por defecto mantener la imagen actual

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['imagen'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    
    // Validar extensión
    if (!in_array($extension, $extensiones_permitidas)) {
        echo json_encode(['success' => false, 'message' => 'Formato de imagen no permitido. Use JPG, PNG o WEBP']);
        exit;
    }
    
    // Validar tamaño (2MB máximo)
    if ($archivo['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'La imagen no debe superar los 2MB']);
        exit;
    }
    
    // Generar nombre único para la nueva imagen
    $nombre_imagen = 'producto_' . time() . '_' . uniqid() . '.' . $extension;
    $ruta_destino = '../img/productos/' . $nombre_imagen;
    
    // Crear directorio si no existe
    if (!file_exists('../img/productos/')) {
        mkdir('../img/productos/', 0777, true);
    }
    
    // Mover nueva imagen
    if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        // Eliminar imagen anterior si existe
        if ($imagen_actual && file_exists('../img/productos/' . $imagen_actual)) {
            unlink('../img/productos/' . $imagen_actual);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al subir la nueva imagen']);
        exit;
    }
}

// ============================================================================
// ACTUALIZAR PRODUCTO EN BD
// ============================================================================
$query = "UPDATE productos SET 
          categoria_id = ?,
          nombre = ?,
          descripcion = ?,
          precio = ?,
          en_promocion = ?,
          descuento_porcentaje = ?,
          stock = ?,
          colores = ?,
          talles = ?,
          imagen = ?,
          marca = ?,
          material = ?,
          genero = ?,
          destacado = ?,
          activo = ?,
          fecha_actualizacion = NOW()
          WHERE id = ?";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    'issdiiisssssssiii',
    $categoria_id,
    $nombre,
    $descripcion,
    $precio,
    $en_promocion,
    $descuento_porcentaje,
    $stock_total,
    $colores_json,
    $talles,
    $nombre_imagen,
    $marca,
    $material,
    $genero,
    $destacado,
    $activo,
    $producto_id
);

if (mysqli_stmt_execute($stmt)) {
    // Registrar en notificaciones
    $query_notif = "INSERT INTO notificaciones (tipo, titulo, mensaje, visible_para, fecha_creacion) 
                    VALUES ('producto_actualizado', 'Producto actualizado', ?, 'ambos', NOW())";
    $stmt_notif = mysqli_prepare($conn, $query_notif);
    $mensaje_notif = "Se actualizó el producto: $nombre";
    mysqli_stmt_bind_param($stmt_notif, 's', $mensaje_notif);
    mysqli_stmt_execute($stmt_notif);
    
    echo json_encode([
        'success' => true,
        'message' => 'Producto actualizado exitosamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar el producto: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
