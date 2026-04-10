<?php
/**
 * AJAX: Buscar productos para autocompletar en registro de venta
 */

require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente.php');

header('Content-Type: application/json');

$sucursal_id = obtenerSucursalGerente();
$busqueda = isset($_GET['q']) ? limpiarDato($_GET['q']) : '';

if (strlen($busqueda) < 2) {
    echo json_encode([]);
    exit;
}

// Buscar productos que tengan stock en esta sucursal
$sql = "SELECT 
            p.id,
            p.nombre,
            p.precio,
            p.talles,
            p.colores,
            p.imagen,
            COALESCE(ss.cantidad, 0) as stock_sucursal
        FROM productos p
        LEFT JOIN stock_sucursal ss ON p.id = ss.producto_id AND ss.sucursal_id = ?
        WHERE p.activo = 1 
        AND p.nombre LIKE ?
        ORDER BY p.nombre ASC
        LIMIT 10";

$stmt = mysqli_prepare($conn, $sql);
$busqueda_like = "%$busqueda%";
mysqli_stmt_bind_param($stmt, "is", $sucursal_id, $busqueda_like);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$productos = [];
while ($row = mysqli_fetch_assoc($resultado)) {
    // Decodificar colores JSON si existen
    $colores_disponibles = [];
    if ($row['colores']) {
        $colores_json = json_decode($row['colores'], true);
        if ($colores_json) {
            $colores_disponibles = array_keys($colores_json);
        }
    }
    
    $productos[] = [
        'id' => $row['id'],
        'nombre' => $row['nombre'],
        'precio' => $row['precio'],
        'talles' => $row['talles'],
        'colores' => $colores_disponibles,
        'stock' => $row['stock_sucursal'],
        'imagen' => $row['imagen']
    ];
}

echo json_encode($productos);
mysqli_close($conn);
?>
