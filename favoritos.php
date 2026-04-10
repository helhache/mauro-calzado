<?php
/**
 * FAVORITOS.PHP - LISTA DE FAVORITOS (VERSIÓN CON TARJETA UNIFICADA)
 */

require_once('includes/config.php');
$titulo_pagina = "Mis Favoritos";

// Verificar que el usuario esté logueado
if (!estaLogueado()) {
    redirigir('login.php?redirect=favoritos.php');
}

$usuario_id = $_SESSION['usuario_id'];

// Procesar eliminación de favorito
if (isset($_POST['eliminar_favorito'])) {
    $producto_id = intval($_POST['producto_id']);
    
    $stmt = mysqli_prepare($conn, "DELETE FROM favoritos WHERE usuario_id = ? AND producto_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $usuario_id, $producto_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Obtener favoritos del usuario (CON STOCK)
$query = "SELECT p.*, f.fecha_agregado,
          CASE 
              WHEN p.en_promocion = 1 THEN p.precio - (p.precio * p.descuento_porcentaje / 100)
              ELSE p.precio
          END AS precio_final
          FROM favoritos f
          INNER JOIN productos p ON f.producto_id = p.id
          WHERE f.usuario_id = ? AND p.activo = 1 AND p.stock > 0
          ORDER BY f.fecha_agregado DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $usuario_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

require_once('includes/header.php');
?>

<div class="container py-5">
    
    <!-- Título -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold">
            <i class="bi bi-heart-fill text-danger me-2"></i>Mis Favoritos
        </h1>
        <span class="badge bg-danger fs-6">
            <?php echo mysqli_num_rows($result); ?> producto<?php echo mysqli_num_rows($result) != 1 ? 's' : ''; ?>
        </span>
    </div>
    
    <?php if (mysqli_num_rows($result) == 0): ?>
        <!-- FAVORITOS VACÍOS -->
        <div class="text-center py-5">
            <i class="bi bi-heart display-1 text-muted"></i>
            <h3 class="mt-4">No tienes productos en favoritos</h3>
            <p class="text-muted">Empieza a guardar tus productos favoritos para comprarlos más tarde</p>
            <a href="index.php" class="btn btn-primary btn-lg mt-3">
                <i class="bi bi-shop me-2"></i>Explorar Productos
            </a>
        </div>
        
    <?php else: ?>
        <!-- GRID DE FAVORITOS -->
        <div class="row">
            <?php 
            // Configurar contexto para la tarjeta
            $contexto = 'favoritos';
            
            while ($producto = mysqli_fetch_assoc($result)): 
            ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <?php include('includes/componentes/tarjeta-producto.php'); ?>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Botón continuar comprando -->
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-arrow-left me-2"></i>Continuar Comprando
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
mysqli_stmt_close($stmt);
require_once('includes/footer.php');
?>
