    <!-- 
        FOOTER - PIE DE PÁGINA (VERSIÓN CORREGIDA)
        
        CORRECCIONES:
        1. Copyright centrado
        2. Bootstrap JS incluido correctamente
    -->
    
    <footer class="bg-dark text-white mt-5">
        
        <!-- SECCIÓN PRINCIPAL DEL FOOTER -->
        <div class="container py-5">
            <div class="row">
                
                <!-- COLUMNA 1: INFORMACIÓN DE LA EMPRESA -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="text-uppercase mb-3 fw-bold">
                        <i class="bi bi-shop text-danger me-2"></i>Mauro Calzado
                    </h5>
                    <p class="small">
                        Zapatería con más de 20 años de experiencia. Calidad, variedad y los mejores precios para toda la familia.
                    </p>
                    
                    <div class="mt-3">
                        <p class="mb-2">
                            <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                            Av. Principal 123, Catamarca
                        </p>
                        <p class="mb-2">
                            <i class="bi bi-telephone-fill text-danger me-2"></i>
                            (0383) 123-4567
                        </p>
                        <p class="mb-2">
                            <i class="bi bi-envelope-fill text-danger me-2"></i>
                            info@maurocalzado.com
                        </p>
                    </div>
                </div>

                <!-- COLUMNA 2: ENLACES RÁPIDOS -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="text-uppercase mb-3 fw-bold">Enlaces Rápidos</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?php echo BASE_PATH; ?>catalogo/infantiles.php" class="text-white text-decoration-none">
                                <i class="bi bi-chevron-right text-danger"></i> Calzado Infantil
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_PATH; ?>catalogo/mujer.php" class="text-white text-decoration-none">
                                <i class="bi bi-chevron-right text-danger"></i> Calzado Mujer
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_PATH; ?>catalogo/hombre.php" class="text-white text-decoration-none">
                                <i class="bi bi-chevron-right text-danger"></i> Calzado Hombre
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_PATH; ?>catalogo/ofertas.php" class="text-white text-decoration-none">
                                <i class="bi bi-chevron-right text-danger"></i> Ofertas
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_PATH; ?>info/nosotros.php" class="text-white text-decoration-none">
                                <i class="bi bi-chevron-right text-danger"></i> Sobre Nosotros
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_PATH; ?>info/contactanos.php" class="text-white text-decoration-none">
                                <i class="bi bi-chevron-right text-danger"></i> Contacto
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- COLUMNA 3: INFORMACIÓN ÚTIL -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="text-uppercase mb-3 fw-bold">Información</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?php echo BASE_PATH; ?>info/nosotros.php#envios" class="text-white text-decoration-none">
                                <i class="bi bi-chevron-right text-danger"></i> Envíos y Entregas
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_PATH; ?>info/nosotros.php#pagos" class="text-white text-decoration-none">
                                <i class="bi bi-chevron-right text-danger"></i> Métodos de Pago
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_PATH; ?>info/nosotros.php#cambios" class="text-white text-decoration-none">
                                <i class="bi bi-chevron-right text-danger"></i> Cambios y Devoluciones
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_PATH; ?>info/nosotros.php#faq" class="text-white text-decoration-none">
                                <i class="bi bi-chevron-right text-danger"></i> Preguntas Frecuentes
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_PATH; ?>info/terminos.php" class="text-white text-decoration-none">
                                <i class="bi bi-chevron-right text-danger"></i> Términos y Condiciones
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- COLUMNA 4: REDES SOCIALES Y NEWSLETTER -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="text-uppercase mb-3 fw-bold">Síguenos</h5>
                    <div class="d-flex gap-3 mb-4">
                        <a href="#" class="text-white fs-4" target="_blank" aria-label="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="#" class="text-white fs-4" target="_blank" aria-label="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="#" class="text-white fs-4" target="_blank" aria-label="WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                    </div>

                    <h6 class="text-uppercase mb-2 fw-bold">Newsletter</h6>
                    <p class="small">Recibe nuestras ofertas y novedades</p>
                    <form method="POST" action="<?php echo BASE_PATH; ?>info/newsletter.php">
                        <div class="input-group mb-3">
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="Tu email" required>
                            <button class="btn btn-danger btn-sm" type="submit">
                                Suscribirse
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SECCIÓN INFERIOR: COPYRIGHT Y MÉTODOS DE PAGO -->
        <div class="border-top border-secondary">
            <div class="container py-3">
                <div class="row align-items-center">
                    
                    <!-- CORRECCIÓN: Copyright centrado -->
                    <div class="col-12 text-center mb-2">
                        <small>
                            &copy; <?php echo date('Y'); ?> Mauro Calzado. Todos los derechos reservados.
                        </small>
                    </div>
                    
                    <!-- Métodos de pago -->
                    <div class="col-12 text-center">
                        <small class="text-muted">Aceptamos:</small>
                        <i class="bi bi-credit-card fs-5 text-muted mx-2"></i>
                        <i class="bi bi-paypal fs-5 text-muted mx-2"></i>
                        <i class="bi bi-bank fs-5 text-muted mx-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- MODAL: SELECCIÓN DE COLOR Y TALLE ANTES DE AGREGAR AL CARRITO -->
    <div class="modal fade" id="modalSeleccionCarrito" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cart-plus me-2"></i>Agregar al Carrito</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded">
                        <img id="modal-carrito-imagen" src="" alt="" width="70" height="70"
                             class="rounded" style="object-fit:cover;">
                        <div>
                            <h6 class="mb-0 fw-bold" id="modal-carrito-nombre"></h6>
                            <span class="text-primary fw-semibold fs-5" id="modal-carrito-precio"></span>
                        </div>
                    </div>
                    <div id="modal-bloque-color" class="mb-4" style="display:none;">
                        <label class="form-label fw-semibold">Color: <span id="modal-color-texto" class="text-primary">Seleccioná uno</span></label>
                        <div class="d-flex gap-2 flex-wrap" id="modal-selector-colores"></div>
                    </div>
                    <div id="modal-bloque-talle" class="mb-3" style="display:none;">
                        <label class="form-label fw-semibold">Talle: <span id="modal-talle-texto" class="text-primary">Seleccioná uno</span></label>
                        <div class="d-flex gap-2 flex-wrap" id="modal-selector-talles"></div>
                    </div>
                    <div id="modal-carrito-error" class="alert alert-warning d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-confirmar-carrito">
                        <i class="bi bi-cart-check me-2"></i>Agregar al Carrito
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: LIGHTBOX DE IMAGEN -->
    <div class="modal fade image-lightbox-modal" id="imageLightbox" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    <img src="" alt="Imagen ampliada" id="lightboxImage">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Flash newsletter -->
    <?php if (!empty($_SESSION['newsletter_mensaje'])): ?>
    <div class="toast-container position-fixed bottom-0 start-50 translate-middle-x mb-4" style="z-index:9999;">
        <div class="toast show align-items-center text-bg-<?php echo htmlspecialchars($_SESSION['newsletter_tipo']); ?> border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body fw-semibold">
                    <?php echo htmlspecialchars($_SESSION['newsletter_mensaje']); ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    <?php
    unset($_SESSION['newsletter_mensaje'], $_SESSION['newsletter_tipo']);
    endif;
    ?>

    <!-- CORRECCIÓN: Bootstrap Bundle JS (incluye Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Modal personalizado MC (reemplaza alert/confirm nativos) -->
    <div class="modal fade" id="mc-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 pb-1">
                    <div class="d-flex align-items-center gap-2">
                        <span id="mc-modal-icon-wrap" class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;">
                            <i id="mc-modal-icon" class="bi fs-5"></i>
                        </span>
                        <h5 class="modal-title fw-bold mb-0" id="mc-modal-titulo">Aviso</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2 pb-3">
                    <p id="mc-modal-mensaje" class="mb-0" style="color:#444;font-size:.95rem;line-height:1.5;"></p>
                </div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-light px-4" id="mc-modal-cancelar" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn px-4 fw-semibold" id="mc-modal-ok">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
    <script src="<?php echo BASE_PATH; ?>js/modal-utils.js"></script>

    <!-- JavaScript personalizado -->
    <script src="<?php echo BASE_PATH; ?>js/main.js"></script>

    <!-- Scripts específicos de la página (se definen en $scripts_pagina antes de incluir footer) -->
    <?php if (!empty($scripts_pagina)) echo $scripts_pagina; ?>
    
    <!-- Script para Lightbox -->
    <script>
    // Lightbox para imágenes de productos
    document.addEventListener('DOMContentLoaded', function() {
        // Seleccionar todas las imágenes de productos
        const productImages = document.querySelectorAll('.product-card img');
        const lightboxModal = new bootstrap.Modal(document.getElementById('imageLightbox'));
        const lightboxImage = document.getElementById('lightboxImage');
        
        productImages.forEach(function(img) {
            // Hacer que la imagen sea clickeable
            img.style.cursor = 'pointer';
            
            img.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Obtener la URL de la imagen
                const imgSrc = this.getAttribute('src');
                const imgAlt = this.getAttribute('alt');
                
                // Establecer la imagen en el modal
                lightboxImage.setAttribute('src', imgSrc);
                lightboxImage.setAttribute('alt', imgAlt);
                
                // Mostrar el modal
                lightboxModal.show();
            });
        });
        
        // Cerrar con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                lightboxModal.hide();
            }
        });
    });
    </script>
    
</body>
</html>
