<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="RK Marketing Drive - Plataforma de gestion de archivos multimedia para clientes">
    <meta name="robots" content="noindex, nofollow">

    <title><?= $pageTitle ?? 'RK Marketing Drive' ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= APP_URL ?>/public/img/favicon.ico">

    <!-- Google Fonts: Poppins + Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Manrope:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/public/vendor/bootstrap/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="<?= APP_URL ?>/public/vendor/bootstrap-icons/css/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/public/css/style.css?v=<?= APP_VERSION ?>">

    <!-- Aplicar tema guardado antes de render para evitar flash -->
    <script>
        (function(){
            var t = localStorage.getItem('rk-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    <!-- CSRF Token para AJAX -->
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
</head>
<body class="<?= isset($bodyClass) ? $bodyClass : '' ?>">

    <!-- Navigation -->
    <?php if (isAuthenticated()): ?>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">

            <!-- Brand / Logo -->
            <a class="navbar-brand" href="<?= APP_URL ?>/dashboard">
                <img src="<?= APP_URL ?>/public/img/logos/logo_rk_blanco.svg" alt="RK Drive" height="32">
            </a>

            <!-- Mobile toggler -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">

                <!-- Main navigation -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= ($page ?? '') === 'dashboard' ? 'active' : '' ?>"
                           href="<?= APP_URL ?>/dashboard">
                            <i class="bi bi-speedometer2"></i> Panel
                        </a>
                    </li>

                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page ?? '') === 'admin/users' ? 'active' : '' ?>"
                           href="<?= APP_URL ?>/admin/users">
                            <i class="bi bi-people"></i> Usuarios
                        </a>
                    </li>
                    <?php elseif (!isWorker()): ?>
                    <!-- Cliente: carpetas y archivos sueltos -->
                    <li class="nav-item">
                        <a class="nav-link <?= ($page ?? '') === 'folders' ? 'active' : '' ?>"
                           href="<?= APP_URL ?>/folders">
                            <i class="bi bi-files"></i> Archivos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page ?? '') === 'trash' ? 'active' : '' ?>"
                           href="<?= APP_URL ?>/?page=trash">
                            <i class="bi bi-trash"></i> Papelera
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (isWorker()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page ?? '') === 'worker/clients' ? 'active' : '' ?>"
                           href="<?= APP_URL ?>/?page=worker/clients">
                            <i class="bi bi-people"></i> Clientes
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (isAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= (str_starts_with($page ?? '', 'admin') && ($page ?? '') !== 'admin/users') ? 'active' : '' ?>"
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Administracion
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/admin/clients">
                                    <i class="bi bi-folder2-open"></i>Archivos por Cliente
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/admin/logs">
                                    <i class="bi bi-journal-text"></i>Logs
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/admin/settings">
                                    <i class="bi bi-sliders"></i>Configuracion
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page ?? '') === 'trash' ? 'active' : '' ?>"
                           href="<?= APP_URL ?>/?page=trash">
                            <i class="bi bi-trash"></i> Papelera
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- Right-side navigation -->
                <ul class="navbar-nav align-items-center gap-1">

                    <!-- Dark / Light toggle -->
                    <li class="nav-item">
                        <button id="themeToggle" class="btn-theme-toggle" title="Cambiar tema" aria-label="Cambiar tema">
                            <i class="bi bi-sun-fill" id="themeIcon"></i>
                        </button>
                    </li>

                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown"
                           aria-expanded="false" title="Notificaciones">
                            <i class="bi bi-bell"></i>
                            <?php
                            $unreadNotifications = 0;
                            // TODO: Implementar contador real de notificaciones
                            ?>
                            <?php if ($unreadNotifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                  style="font-size:0.6rem;padding:0.2em 0.4em;">
                                <?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="min-width:280px;">
                            <li><span class="dropdown-header">Notificaciones</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item justify-content-center" href="<?= APP_URL ?>/notifications">
                                    Ver todas
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Storage indicator: solo para clientes -->
                    <?php if (!isAdmin() && !isWorker()): ?>
                    <li class="nav-item d-none d-xl-flex align-items-center ms-1">
                        <?php
                        $storageUsed    = $_SESSION['storage_used'] ?? 0;
                        $storageMax     = $_SESSION['storage_max'] ?? MAX_STORAGE_PER_CLIENT;
                        $storagePercent = $storageMax > 0 ? round(($storageUsed / $storageMax) * 100) : 0;
                        ?>
                        <div class="storage-indicator"
                             title="<?= formatFileSize($storageUsed) ?> de <?= formatFileSize($storageMax) ?> usado">
                            <small class="text-muted me-1">Almacenamiento</small>
                            <div class="progress" style="width:72px;height:5px;">
                                <div class="progress-bar <?= $storagePercent > 80 ? 'bg-danger' : ($storagePercent > 60 ? 'bg-warning' : '') ?>"
                                     style="width:<?= $storagePercent ?>%"></div>
                            </div>
                            <small class="text-muted ms-1"><?= $storagePercent ?>%</small>
                        </div>
                    </li>
                    <?php endif; ?>

                    <!-- User profile dropdown -->
                    <li class="nav-item dropdown ms-1">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                                  style="width:28px;height:28px;background:var(--primary);color:#0d0d0d;font-size:0.75rem;font-weight:700;flex-shrink:0;">
                                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                            </span>
                            <span class="d-none d-md-inline" style="font-size:0.84rem;">
                                <?= sanitize($_SESSION['user_name'] ?? 'Usuario') ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/profile">
                                    <i class="bi bi-person"></i>Mi Perfil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="<?= APP_URL ?>/?page=logout" class="d-inline">
                                    <?= csrfField() ?>
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>

                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="<?= isAuthenticated() ? 'container-fluid py-4' : '' ?>">

        <!-- Flash Messages -->
        <?php if (hasFlash()): ?>
        <div class="flash-messages">
            <?php foreach (getFlash() as $flash): ?>
            <div class="alert alert-<?= sanitize($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= sanitize($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Contenido de la pagina -->
