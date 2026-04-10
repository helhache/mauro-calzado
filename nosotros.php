<?php
/**
 * NOSOTROS.PHP - PÁGINA SOBRE LA EMPRESA
 *
 * Contenido:
 * 1. Historia de la empresa
 * 2. Métodos de pago
 * 3. Preguntas frecuentes (FAQ)
 * 4. Ubicación de sucursales con Google Maps (iframes)
 *
 * Tecnologías:
 * - Google Maps Embed API (iframes) - No requiere API key
 */

require_once('includes/config.php');
$titulo_pagina = "Sobre Nosotros";

// Obtener sucursales activas
$query_sucursales = "SELECT * FROM sucursales WHERE activo = 1 ORDER BY id";
$result_sucursales = mysqli_query($conn, $query_sucursales);

require_once('includes/header.php');
?>

<?php
$banner_modo              = 'fondo';
$banner_altura            = '350px';
$banner_overlay_titulo    = 'SOBRE NOSOTROS';
$banner_overlay_subtitulo = 'Más de 20 años llevando calidad a tus pies';
require_once('includes/banner-carousel.php');
?>

<div class="container py-5">

    <!-- NUESTRA HISTORIA -->
    <section id="historia" class="mb-5">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4">
                <img src="img/historia-tienda.jpeg"
                     alt="Historia Mauro Calzado"
                     class="img-fluid rounded shadow">

            </div>
            <div class="col-lg-6 mb-4">
                <h2 class="fw-bold mb-4">
                    <i class="bi bi-clock-history text-primary me-2"></i>
                    Nuestra Historia
                </h2>
                <p class="lead text-muted">
                    Desde 1990, comprometidos con la calidad y el servicio
                </p>
                <p>
                    <strong>Mauro Calzado</strong> nació en el corazón de Jujuy con un sueño simple:
                    ofrecer calzado para toda la familia a precios justos. Lo que comenzó
                    como un pequeño local en el centro de la ciudad, hoy se ha convertido en una cadena
                    de zapateria con 15 sucursales reconocidas en todo el norte del pais.
                </p>
                <p>
                    A lo largo de estos años, hemos mantenido nuestro compromiso
                    con la excelencia, seleccionando cuidadosamente cada producto que ofrecemos.
                    Trabajamos con las mejores marcas nacionales e internacionales, garantizando
                    comodidad, estilo y durabilidad en cada compra.
                </p>
                <p>
                    Nuestro equipo está formado por una familia apasionadas por el calzado,
                    siempre listos para asesorarte y ayudarte a encontrar el producto perfecto
                    para cada ocasión.
                </p>
                <!-- pequeñas tarjetas o textos con logos para mejor visualizacion , logos de bostra-->
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Calidad Garantizada</h6>
                                <small class="text-muted">Productos seleccionados para toda la familia</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-people-fill text-primary fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Atención Personalizada</h6>
                                <small class="text-muted">Asesoramiento profesional en cada compra</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-shield-check text-info fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Garantía Total</h6>
                                <small class="text-muted">3 meses de garantía en todos nuestros productos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-award-fill text-warning fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Mejores Precios</h6>
                                <small class="text-muted">Relación calidad-precio insuperable</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MÉTODOS DE PAGO bloque solo para mostrar metodos de pagos contraste con el fondo,
         con iconos de boststrap en las "i class"
    -->
    <section id="pagos" class="py-5 bg-light rounded mb-5">
        <div class="container">
            <h2 class="fw-bold text-center mb-5">
                <i class="bi bi-credit-card text-success me-2"></i>
                Métodos de Pago
            </h2>

            <div class="row">
                <div class="col-md-3 col-sm-6 mb-4 text-center">
                    <div class="card border-0 h-100">
                        <div class="card-body">
                            <i class="bi bi-cash-coin text-success display-4 mb-3"></i>
                            <h5 class="fw-bold">Efectivo</h5>
                            <p class="text-muted small">Pago en tienda o contra entrega</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4 text-center">
                    <div class="card border-0 h-100">
                        <div class="card-body">
                            <i class="bi bi-credit-card text-primary display-4 mb-3"></i>
                            <h5 class="fw-bold">Tarjetas</h5>
                            <p class="text-muted small">Todas las Débito y crédito - Hasta 6 cuotas</p>
                            <img src="img/tarjeta.png" alt="Visa" height="20" class="me-2">
                            <img src="img/tarjeta.png" alt="Mastercard" height="20">
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4 text-center">
                    <div class="card border-0 h-100">
                        <div class="card-body">
                            <i class="bi bi-app text-info display-4 mb-3"></i>
                            <h5 class="fw-bold">Go-cuotas</h5>
                            <p class="text-muted small">Hasta 6 cuotas sin interés</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4 text-center">
                    <div class="card border-0 h-100">
                        <div class="card-body">
                            <i class="bi bi-bank text-warning display-4 mb-3"></i>
                            <h5 class="fw-bold">Transferencia</h5>
                            <p class="text-muted small">Bancaria o billeteras virtuales</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2"></i>
                <strong>¡Aprovechá!</strong> Hasta 6 cuotas con tu credito personal de TODOCREDITO
            </div>
        </div>
    </section>

    <!-- PREGUNTAS FRECUENTES, tarjatas mensajes en wsp de la empresa-->
    <section id="faq" class="mb-5">
        <h2 class="fw-bold text-center mb-5">
            <i class="bi bi-question-circle text-primary me-2"></i>
            Preguntas Frecuentes
        </h2>

        <div class="accordion" id="accordionFAQ">

            <!-- Pregunta 1: Tiempo de entrega -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                        ¿Cuál es el tiempo de entrega?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#accordionFAQ">
                    <div class="accordion-body">
                        <strong>En Catamarca capital:</strong> 24-48 horas hábiles.<br>
                        <strong>Interior de la provincia:</strong> 3-5 días hábiles.<br>
                        <strong>Trabajamos con:</strong> Adreni y La sevillanita para envíos rápidos y seguros.
                    </div>
                </div>
            </div>

            <!-- Pregunta 2: Cambios y devoluciones -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        ¿Puedo cambiar o devolver un producto?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                    <div class="accordion-body">
                        <strong>Sí, tenés 3 meses</strong> para cambios o devoluciones.<br>
                        <strong>Condiciones:</strong>
                        <ul>
                            <li>Producto sin uso</li>
                            <li>Caja y etiquetas originales</li>
                            <li>Ticket de compra</li>
                        </ul>
                        <strong>Importante:</strong> Productos en mal estado no tienen cambio, esto incluye higiene  del producto.
                    </div>
                </div>
            </div>

            <!-- Pregunta 3: Garantía -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                        ¿Qué garantía tienen los productos?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                    <div class="accordion-body">
                        Todos nuestros productos tienen de<strong>3 a 6 meses de garantía</strong> por defectos de fabricación.<br><br>
                        <strong>La garantía NO cubre:</strong>
                        <ul>
                            <li>Desgaste normal por uso</li>
                            <li>Daños por uso inadecuado</li>
                            <li>Modificaciones o arreglos realizadas por terceros</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Pregunta 4: Talles -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                        ¿Cómo elijo mi talle correcto?
                    </button>
                </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                    <div class="accordion-body">
                        <strong>En cada producto encontrarás una guía de talles.</strong><br><br>
                        <strong>Consejo:</strong> Medite el pie al final del día (es cuando está más hinchado)
                        y comparalo con nuestra tabla de medidas.<br><br>
                        Si tenés dudas, <strong>contactanos por WhatsApp</strong> y te asesoramos personalmente.
                    </div>
                </div>
            </div>

            <!-- Pregunta 5: Envíos -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                        ¿Hacen envíos al interior?
                    </button>
                </h2>
                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                    <div class="accordion-body">
                        <strong>¡Sí! Enviamos a todo el país.</strong><br><br>
                        <strong>Opciones de envío:</strong>
                        <ul>
                            <li><strong>Envío a domicilio:</strong> La sevillanita / Andreani</li>
                            <li><strong>Retiro en sucursal:</strong> Sin cargo en nuestras tiendas</li>
                        </ul>
                        El costo del envío se calcula según destino y peso del paquete, consulte montos minimos para envio.
                    </div>
                </div>
            </div>

            <!-- Pregunta 6: Stock -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                        ¿Cómo sé si hay stock de un producto?
                    </button>
                </h2>
                <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                    <div class="accordion-body">
                        El stock se actualiza en <strong>tiempo real</strong> en nuestra web.<br><br>
                        Si un talle no está disponible para comprar online, podés:
                        <ul>
                            <li>Consultarnos por WhatsApp</li>
                            <li>Dejarnos un mensaje por la paguina y te contestamos en 24hs</li>
                            <li>Llamar a nuestras sucursales</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- NUESTRAS SUCURSALES - datos dinámicos desde BD -->
    <?php
    // Mapas embed de Google Maps por ID de sucursal (URLs reales de cada local)
    $mapas_embed = [
        1 => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d438.40049521373885!2d-65.77815919999999!3d-28.473403599999997!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x942428c6a855b2f5%3A0xd0e7a134b5822a0c!2sMauro%20Calzados!5e0!3m2!1ses-419!2sar!4v1761622442185!5m2!1ses-419!2sar',
        2 => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d870.994614962631!2d-67.49699973046992!3d-29.165306998461116!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x969d65730e3b61d5%3A0xb9c36287407c6221!2sMauro%20Calzados!5e0!3m2!1ses-419!2sar!4v1761622730216!5m2!1ses-419!2sar',
        3 => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1737.787119140447!2d-66.85604407440334!3d-29.41200872261204!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9427da4ace6c74a5%3A0x3bb5f8294b1bcd36!2sMauro%20Calzados!5e0!3m2!1ses-419!2sar!4v1761622815739!5m2!1ses-419!2sar',
        4 => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d748.4481287183804!2d-65.20050450414735!3d-26.832338186508952!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94225dd35a1af20d%3A0xefd75fe7fde1dd56!2sMauro%20Calzados!5e0!3m2!1ses-419!2sar!4v1761622877162!5m2!1ses-419!2sar',
        5 => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4307.4500185325005!2d-65.41561149831072!3d-24.791368819458793!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x941bc3a4e86ac617%3A0x1dd02652ab80eed!2sMauro%20Calzados!5e0!3m2!1ses-419!2sar!4v1761622933527!5m2!1ses-419!2sar',
    ];
    ?>
    <section id="sucursales" class="mb-5">
        <h2 class="fw-bold text-center mb-5">
            <i class="bi bi-geo-alt-fill text-danger me-2"></i>
            Nuestras Sucursales
        </h2>

        <?php if (mysqli_num_rows($result_sucursales) > 0): ?>
        <?php while ($suc = mysqli_fetch_assoc($result_sucursales)):
            // Formatear horarios desde los campos TIME de la BD
            $ap_man = $suc['horario_apertura_manana'] ? date('H:i', strtotime($suc['horario_apertura_manana'])) : null;
            $ci_man = $suc['horario_cierre_manana']   ? date('H:i', strtotime($suc['horario_cierre_manana']))   : null;
            $ap_tar = $suc['horario_apertura_tarde']  ? date('H:i', strtotime($suc['horario_apertura_tarde']))  : null;
            $ci_tar = $suc['horario_cierre_tarde']    ? date('H:i', strtotime($suc['horario_cierre_tarde']))    : null;

            $horario_lv  = ($ap_man && $ci_man && $ap_tar && $ci_tar)
                ? "{$ap_man} - {$ci_man} / {$ap_tar} - {$ci_tar}"
                : 'Consultar';
            $horario_sab = $suc['trabaja_sabado']
                ? ($ap_man && $ci_man ? "{$ap_man} - {$ci_man}" : 'Abierto')
                : 'Cerrado';
            $horario_dom = $suc['trabaja_domingo'] ? 'Abierto' : 'Cerrado';

            $mapa_url = $mapas_embed[$suc['id']] ?? null;
        ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-4 mb-3">
                        <h4 class="fw-bold text-primary">
                            <i class="bi bi-shop me-2"></i>
                            <?php echo htmlspecialchars($suc['nombre']); ?>
                        </h4>
                        <p class="mb-2">
                            <i class="bi bi-geo-alt text-danger me-2"></i>
                            <strong>Dirección:</strong><br>
                            <span class="ms-4">
                                <?php echo htmlspecialchars($suc['direccion'] . ', ' . $suc['ciudad'] . ', ' . $suc['provincia']); ?>
                            </span>
                        </p>
                        <?php if (!empty($suc['telefono'])): ?>
                        <p class="mb-2">
                            <i class="bi bi-telephone text-success me-2"></i>
                            <strong>Teléfono:</strong><br>
                            <span class="ms-4"><?php echo htmlspecialchars($suc['telefono']); ?></span>
                        </p>
                        <?php endif; ?>
                        <p class="mb-1">
                            <i class="bi bi-clock text-info me-2"></i>
                            <strong>Horarios:</strong>
                        </p>
                        <small class="ms-4 d-block"><strong>Lun - Vie:</strong> <?php echo htmlspecialchars($horario_lv); ?></small>
                        <small class="ms-4 d-block"><strong>Sábados:</strong> <?php echo htmlspecialchars($horario_sab); ?></small>
                        <small class="ms-4 d-block"><strong>Domingos:</strong> <?php echo htmlspecialchars($horario_dom); ?></small>
                    </div>

                    <div class="col-lg-8">
                        <?php if ($mapa_url): ?>
                        <div class="ratio ratio-21x9 rounded overflow-hidden shadow-sm">
                            <iframe
                                src="<?php echo $mapa_url; ?>"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                        <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center bg-light rounded h-100" style="min-height:200px;">
                            <div class="text-center text-muted">
                                <i class="bi bi-map fs-1 d-block mb-2"></i>
                                <p class="mb-0">Mapa no disponible</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        <?php else: ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-shop fs-1 d-block mb-3"></i>
            <p>No hay sucursales disponibles en este momento.</p>
        </div>
        <?php endif; ?>

    </section>

</div>

<?php require_once('includes/footer.php'); ?>
