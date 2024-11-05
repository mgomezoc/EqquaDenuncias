<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Sistema de Denuncias') ?></title>
    <meta name="description" content="Eqqua - Sistema de Gestión de Denuncias. Captura, revisa y gestiona denuncias de manera eficiente.">
    <meta name="keywords" content="denuncias, gestión de denuncias, reporte de denuncias, Eqqua">
    <meta name="author" content="Eqqua.mx">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?= $cliente['primary_color'] ?>;
            --secondary-color: <?= $cliente['secondary_color'] ?>;
            --link-color: <?= $cliente['link_color'] ?>;
        }
    </style>
    <link rel="stylesheet" href="<?= base_url('assets/css/portal.css') ?>">

    <?= $this->renderSection('styles') ?>

    <script>
        var Server = "<?= base_url('/') ?>";
    </script>
    <script src="https://kit.fontawesome.com/901438e2f4.js" crossorigin="anonymous"></script>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="<?= base_url("c/" . esc($cliente['slug'])) ?>">
                <img src="<?= base_url('assets/images/logo.png') ?>" alt="Logo Eqqua" style="height: 45px;">
                <span class="navbar-brand-client"><?= $cliente['nombre_empresa'] ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url("c/" . esc($cliente['slug'])) ?>">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url("c/" . esc($cliente['slug']) . "/formulario-denuncia") ?>">Registrar Denuncia</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url("c/" . esc($cliente['slug']) . "/seguimiento-denuncia") ?>">Seguimiento de Folio</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main content -->
    <div id="content" class="content">
        <?= $this->renderSection('content') ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <img src="<?= base_url("assets/images/eqqua logos-09.png") ?>" alt="Eqqua Logo" class="footer-logo">
            <p>&copy; <?= date('Y') ?> Sistema de Denuncias. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/handlebars@latest/dist/handlebars.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= base_url('assets/js/main_public.js') ?>"></script>

    <?= $this->renderSection('scripts') ?>
</body>

</html>