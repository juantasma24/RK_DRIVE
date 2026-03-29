    </main>

    <!-- Footer -->
    <?php if (isAuthenticated()): ?>
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <span class="text-muted">&copy; <?= date('Y') ?> RK Solutions - Marketing Drive v<?= APP_VERSION ?></span>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-muted small">
                        <i class="bi bi-clock me-1"></i>
                        Ultima actividad: <?= date('d/m/Y H:i') ?>
                    </span>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Bootstrap 5 JS -->
    <script src="<?= APP_URL ?>/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="<?= APP_URL ?>/src/Js/app.js"></script>

    <!-- Scripts especificos de la pagina -->
    <?php if (isset($scripts) && is_array($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
        <script src="<?= APP_URL ?>/src/Js/<?= $script ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>

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
