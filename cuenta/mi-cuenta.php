<?php
/**
 * MI-CUENTA.PHP - PERFIL DEL CLIENTE
 * 
 * Funcionalidades:
 * - Ver datos personales
 * - Editar información de perfil
 * - Completar dirección para envíos
 * - Cambiar contraseña
 * - Ver resumen de actividad
 * - Acceso a pedidos y mensajes
 */

require_once('../includes/config.php');
require_once('../includes/verificar-cliente.php');

$titulo_pagina = "Mi Cuenta";
$usuario_id = $_SESSION['usuario_id'];

// Obtener datos del usuario
$stmt = mysqli_prepare($conn, "SELECT * FROM usuarios WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $usuario_id);
mysqli_stmt_execute($stmt);
$usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Obtener estadísticas del usuario
$stmt_stats = mysqli_prepare($conn, "
    SELECT 
        COUNT(*) as total_pedidos,
        SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as pedidos_completados,
        SUM(CASE WHEN estado IN ('pendiente', 'confirmado', 'preparando', 'enviado') THEN 1 ELSE 0 END) as pedidos_activos,
        COALESCE(SUM(CASE WHEN estado = 'entregado' THEN total ELSE 0 END), 0) as total_gastado
    FROM pedidos
    WHERE usuario_id = ?
");
mysqli_stmt_bind_param($stmt_stats, "i", $usuario_id);
mysqli_stmt_execute($stmt_stats);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_stats));
mysqli_stmt_close($stmt_stats);

// Obtener mensajes pendientes
$stmt_mensajes = mysqli_prepare($conn, "
    SELECT COUNT(*) as mensajes_pendientes
    FROM mensajes_internos
    WHERE usuario_id = ? AND estado = 'respondido' AND leido_cliente = 0
");
mysqli_stmt_bind_param($stmt_mensajes, "i", $usuario_id);
mysqli_stmt_execute($stmt_mensajes);
$result_mensajes = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_mensajes));
$mensajes_pendientes = $result_mensajes['mensajes_pendientes'] ?? 0;
mysqli_stmt_close($stmt_mensajes);

$errores = [];
$success = '';

// PROCESAR ACTUALIZACIÓN DE DATOS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_datos'])) {
    
    $nombre = limpiarDato($_POST['nombre'] ?? '');
    $apellido = limpiarDato($_POST['apellido'] ?? '');
    $telefono = limpiarDato($_POST['telefono'] ?? '');
    $direccion = limpiarDato($_POST['direccion'] ?? '');
    $ciudad = limpiarDato($_POST['ciudad'] ?? '');
    $provincia = limpiarDato($_POST['provincia'] ?? '');
    $codigo_postal = limpiarDato($_POST['codigo_postal'] ?? '');
    
    // Validaciones
    if (empty($nombre) || strlen($nombre) < 2) {
        $errores['nombre'] = 'El nombre es obligatorio';
    }
    
    if (empty($apellido) || strlen($apellido) < 2) {
        $errores['apellido'] = 'El apellido es obligatorio';
    }
    
    if (empty($errores)) {
        $stmt_update = mysqli_prepare($conn,
            "UPDATE usuarios 
             SET nombre = ?, apellido = ?, telefono = ?, direccion = ?, ciudad = ?, provincia = ?, codigo_postal = ?
             WHERE id = ?"
        );
        
        mysqli_stmt_bind_param($stmt_update, "sssssssi",
            $nombre, $apellido, $telefono, $direccion, $ciudad, $provincia, $codigo_postal, $usuario_id
        );
        
        if (mysqli_stmt_execute($stmt_update)) {
            $success = '¡Datos actualizados correctamente!';
            
            // Recargar datos del usuario
            $usuario['nombre'] = $nombre;
            $usuario['apellido'] = $apellido;
            $usuario['telefono'] = $telefono;
            $usuario['direccion'] = $direccion;
            $usuario['ciudad'] = $ciudad;
            $usuario['provincia'] = $provincia;
            $usuario['codigo_postal'] = $codigo_postal;
            
            // Actualizar sesión
            $_SESSION['nombre'] = $nombre;
        } else {
            $errores['general'] = 'Error al actualizar los datos';
        }
        
        mysqli_stmt_close($stmt_update);
    }
}

// PROCESAR CAMBIO DE CONTRASEÑA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_password'])) {
    
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';
    
    // Validaciones
    if (empty($password_actual)) {
        $errores['password_actual'] = 'Ingresa tu contraseña actual';
    } elseif (!password_verify($password_actual, $usuario['password'])) {
        $errores['password_actual'] = 'La contraseña actual es incorrecta';
    }
    
    if (empty($password_nueva)) {
        $errores['password_nueva'] = 'Ingresa una nueva contraseña';
    } elseif (strlen($password_nueva) < 6) {
        $errores['password_nueva'] = 'La contraseña debe tener al menos 6 caracteres';
    }
    
    if ($password_nueva !== $password_confirmar) {
        $errores['password_confirmar'] = 'Las contraseñas no coinciden';
    }
    
    if (empty($errores)) {
        $password_hash = password_hash($password_nueva, PASSWORD_BCRYPT);
        
        $stmt_password = mysqli_prepare($conn, "UPDATE usuarios SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_password, "si", $password_hash, $usuario_id);
        
        if (mysqli_stmt_execute($stmt_password)) {
            $success = '¡Contraseña actualizada correctamente!';
        } else {
            $errores['general'] = 'Error al cambiar la contraseña';
        }
        
        mysqli_stmt_close($stmt_password);
    }
}

require_once('../includes/header.php');
?>

<div class="container py-5">
    
    <!-- Encabezado -->
    <div class="mb-4">
        <h1 class="fw-bold mb-1">
            <i class="bi bi-person-circle me-2"></i>Mi Cuenta
        </h1>
        <p class="text-muted mb-0">Administra tu información personal y tus pedidos</p>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <h6 class="fw-bold mb-2">Por favor corrige los siguientes errores:</h6>
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        
        <!-- COLUMNA IZQUIERDA: INFORMACIÓN Y ESTADÍSTICAS -->
        <div class="col-lg-4 mb-4">
            
            <!-- Tarjeta de perfil -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-person-circle text-primary" style="font-size: 5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-1">
                        <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                    </h4>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($usuario['email']); ?></p>
                    <span class="badge bg-success">Cliente</span>
                    
                    <hr class="my-3">
                    
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h4 class="fw-bold text-primary mb-0"><?php echo $stats['total_pedidos']; ?></h4>
                            <small class="text-muted">Pedidos Totales</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="fw-bold text-success mb-0"><?php echo $stats['pedidos_completados']; ?></h4>
                            <small class="text-muted">Completados</small>
                        </div>
                        <div class="col-12">
                            <h5 class="fw-bold text-info mb-0">
                                $<?php echo number_format($stats['total_gastado'], 0, ',', '.'); ?>
                            </h5>
                            <small class="text-muted">Total Gastado</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Accesos rápidos -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold">Accesos Rápidos</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="mis-pedidos.php" class="list-group-item list-group-item-action">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-bag-check text-primary fs-4 me-3"></i>
                            <div class="flex-grow-1">
                                <strong>Mis Pedidos</strong>
                                <?php if ($stats['pedidos_activos'] > 0): ?>
                                    <span class="badge bg-warning text-dark float-end">
                                        <?php echo $stats['pedidos_activos']; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    
                    <a href="mensajes-internos.php" class="list-group-item list-group-item-action">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-chat-dots text-success fs-4 me-3"></i>
                            <div class="flex-grow-1">
                                <strong>Mensajes</strong>
                                <?php if ($mensajes_pendientes > 0): ?>
                                    <span class="badge bg-danger float-end">
                                        <?php echo $mensajes_pendientes; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    
                    <a href="favoritos.php" class="list-group-item list-group-item-action">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-heart text-danger fs-4 me-3"></i>
                            <div class="flex-grow-1">
                                <strong>Favoritos</strong>
                            </div>
                        </div>
                    </a>
                    
                    <a href="<?php echo BASE_PATH; ?>index.php" class="list-group-item list-group-item-action">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-shop text-info fs-4 me-3"></i>
                            <div class="flex-grow-1">
                                <strong>Seguir Comprando</strong>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- Última compra -->
            <?php
            $stmt_ultima = mysqli_prepare($conn, "
                SELECT * FROM pedidos 
                WHERE usuario_id = ? AND estado = 'entregado'
                ORDER BY fecha_entrega DESC 
                LIMIT 1
            ");
            mysqli_stmt_bind_param($stmt_ultima, "i", $usuario_id);
            mysqli_stmt_execute($stmt_ultima);
            $ultima_compra = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_ultima));
            mysqli_stmt_close($stmt_ultima);
            
            if ($ultima_compra):
            ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-clock-history me-2"></i>Última Compra
                        </h6>
                        <p class="mb-1">
                            <strong>Pedido:</strong> #<?php echo $ultima_compra['numero_pedido']; ?>
                        </p>
                        <p class="mb-1">
                            <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($ultima_compra['fecha_entrega'])); ?>
                        </p>
                        <p class="mb-3">
                            <strong>Total:</strong> $<?php echo number_format($ultima_compra['total'], 0, ',', '.'); ?>
                        </p>
                        <a href="mis-pedidos.php" class="btn btn-sm btn-outline-primary w-100">
                            Ver Historial Completo
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
        
        <!-- COLUMNA DERECHA: FORMULARIOS -->
        <div class="col-lg-8">
            
            <!-- DATOS PERSONALES -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person me-2"></i>Datos Personales
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label fw-semibold">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre" 
                                       name="nombre"
                                       value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="apellido" class="form-label fw-semibold">
                                    Apellido <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="apellido" 
                                       name="apellido"
                                       value="<?php echo htmlspecialchars($usuario['apellido']); ?>"
                                       required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-semibold">
                                    Email
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email"
                                       value="<?php echo htmlspecialchars($usuario['email']); ?>"
                                       disabled>
                                <small class="text-muted">El email no se puede modificar</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="dni" class="form-label fw-semibold">
                                    DNI
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="dni"
                                       value="<?php echo htmlspecialchars($usuario['dni']); ?>"
                                       disabled>
                                <small class="text-muted">El DNI no se puede modificar</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telefono" class="form-label fw-semibold">
                                Teléfono
                            </label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="telefono" 
                                   name="telefono"
                                   placeholder="3834567890"
                                   value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="fw-bold mb-3">Dirección para Envíos</h6>
                        
                        <div class="mb-3">
                            <label for="direccion" class="form-label fw-semibold">
                                Dirección Completa
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="direccion" 
                                   name="direccion"
                                   placeholder="Calle, número, piso, depto"
                                   value="<?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?>">
                            <small class="text-muted">Esta será tu dirección predeterminada para envíos</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ciudad" class="form-label fw-semibold">
                                    Ciudad
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="ciudad" 
                                       name="ciudad"
                                       value="<?php echo htmlspecialchars($usuario['ciudad'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="provincia" class="form-label fw-semibold">
                                    Provincia
                                </label>
                                <select class="form-select" id="provincia" name="provincia">
                                    <option value="">Selecciona...</option>
                                    <?php
                                    $provincias = ['Buenos Aires', 'Catamarca', 'Chaco', 'Chubut', 'Córdoba', 
                                                  'Corrientes', 'Entre Ríos', 'Formosa', 'Jujuy', 'La Pampa', 
                                                  'La Rioja', 'Mendoza', 'Misiones', 'Neuquén', 'Río Negro', 
                                                  'Salta', 'San Juan', 'San Luis', 'Santa Cruz', 'Santa Fe', 
                                                  'Santiago del Estero', 'Tierra del Fuego', 'Tucumán'];
                                    foreach ($provincias as $prov):
                                    ?>
                                        <option value="<?php echo $prov; ?>" 
                                                <?php echo ($prov == ($usuario['provincia'] ?? '')) ? 'selected' : ''; ?>>
                                            <?php echo $prov; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="codigo_postal" class="form-label fw-semibold">
                                Código Postal
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="codigo_postal" 
                                   name="codigo_postal"
                                   value="<?php echo htmlspecialchars($usuario['codigo_postal'] ?? ''); ?>">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="actualizar_datos" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- CAMBIAR CONTRASEÑA -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-key me-2"></i>Cambiar Contraseña
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="password_actual" class="form-label fw-semibold">
                                Contraseña Actual <span class="text-danger">*</span>
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_actual" 
                                   name="password_actual"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_nueva" class="form-label fw-semibold">
                                Nueva Contraseña <span class="text-danger">*</span>
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_nueva" 
                                   name="password_nueva"
                                   minlength="6"
                                   required>
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password_confirmar" class="form-label fw-semibold">
                                Confirmar Nueva Contraseña <span class="text-danger">*</span>
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmar" 
                                   name="password_confirmar"
                                   minlength="6"
                                   required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="cambiar_password" class="btn btn-warning btn-lg">
                                <i class="bi bi-shield-check me-2"></i>Cambiar Contraseña
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>

<script>
// Validar que las contraseñas coincidan
document.getElementById('password_confirmar').addEventListener('input', function() {
    const nueva = document.getElementById('password_nueva').value;
    const confirmar = this.value;
    
    if (confirmar && nueva !== confirmar) {
        this.setCustomValidity('Las contraseñas no coinciden');
    } else {
        this.setCustomValidity('');
    }
});
</script>
