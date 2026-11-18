        </main>
        
        <!-- Dashboard Footer -->
        <footer class="py-3 px-4 border-top bg-card text-muted small d-flex flex-wrap justify-content-between align-items-center">
            <div>
                &copy; <?= date('Y') ?> <strong><?= e(APP_NAME) ?></strong> — Intern Portal
            </div>
            <div>
                Sole Proprietorship by <strong>Amit Repswal</strong> | Nawalgarh, Rajasthan
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Sidebar Toggle for Mobile View
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('internSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggleBtn && sidebar && overlay) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // Theme Toggle Handler
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;

    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-bs-theme', savedTheme);
    updateThemeIcon(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const current = html.getAttribute('data-bs-theme');
            const next = (current === 'dark') ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon(next);
        });
    }

    function updateThemeIcon(theme) {
        if (!themeToggle) return;
        const icon = themeToggle.querySelector('i');
        if (icon) {
            icon.className = (theme === 'dark') ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        }
    }
});
</script>

<?php if (isset($extraJS)): ?>
    <?php foreach ((array)$extraJS as $js): ?>
        <script src="<?= baseUrl('assets/js/' . $js) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
