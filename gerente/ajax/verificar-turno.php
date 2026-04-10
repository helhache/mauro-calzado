<?php
/**
 * VERIFICAR ESTADO DEL TURNO ACTUAL
 *
 * Verifica si hay un turno abierto para el gerente actual
 */

require_once '../../includes/config.php';
require_once '../../includes/verificar-gerente.php';

header('Content-Type: application/json');

try {
    $gerente_id  = $_SESSION['usuario_id'];
    $sucursal_id = obtenerSucursalGerente();

    // Buscar turno abierto
    $query = "SELECT
                t.id,
                t.turno,
                t.monto_inicial,
                t.fecha_apertura,
                t.gerente_id,
                t.sucursal_id,
                COALESCE(SUM(CASE WHEN m.tipo = 'venta' THEN m.monto ELSE 0 END), 0) as total_ventas,
                COALESCE(SUM(CASE WHEN m.tipo = 'gasto' THEN m.monto ELSE 0 END), 0) as total_gastos,
                COALESCE(SUM(CASE WHEN m.tipo = 'cobro_cuota' THEN m.monto ELSE 0 END), 0) as total_cobros
              FROM turnos_caja t
              LEFT JOIN movimientos_caja m ON m.turno_id = t.id
              WHERE t.gerente_id = ?
                AND t.sucursal_id = ?
                AND t.estado = 'abierto'
              GROUP BY t.id
              ORDER BY t.fecha_apertura DESC
              LIMIT 1";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $gerente_id, $sucursal_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($turno = mysqli_fetch_assoc($result)) {
        // Hay turno abierto
        $turno['turno_abierto'] = true;
        $turno['monto_actual'] = $turno['monto_inicial'] + $turno['total_ventas'] + $turno['total_cobros'] - $turno['total_gastos'];

        echo json_encode([
            'success' => true,
            'turno_abierto' => true,
            'turno' => $turno
        ]);
    } else {
        // No hay turno abierto
        echo json_encode([
            'success' => true,
            'turno_abierto' => false,
            'turno' => null
        ]);
    }

    mysqli_stmt_close($stmt);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al verificar turno: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
