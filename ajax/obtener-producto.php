<?php
/**
 * AJAX - OBTENER PRODUCTO (VERSIÓN CORREGIDA)
 * Devuelve los datos de un producto específico en formato JSON
 * Compatible con formato antiguo y nuevo de colores
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

header('Content-Type: application/json');

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// ============================================================================
// VALIDAR ID DEL PRODUCTO
// ============================================================================
if (empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de producto no válido']);
    exit;
}

$producto_id = intval($_GET['id']);

// ============================================================================
// OBTENER DATOS DEL PRODUCTO
// ============================================================================
$query = "SELECT p.*, c.nombre as categoria_nombre
          FROM productos p
          LEFT JOIN categorias c ON p.categoria_id = c.id
          WHERE p.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $producto_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    exit;
}

$producto = mysqli_fetch_assoc($result);

// ============================================================================
// CONVERTIR COLORES AL FORMATO JSON SI ES NECESARIO
// ============================================================================
$colores = $producto['colores'];
$colores_array = json_decode($colores, true);

// Si no es JSON válido, convertir formato antiguo
if (json_last_error() !== JSON_ERROR_NONE) {
    // Formato antiguo: "Negro,Blanco,Gris"
    if (!empty($colores)) {
        $colores_antiguos = explode(',', $colores);
        $colores_nuevos = [];
        
        // Calcular stock por color (dividir equitativamente)
        $cantidad_colores = count($colores_antiguos);
        $stock_por_color = $cantidad_colores > 0 ? floor($producto['stock'] / $cantidad_colores) : 0;
        
        foreach ($colores_antiguos as $color) {
            $color_limpio = trim($color);
            if (!empty($color_limpio)) {
                $colores_nuevos[$color_limpio] = $stock_por_color;
            }
        }
        
        // Convertir a JSON y actualizar en BD
        $colores_json = json_encode($colores_nuevos, JSON_UNESCAPED_UNICODE);
        
        // Actualizar el producto con el nuevo formato
        $query_update = "UPDATE productos SET colores = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $query_update);
        mysqli_stmt_bind_param($stmt_update, 'si', $colores_json, $producto_id);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        
        // Usar el nuevo formato en la respuesta
        $producto['colores'] = $colores_json;
    } else {
        // Sin colores
        $producto['colores'] = '{}';
    }
}

// Convertir valores booleanos a enteros para JavaScript
$producto['destacado'] = (int)$producto['destacado'];
$producto['activo'] = (int)$producto['activo'];
$producto['en_promocion'] = (int)$producto['en_promocion'];

// Devolver producto en formato JSON
echo json_encode($producto);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
