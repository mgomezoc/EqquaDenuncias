<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <meta name="description" content="Eqqua - Sistema de Gestión de Denuncias. Captura, revisa y gestiona denuncias de manera eficiente.">
    <meta name="keywords" content="denuncias, gestión de denuncias, reporte de denuncias, Eqqua">
    <meta name="author" content="Eqqua.mx">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            color: #333;
        }

        .navbar {
            background-color: #4CAF50;
        }

        .navbar-brand,
        .nav-link {
            color: #fff !important;
        }

        .hero-section {
            background: #ccc url('<?= base_url($cliente['banner']) ?>') no-repeat center center;
            background-size: cover;
            padding: 100px 0;
            text-align: center;
            color: #fff;
        }

        .hero-section h1 {
            font-size: 3rem;
            font-weight: bold;
        }

        .hero-section p {
            font-size: 1.25rem;
            margin-bottom: 30px;
        }

        .hero-section .logo-container img {
            max-width: 150px;
            height: auto;
            margin: 0 10px;
        }

        .button-group .btn-custom {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-transform: uppercase;
        }

        .button-group .btn-custom:hover {
            background-color: #45a049;
        }

        .contact-info {
            font-size: 1.25rem;
            color: #333;
            margin-top: 40px;
        }

        .contact-info p {
            margin-bottom: 15px;
        }

        .contact-info i {
            color: #4CAF50;
        }

        footer {
            background-color: #f1f1f1;
            padding: 20px 0;
            text-align: center;
            color: #333;
        }

        .logo-eqqua {
            max-width: 200px;
            margin-bottom: 20px;
        }

        .animate__animated.animate__fadeIn {
            --animate-duration: 2s;
        }

        .social-icons a {
            color: #4CAF50;
            margin: 0 10px;
            font-size: 1.5rem;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="<?= base_url("eqqua-bienvenida") ?>">Sistema de Denuncias</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url("eqqua-formulario") ?>">Registrar Denuncia</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url("eqqua-seguimiento") ?>">Seguimiento de Folio</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section animate__animated animate__fadeIn">
        <div class="container">
            <img src="<?= base_url("assets/images/logo_blanco.png") ?>" alt="Eqqua Logo" class="logo-eqqua">
            <h1>Línea de Denuncias</h1>
            <div class="logo-container">
                <img src="<?= base_url($cliente['logo']) ?>" alt="<?= esc($cliente['nombre_empresa']) ?> Logo">
            </div>
            <p>En <?= esc($cliente['nombre_empresa']) ?> es muy importante el bienestar de nuestros colaboradores, proveedores, socios de negocio y clientes. Por esto, ponemos a su disposición esta plataforma de recepción de denuncias.</p>
            <div class="button-group">
                <a href="<?= base_url("eqqua-formulario") ?>" class="btn btn-custom">Reportar Denuncia Aquí <i class="fas fa-bullhorn"></i></a>
                <a href="<?= base_url("eqqua-seguimiento") ?>" class="btn btn-custom">Seguimiento a mi Denuncia <i class="fas fa-search"></i></a>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <div class="text-center mt-5 contact-info animate__animated animate__fadeInUp">
            <h2>Ó a través de los siguientes canales:</h2>
            <p><i class="fas fa-phone"></i> Línea telefónica: <a href="tel:<?= esc($cliente['telefono_contacto']) ?>"><?= esc($cliente['telefono_contacto']) ?></a></p>
            <p><i class="fas fa-envelope"></i> Email: <a href="mailto:<?= esc($cliente['correo_contacto']) ?>"><?= esc($cliente['correo_contacto']) ?></a></p>
            <p><i class="fas fa-comments"></i> WhatsApp: <a href="https://wa.me/<?= esc($cliente['telefono_contacto']) ?>"><?= esc($cliente['telefono_contacto']) ?></a></p>
        </div>
        <div class="text-center social-icons animate__animated animate__fadeInUp">
            <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook"></i></a>
            <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
            <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin"></i></a>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2024 Sistema de Denuncias. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></script>
</body>

</html>