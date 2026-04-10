</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle Sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('backdrop');
    sidebar.classList.toggle('show');
    backdrop.classList.toggle('show');
}

// Cerrar sidebar al hacer click fuera (mobile)
document.getElementById('backdrop').addEventListener('click', toggleSidebar);
</script>

</body>
</html>
