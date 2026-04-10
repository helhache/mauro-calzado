<?php
/**
 * OBTENER RESUMEN DE CAJA DEL TURNO ACTUAL
 *
 * Devuelve el resumen actualizado del turno abierto
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
                COALESCE(SUM(CASE WHEN m.tipo = 'venta' THEN m.monto ELSE 0 END), 0) as total_ventas,
                COALESCE(SUM(CASE WHEN m.tipo = 'gasto' THEN m.monto ELSE 0 END), 0) as total_gastos,
                COALESCE(SUM(CASE WHEN m.tipo = 'cobro_cuota' THEN m.monto ELSE 0 END), 0) as total_cobros,
                COUNT(CASE WHEN m.tipo = 'venta' THEN 1 END) as cantidad_ventas,
                COUNT(CASE WHEN m.tipo = 'gasto' THEN 1 END) as cantidad_gastos,
                COUNT(CASE WHEN m.tipo = 'cobro_cuota' THEN 1 END) as cantidad_cobros
              FROM turnos_caja t
              LEFT JOIN movimientos_caja m ON m.turno_id = t.id
              WHERE t.gerente_id = ?
                AND t.sucursal_id = ?
                AND t.estado = 'abierto'
              GROUP BY t.id
              LIMIT 1";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $gerente_id, $sucursal_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($turno = mysqli_fetch_assoc($result)) {
        // Calcular monto actual
        $monto_actual = $turno['monto_inicial'] +
                        $turno['total_ventas'] +
                        $turno['total_cobros'] -
                        $turno['total_gastos'];

        // Obtener últimos movimientos
        $query_movimientos = "SELECT
                                m.id,
                                m.tipo,
                                m.monto,
                                m.descripcion,
                                m.fecha_registro,
                                m.metodo_pago
                              FROM movimientos_caja m
                              WHERE m.turno_id = ?
                              ORDER BY m.fecha_registro DESC
                              LIMIT 10";

        $stmt_mov = mysqli_prepare($conn, $query_movimientos);
        mysqli_stmt_bind_param($stmt_mov, "i", $turno['id']);
        mysqli_stmt_execute($stmt_mov);
        $result_mov = mysqli_stmt_get_result($stmt_mov);

        $movimientos = [];
        while ($mov = mysqli_fetch_assoc($result_mov)) {
            $movimientos[] = $mov;
        }

        mysqli_stmt_close($stmt_mov);

        echo json_encode([
            'success' => true,
            'turno' => [
                'id' => $turno['id'],
                'turno' => $turno['turno'],
                'monto_inicial' => floatval($turno['monto_inicial']),
                'total_ventas' => floatval($turno['total_ventas']),
                'total_gastos' => floatval($turno['total_gastos']),
                'total_cobros' => floatval($turno['total_cobros']),
                'monto_actual' => $monto_actual,
                'cantidad_ventas' => intval($turno['cantidad_ventas']),
                'cantidad_gastos' => intval($turno['cantidad_gastos']),
                'cantidad_cobros' => intval($turno['cantidad_cobros']),
                'fecha_apertura' => $turno['fecha_apertura']
            ],
            'ultimos_movimientos' => $movimientos
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No hay turno abierto'
        ]);
    }

    mysqli_stmt_close($stmt);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener resumen: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
