<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="RK Marketing Drive - Plataforma de gestion de archivos multimedia para clientes">
    <meta name="robots" content="noindex, nofollow">

    <title><?= $pageTitle ?? 'RK Marketing Drive' ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= APP_URL ?>/src/img/favicon.ico">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/src/Css/style.css">

    <!-- CSRF Token para AJAX -->
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
</head>
<body class="<?= isset($bodyClass) ? $bodyClass : '' ?>">
    <!-- Navigation -->
    <?php if (isAuthenticated()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= APP_URL ?>/dashboard">
                <i class="bi bi-cloud-arrow-up me-2"></i>
                RK Marketing Drive
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Menu principal -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= ($page ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= APP_URL ?>/dashboard">
                            <i class="bi bi-speedometer2 me-1"></i> Panel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page ?? '') === 'folders' ? 'active' : '' ?>" href="<?= APP_URL ?>/folders">
                            <i class="bi bi-folder me-1"></i> Carpetas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page ?? '') === 'trash' ? 'active' : '' ?>" href="<?= APP_URL ?>/trash">
                            <i class="bi bi-trash me-1"></i> Papelera
                        </a>
                    </li>

                    <?php if (isAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i> Administracion
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/users"><i class="bi bi-people me-2"></i>Usuarios</a></li>
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/files"><i class="bi bi-files me-2"></i>Archivos</a></li>
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/logs"><i class="bi bi-journal-text me-2"></i>Logs</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/settings"><i class="bi bi-sliders me-2"></i>Configuracion</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- Menu derecho -->
                <ul class="navbar-nav">
                    <!-- Notificaciones -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <?php
                            $unreadNotifications = 0;
                            // TODO: Implementar contador real de notificaciones
                            ?>
                            <?php if ($unreadNotifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px;">
                            <li><h6 class="dropdown-header">Notificaciones</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="<?= APP_URL ?>/notifications">Ver todas</a></li>
                        </ul>
                    </li>

                    <!-- Almacenamiento -->
                    <li class="nav-item d-flex align-items-center ms-2">
                        <?php
                        $storageUsed = $_SESSION['storage_used'] ?? 0;
                        $storageMax = $_SESSION['storage_max'] ?? MAX_STORAGE_PER_CLIENT;
                        $storagePercent = $storageMax > 0 ? round(($storageUsed / $storageMax) * 100) : 0;
                        ?>
                        <div class="storage-indicator" title="<?= formatFileSize($storageUsed) ?> de <?= formatFileSize($storageMax) ?> usado">
                            <small class="text-white-50 me-1">Almacenamiento:</small>
                            <div class="progress" style="width: 80px; height: 8px;">
                                <div class="progress-bar <?= $storagePercent > 80 ? 'bg-danger' : ($storagePercent > 60 ? 'bg-warning' : 'bg-success') ?>"
                                     style="width: <?= $storagePercent ?>%"></div>
                            </div>
                            <small class="text-white-50 ms-1"><?= $storagePercent ?>%</small>
                        </div>
                    </li>

                    <!-- Perfil -->
                    <li class="nav-item dropdown ms-3">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= sanitize($_SESSION['user_name'] ?? 'Usuario') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/profile"><i class="bi bi-person me-2"></i>Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="<?= isAuthenticated() ? 'container-fluid py-4' : '' ?>">
        <!-- Mensajes Flash -->
        <?php if (hasFlash()): ?>
        <div class="flash-messages">
            <?php foreach (getFlash() as $flash): ?>
            <div class="alert alert-<?= sanitize($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= sanitize($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Contenido de la pagina -->
