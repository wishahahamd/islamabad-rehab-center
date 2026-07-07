<?php
// Footer layout
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/functions.php';
?>
        <!-- Main Footer -->
        <footer class="app-footer">
            <div class="float-end d-none d-sm-inline">
                Universal Skeleton App
            </div>
            <strong><?php echo sanitize($footer_text); ?></strong>
        </footer>
    </div>
    <!-- ./app-wrapper -->

    <!-- Bootstrap 5.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE 4 JS -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@4/dist/js/adminlte.min.js"></script>
    
    <!-- Dark Mode Toggle Script -->
    <script>
        function toggleDarkMode() {
            const html = document.documentElement;
            const current = html.getAttribute('data-bs-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('theme', next);
            updateToggleIcon(next);
        }

        function updateToggleIcon(theme) {
            const icon = document.getElementById('theme-toggle-icon');
            if (icon) {
                if (theme === 'dark') {
                    icon.className = 'bi bi-moon-fill';
                } else {
                    icon.className = 'bi bi-sun-fill';
                }
            }
        }

        // Sync toggle icon on load
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light';
            updateToggleIcon(savedTheme);
        });
    </script>
</body>
</html>
