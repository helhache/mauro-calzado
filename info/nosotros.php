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

require_once('../includes/config.php');
$titulo_pagina = "Sobre Nosotros";

// Obtener sucursales activas
$query_sucursales = "SELECT * FROM sucursales WHERE activo = 1 ORDER BY id";
$result_sucursales = mysqli_query($conn, $query_sucursales);

require_once('../includes/header.php');
?>

<?php
$banner_modo              = 'fondo';
$banner_altura            = '350px';
$banner_overlay_titulo    = 'SOBRE NOSOTROS';
$banner_overlay_subtitulo = 'Más de 20 años llevando calidad a tus pies';
require_once('../includes/banner-carousel.php');
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

    <!-- NUESTRAS SUCURSALES - mapa único con Leaflet.js -->
    <?php
    // Coordenadas geográficas por ID de sucursal
    $coordenadas_sucursales = [
        1 => ['lat' => -28.473404, 'lng' => -65.778159],
        2 => ['lat' => -29.165307, 'lng' => -67.496999],
        3 => ['lat' => -29.412009, 'lng' => -66.856044],
        4 => ['lat' => -26.832338, 'lng' => -65.200504],
        5 => ['lat' => -24.791369, 'lng' => -65.415611],
    ];

    // Construir array de sucursales para JS
    $sucursales_js = [];
    $sucursales_lista = [];
    while ($suc = mysqli_fetch_assoc($result_sucursales)) {
        $ap_man = $suc['horario_apertura_manana'] ? date('H:i', strtotime($suc['horario_apertura_manana'])) : null;
        $ci_man = $suc['horario_cierre_manana']   ? date('H:i', strtotime($suc['horario_cierre_manana']))   : null;
        $ap_tar = $suc['horario_apertura_tarde']  ? date('H:i', strtotime($suc['horario_apertura_tarde']))  : null;
        $ci_tar = $suc['horario_cierre_tarde']    ? date('H:i', strtotime($suc['horario_cierre_tarde']))    : null;

        $horario_lv  = ($ap_man && $ci_man && $ap_tar && $ci_tar)
            ? "{$ap_man} - {$ci_man} / {$ap_tar} - {$ci_tar}" : 'Consultar';
        $horario_sab = $suc['trabaja_sabado']
            ? ($ap_man && $ci_man ? "{$ap_man} - {$ci_man}" : 'Abierto') : 'Cerrado';
        $horario_dom = $suc['trabaja_domingo'] ? 'Abierto' : 'Cerrado';

        $coords = $coordenadas_sucursales[$suc['id']] ?? null;
        $suc['horario_lv']  = $horario_lv;
        $suc['horario_sab'] = $horario_sab;
        $suc['horario_dom'] = $horario_dom;
        $suc['lat'] = $coords ? $coords['lat'] : null;
        $suc['lng'] = $coords ? $coords['lng'] : null;
        $sucursales_lista[] = $suc;

        if ($coords) {
            $sucursales_js[] = [
                'id'          => (int)$suc['id'],
                'nombre'      => $suc['nombre'],
                'direccion'   => $suc['direccion'] . ', ' . $suc['ciudad'] . ', ' . $suc['provincia'],
                'telefono'    => $suc['telefono'] ?? '',
                'horario_lv'  => $horario_lv,
                'horario_sab' => $horario_sab,
                'horario_dom' => $horario_dom,
                'lat'         => $coords['lat'],
                'lng'         => $coords['lng'],
            ];
        }
    }
    ?>

    <section id="sucursales" class="mb-5">
        <h2 class="fw-bold text-center mb-5">
            <i class="bi bi-geo-alt-fill text-danger me-2"></i>
            Nuestras Sucursales
        </h2>

        <?php if (!empty($sucursales_lista)): ?>
        <!-- Link a Leaflet.js (OpenStreetMap, sin API key) -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

        <div class="row g-0 shadow rounded overflow-hidden" style="min-height:520px;">

            <!-- Panel lateral: lista de sucursales -->
            <div class="col-lg-4 bg-white border-end" style="max-height:520px;overflow-y:auto;">
                <div class="p-3 border-bottom bg-light">
                    <h6 class="mb-0 fw-bold text-primary">
                        <i class="bi bi-list-ul me-2"></i>Seleccioná una sucursal
                    </h6>
                </div>
                <div id="lista-sucursales">
                    <?php foreach ($sucursales_lista as $idx => $suc): ?>
                    <div class="sucursal-item p-3 border-bottom cursor-pointer"
                         data-idx="<?php echo $idx; ?>"
                         style="cursor:pointer; transition:background .2s;">
                        <div class="d-flex align-items-start gap-2">
                            <span class="badge bg-danger rounded-circle mt-1" style="width:24px;height:24px;line-height:1.3;font-size:.75rem;">
                                <?php echo $idx + 1; ?>
                            </span>
                            <div>
                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($suc['nombre']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($suc['ciudad'] . ', ' . $suc['provincia']); ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Mapa -->
            <div class="col-lg-8 position-relative">
                <div id="mapa-sucursales" style="height:520px;width:100%;"></div>

                <!-- Panel de info de la sucursal seleccionada -->
                <div id="panel-sucursal" class="position-absolute bottom-0 start-0 end-0 bg-white border-top p-3" style="display:none;z-index:1000;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="fw-bold mb-1" id="info-nombre"></h6>
                            <p class="mb-1 small"><i class="bi bi-geo-alt text-danger me-1"></i><span id="info-direccion"></span></p>
                            <p class="mb-1 small" id="info-tel-wrap"><i class="bi bi-telephone text-success me-1"></i><span id="info-telefono"></span></p>
                            <p class="mb-0 small">
                                <i class="bi bi-clock text-info me-1"></i>
                                <strong>L-V:</strong> <span id="info-lv"></span> |
                                <strong>Sáb:</strong> <span id="info-sab"></span> |
                                <strong>Dom:</strong> <span id="info-dom"></span>
                            </p>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="document.getElementById('panel-sucursal').style.display='none'">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-shop fs-1 d-block mb-3"></i>
            <p>No hay sucursales disponibles en este momento.</p>
        </div>
        <?php endif; ?>

    </section>

    <?php if (!empty($sucursales_js)): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    (function() {
        const sucursales = <?php echo json_encode($sucursales_js, JSON_UNESCAPED_UNICODE); ?>;

        // Centro del mapa: promedio de coordenadas
        const latC = sucursales.reduce((s, x) => s + x.lat, 0) / sucursales.length;
        const lngC = sucursales.reduce((s, x) => s + x.lng, 0) / sucursales.length;

        const mapa = L.map('mapa-sucursales').setView([latC, lngC], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(mapa);

        // Icono personalizado rojo
        const iconoMarcador = L.divIcon({
            className: '',
            html: '<div style="background:#DC143C;color:#fff;width:30px;height:30px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;"><span style="transform:rotate(45deg);font-weight:bold;font-size:11px;line-height:1;">{N}</span></div>',
            iconSize: [30, 30],
            iconAnchor: [15, 30],
            popupAnchor: [0, -32]
        });

        const marcadores = [];

        sucursales.forEach((suc, idx) => {
            const icono = L.divIcon({
                className: '',
                html: `<div style="background:#DC143C;color:#fff;width:32px;height:32px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;"><span style="transform:rotate(45deg);font-weight:bold;font-size:12px;">${idx+1}</span></div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -34]
            });

            const marcador = L.marker([suc.lat, suc.lng], { icon: icono })
                .addTo(mapa)
                .on('click', () => mostrarInfo(suc));

            marcadores.push(marcador);
        });

        function mostrarInfo(suc) {
            document.getElementById('info-nombre').textContent    = suc.nombre;
            document.getElementById('info-direccion').textContent = suc.direccion;
            document.getElementById('info-telefono').textContent  = suc.telefono;
            document.getElementById('info-tel-wrap').style.display = suc.telefono ? '' : 'none';
            document.getElementById('info-lv').textContent  = suc.horario_lv;
            document.getElementById('info-sab').textContent = suc.horario_sab;
            document.getElementById('info-dom').textContent = suc.horario_dom;
            document.getElementById('panel-sucursal').style.display = '';
        }

        function centrarEnSucursal(idx) {
            const suc = sucursales[idx];
            mapa.setView([suc.lat, suc.lng], 15, { animate: true });
            mostrarInfo(suc);
            // Resaltar item en lista
            document.querySelectorAll('.sucursal-item').forEach(el => el.style.background = '');
            const item = document.querySelector(`.sucursal-item[data-idx="${idx}"]`);
            if (item) item.style.background = '#fff3cd';
        }

        // Click en lista de sucursales
        document.querySelectorAll('.sucursal-item').forEach(el => {
            el.addEventListener('click', () => centrarEnSucursal(parseInt(el.dataset.idx)));
            el.addEventListener('mouseenter', () => { if (el.style.background !== 'rgb(255, 243, 205)') el.style.background = '#f8f9fa'; });
            el.addEventListener('mouseleave', () => { if (el.style.background !== 'rgb(255, 243, 205)') el.style.background = ''; });
        });

        // Mostrar primera sucursal por defecto
        if (sucursales.length > 0) centrarEnSucursal(0);
    })();
    </script>
    <?php endif; ?>

</div>

<?php require_once('../includes/footer.php'); ?>
