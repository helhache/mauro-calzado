<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente.php');
header('Content-Type: application/json');

$sucursal_id = obtenerSucursalGerente();
$turno_id    = (int)($_POST['turno_id'] ?? 0);

// Campos globales
$tipo_venta   = limpiarDato($_POST['tipo_venta']  ?? 'mostrador');
$metodo_pago  = limpiarDato($_POST['metodo_pago'] ?? '');
$notas        = !empty($_POST['notas']) ? limpiarDato($_POST['notas']) : null;
$numero_cupon = !empty($_POST['numero_cupon']) ? limpiarDato($_POST['numero_cupon']) : null;
$transf_nombre = !empty($_POST['transferencia_nombre'])  ? limpiarDato($_POST['transferencia_nombre'])  : null;
$transf_apell  = !empty($_POST['transferencia_apellido']) ? limpiarDato($_POST['transferencia_apellido']) : null;
$transferencia_cliente = ($transf_nombre || $transf_apell)
    ? trim(($transf_nombre ?? '') . ' ' . ($transf_apell ?? '')) : null;
$cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;

$metodos_validos = ['efectivo', 'tarjeta', 'transferencia', 'go_cuotas', 'credito'];

if (!$turno_id) {
    echo json_encode(['success' => false, 'message' => 'No hay turno activo']);
    exit;
}
if (!in_array($metodo_pago, $metodos_validos)) {
    echo json_encode(['success' => false, 'message' => 'Método de pago inválido']);
    exit;
}

// Verificar turno pertenece a esta sucursal
$stmt = mysqli_prepare($conn, "SELECT id FROM turnos_caja WHERE id=? AND sucursal_id=? AND estado='abierto'");
mysqli_stmt_bind_param($stmt, "ii", $turno_id, $sucursal_id);
mysqli_stmt_execute($stmt);
if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    echo json_encode(['success' => false, 'message' => 'Turno no válido']);
    exit;
}

// Leer ítems
$items = $_POST['items'] ?? [];
if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'No hay ítems en la venta']);
    exit;
}

$es_primera_cuota = ($metodo_pago === 'credito') ? 1 : 0;
$total_venta = 0;
$total_pares = 0;
$errores     = [];

// Preparar INSERT con columnas nuevas; fallback si no existen
$sql_ins = "INSERT INTO ventas_diarias
    (turno_id, sucursal_id, producto_id, producto_nombre, talle, color, cantidad,
     precio_unitario, subtotal, metodo_pago, tipo_venta, es_primera_cuota, notas,
     numero_cupon, transferencia_cliente, cliente_id)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
$stmt_ins = mysqli_prepare($conn, $sql_ins);
$usa_nuevas = ($stmt_ins !== false);

if (!$usa_nuevas) {
    $sql_ins = "INSERT INTO ventas_diarias
        (turno_id, sucursal_id, producto_id, producto_nombre, talle, color, cantidad,
         precio_unitario, subtotal, metodo_pago, tipo_venta, es_primera_cuota, notas)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt_ins = mysqli_prepare($conn, $sql_ins);
    if (!$stmt_ins) {
        echo json_encode(['success' => false, 'message' => 'Error preparando INSERT: ' . mysqli_error($conn)]);
        exit;
    }
}

foreach ($items as $item) {
    $producto_id     = !empty($item['producto_id']) ? (int)$item['producto_id'] : null;
    $producto_nombre = limpiarDato($item['producto_nombre'] ?? '');
    $talle           = !empty($item['talle'])  ? limpiarDato($item['talle'])  : null;
    $color           = !empty($item['color'])  ? limpiarDato($item['color'])  : null;
    $cantidad        = max(1, (int)($item['cantidad'] ?? 1));
    $precio_unitario = floatval($item['precio_unitario'] ?? 0);
    $subtotal        = $cantidad * $precio_unitario;

    if (!$producto_nombre || $precio_unitario <= 0) continue;

    // Verificar stock si hay producto_id
    if ($producto_id) {
        $stock_disponible = null;

        // 1. Stock exacto por color+talle
        if ($talle && $color) {
            try {
                $s = mysqli_prepare($conn, "SELECT cantidad FROM stock_sucursal_detalle WHERE producto_id=? AND sucursal_id=? AND talle=? AND color=?");
                if ($s) {
                    mysqli_stmt_bind_param($s, "iiss", $producto_id, $sucursal_id, $talle, $color);
                    mysqli_stmt_execute($s);
                    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
                    if ($r !== false && $r !== null) {
                        $stock_disponible = (int)$r['cantidad'];
                    }
                }
            } catch (Exception $e) { /* tabla no existe, ignorar */ }
        }

        // 2. Fallback: stock total de la sucursal
        if ($stock_disponible === null) {
            $s = mysqli_prepare($conn, "SELECT cantidad FROM stock_sucursal WHERE producto_id=? AND sucursal_id=?");
            if ($s) {
                mysqli_stmt_bind_param($s, "ii", $producto_id, $sucursal_id);
                mysqli_stmt_execute($s);
                $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
                $stock_disponible = $r ? (int)$r['cantidad'] : 0;
            } else {
                $stock_disponible = 0;
            }
        }

        if ($stock_disponible < $cantidad) {
            $errores[] = "Stock insuficiente para \"$producto_nombre\""
                . ($talle ? " T:$talle" : '') . ($color ? " C:$color" : '')
                . ". Disponible: $stock_disponible";
            continue;
        }
    }

    // Insertar venta
    if ($usa_nuevas) {
        mysqli_stmt_bind_param($stmt_ins, "iiisssiddssisssi",
            $turno_id, $sucursal_id, $producto_id, $producto_nombre, $talle, $color, $cantidad,
            $precio_unitario, $subtotal, $metodo_pago, $tipo_venta, $es_primera_cuota, $notas,
            $numero_cupon, $transferencia_cliente, $cliente_id
        );
    } else {
        mysqli_stmt_bind_param($stmt_ins, "iiisssiddssis",
            $turno_id, $sucursal_id, $producto_id, $producto_nombre, $talle, $color, $cantidad,
            $precio_unitario, $subtotal, $metodo_pago, $tipo_venta, $es_primera_cuota, $notas
        );
    }
    mysqli_stmt_execute($stmt_ins);

    // Descontar stock
    if ($producto_id) {
        // stock_sucursal_detalle (exacto)
        if ($talle && $color) {
            try {
                $sd = mysqli_prepare($conn, "UPDATE stock_sucursal_detalle SET cantidad=GREATEST(0,cantidad-?) WHERE producto_id=? AND sucursal_id=? AND talle=? AND color=?");
                if ($sd) {
                    mysqli_stmt_bind_param($sd, "iiiss", $cantidad, $producto_id, $sucursal_id, $talle, $color);
                    mysqli_stmt_execute($sd);
                }
            } catch (Exception $e) {}
        }
        // stock_sucursal (total)
        $ss = mysqli_prepare($conn, "UPDATE stock_sucursal SET cantidad=GREATEST(0,cantidad-?) WHERE producto_id=? AND sucursal_id=?");
        if ($ss) {
            mysqli_stmt_bind_param($ss, "iii", $cantidad, $producto_id, $sucursal_id);
            mysqli_stmt_execute($ss);
        }
        // stock global del producto
        $sp = mysqli_prepare($conn, "UPDATE productos SET stock=(SELECT COALESCE(SUM(cantidad),0) FROM stock_sucursal WHERE producto_id=?) WHERE id=?");
        if ($sp) {
            mysqli_stmt_bind_param($sp, "ii", $producto_id, $producto_id);
            mysqli_stmt_execute($sp);
        }
    }

    $total_venta += $subtotal;
    $total_pares += $cantidad;
}

// Si todos los ítems fallaron
if ($total_venta == 0 && !empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode('; ', $errores)]);
    exit;
}

// Actualizar turnos_caja
$campos_map = [
    'efectivo'      => 'efectivo_ventas',
    'tarjeta'       => 'tarjeta_ventas',
    'transferencia' => 'transferencia_ventas',
    'go_cuotas'     => 'go_cuotas_ventas',
    'credito'       => 'credito_ventas',
];
$campo_turno = $campos_map[$metodo_pago] ?? null;
if ($campo_turno && $total_venta > 0) {
    $sql_tc = "UPDATE turnos_caja SET
               {$campo_turno} = {$campo_turno} + ?,
               venta_total_dia = venta_total_dia + ?,
               pares_vendidos = pares_vendidos + ?
               WHERE id = ?";
    $stmt_tc = mysqli_prepare($conn, $sql_tc);
    mysqli_stmt_bind_param($stmt_tc, "ddii", $total_venta, $total_venta, $total_pares, $turno_id);
    mysqli_stmt_execute($stmt_tc);
}

$resp = ['success' => true];
if (!empty($errores)) {
    $resp['warning'] = 'Algunos ítems no pudieron procesarse: ' . implode('; ', $errores);
}
echo json_encode($resp);
mysqli_close($conn);
?>
