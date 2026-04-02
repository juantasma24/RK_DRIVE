    </main>

    <!-- Footer -->
    <?php if (isAuthenticated()): ?>
    <footer class="footer mt-auto">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <span class="text-muted">&copy; <?= date('Y') ?> RK Solutions &mdash; Marketing Drive v<?= APP_VERSION ?></span>
                </div>
                <div class="col-md-6 text-md-end mt-1 mt-md-0">
                    <span class="text-muted small">
                        <i class="bi bi-clock me-1"></i>Ultima actividad: <?= date('d/m/Y H:i') ?>
                    </span>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Bootstrap 5 JS -->
    <script src="<?= APP_URL ?>/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="<?= APP_URL ?>/public/js/app.js?v=<?= APP_VERSION ?>"></script>

    <!-- Scripts especificos de la pagina -->
    <?php if (isset($scripts) && is_array($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
        <script src="<?= APP_URL ?>/src/Js/<?= $script ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Dark / Light mode toggle — inline para garantizar ejecucion sin cache -->
    <script>
    (function () {
        var btn  = document.getElementById('themeToggle');
        var icon = document.getElementById('themeIcon');

        function applyTheme(t) {
            document.documentElement.setAttribute('data-theme', t);
            localStorage.setItem('rk-theme', t);
            if (icon) icon.className = t === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        }

        // Sincronizar icono con tema actual
        var current = document.documentElement.getAttribute('data-theme') || 'dark';
        if (icon) icon.className = current === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';

        if (btn) {
            btn.addEventListener('click', function () {
                var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                applyTheme(next);

                // Guardar en BD para persistir por usuario
                var token = document.querySelector('meta[name="csrf-token"]');
                fetch('<?= APP_URL ?>/?page=theme', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': token ? token.content : ''
                    },
                    body: 'csrf_token=' + encodeURIComponent(token ? token.content : '') + '&tema=' + encodeURIComponent(next)
                });
            });
        }
    })();
    </script>

    <!-- Script inline para CSRF -->
    <script>
        // Token CSRF para peticiones AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // Funcion helper para peticiones fetch con CSRF
        async function fetchWithCsrf(url, options = {}) {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                }
            };

            if (options.body && typeof options.body === 'object') {
                options.body = JSON.stringify(options.body);
            }

            return fetch(url, { ...defaultOptions, ...options });
        }
    </script>
</body>
</html>
