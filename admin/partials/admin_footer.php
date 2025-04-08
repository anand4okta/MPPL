</div> <!-- Close content-wrapper -->
    </div> <!-- Close container-fluid -->
    
    <footer class="bg-white shadow-sm mt-3 py-2">
        <div class="container-fluid">
            <div class="text-center text-muted">
                &copy; <?= date('Y') ?> PPDB MAN 1 MUSI RAWAS
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const toggler = document.querySelector('.navbar-toggler');
            const sidebar = document.querySelector('.sidebar');
            
            if (toggler && sidebar) {
                toggler.addEventListener('click', () => {
                    sidebar.classList.toggle('d-none');
                });
            }
        });
    </script>
</body>
</html>