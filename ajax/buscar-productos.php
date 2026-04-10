<?php
/**
 * AJAX/BUSCAR-PRODUCTOS.PHP
 * 
 * Búsqueda en tiempo real de productos
 * Responde con JSON para el buscador del header
 */

require_once('../includes/config.php');
header('Content-Type: application/json');

// Obtener término de búsqueda
$query = isset($_GET['q']) ? limpiarDato($_GET['q']) : '';

// Validar que haya al menos 3 caracteres
if (strlen($query) < 3) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Mínimo 3 caracteres para buscar'
    ]);
    exit;
}

// Buscar productos usando FULLTEXT o LIKE
// Preparar parámetro para LIKE
$query_param = "%{$query}%";

/**
 * Justificación de búsqueda:
 * - LIKE '%término%' busca en cualquier parte del texto
 * - Búsqueda en nombre, descripción y marca
 * - Limitado a 10 resultados para rapidez
 */

$stmt = mysqli_prepare($conn,
    "SELECT id, nombre, precio, imagen, categoria_id,
     CASE 
         WHEN en_promocion = 1 THEN precio - (precio * descuento_porcentaje / 100)
         ELSE precio
     END AS precio_final
     FROM productos 
     WHERE activo = 1 
     AND (nombre LIKE ? OR descripcion LIKE ? OR marca LIKE ?)
     ORDER BY ventas DESC, nombre ASC
     LIMIT 10"
);

mysqli_stmt_bind_param($stmt, "sss", $query_param, $query_param, $query_param);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$productos = [];
while ($row = mysqli_fetch_assoc($result)) {
    $productos[] = [
        'id' => $row['id'],
        'nombre' => $row['nombre'],
        'precio' => $row['precio_final'],
        'imagen' => $row['imagen']
    ];
}

// Respuesta
if (count($productos) > 0) {
    echo json_encode([
        'success' => true,
        'productos' => $productos,
        'total' => count($productos)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'mensaje' => 'No se encontraron productos',
        'productos' => []
    ]);
}
?>