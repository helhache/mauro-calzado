<?php
/**
 * AJAX - CREAR PRODUCTO
 * Procesa el formulario de creación de productos
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

// ============================================================================
// PROCESAR IMAGEN
// ============================================================================
$nombre_imagen = '';

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
    
    // Generar nombre único para la imagen
    $nombre_imagen = 'producto_' . time() . '_' . uniqid() . '.' . $extension;
    $ruta_destino = '../img/productos/' . $nombre_imagen;
    
    // Crear directorio si no existe
    if (!file_exists('../img/productos/')) {
        mkdir('../img/productos/', 0777, true);
    }
    
    // Mover imagen
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        echo json_encode(['success' => false, 'message' => 'Error al subir la imagen']);
        exit;
    }
}

// ============================================================================
// INSERTAR PRODUCTO EN BD
// ============================================================================

// Primero necesitamos obtener o crear el categoria_id basado en el género
// Asumiendo que la tabla categorias tiene: 1=Mujer, 2=Hombre, 3=Infantil
$categoria_map = [
    'mujer' => 1,
    'hombre' => 2,
    'infantil' => 3
];
$categoria_id = $categoria_map[$genero] ?? 1;

$query = "INSERT INTO productos (
            categoria_id, nombre, descripcion, precio, en_promocion, descuento_porcentaje,
            stock, colores, talles, imagen, marca, material, genero, destacado, activo,
            fecha_creacion, fecha_actualizacion
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    'issdiiissssssii',
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
    $activo
);

if (mysqli_stmt_execute($stmt)) {
    $producto_id = mysqli_insert_id($conn);
    
    // Registrar en log o notificaciones
    $query_notif = "INSERT INTO notificaciones (tipo, titulo, mensaje, visible_para, fecha_creacion) 
                    VALUES ('producto_nuevo', 'Nuevo producto creado', ?, 'ambos', NOW())";
    $stmt_notif = mysqli_prepare($conn, $query_notif);
    $mensaje_notif = "Se agregó el producto: $nombre";
    mysqli_stmt_bind_param($stmt_notif, 's', $mensaje_notif);
    mysqli_stmt_execute($stmt_notif);
    
    echo json_encode([
        'success' => true,
        'message' => 'Producto creado exitosamente',
        'producto_id' => $producto_id
    ]);
} else {
    // Si falla, eliminar la imagen subida
    if ($nombre_imagen && file_exists('../img/productos/' . $nombre_imagen)) {
        unlink('../img/productos/' . $nombre_imagen);
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear el producto: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
