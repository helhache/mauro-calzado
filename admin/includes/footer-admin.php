</main><!-- Cierra admin-content del header -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal personalizado MC -->
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
<script src="../js/modal-utils.js"></script>

<script>
// Toggle sidebar en móvil
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('backdrop');
    
    if (window.innerWidth <= 1024) {
        sidebar.classList.toggle('show');
        backdrop.classList.toggle('show');
    }
}

// Cerrar sidebar al hacer clic fuera
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>

</body>
</html>
