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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/portal.css') ?>">
    <style>
        .hero-section {
            background: url('<?= base_url($cliente['banner']) ?>') no-repeat center center;
            background-size: cover;
            position: relative;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            /* Overlay oscuro para asegurar la legibilidad */
            z-index: 1;
        }

        .hero-section .container {
            position: relative;
            z-index: 2;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="<?= base_url('assets/images/logo.png') ?>" alt="Logo Eqqua" style="height: 45px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">Inicio</a>
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


    <!-- Hero Section -->
    <section class="hero-section mb-5">
        <div class="container">
            <h1 class="animate__animated animate__fadeInDown">Línea de Denuncias</h1>
            <p class="animate__animated animate__fadeInUp">En <?= esc($cliente['nombre_empresa']) ?> es muy importante el bienestar de nuestros colaboradores, proveedores, socios de negocio y clientes. Por esto, ponemos a su disposición esta plataforma de recepción de denuncias.</p>
            <div class="logo-container">
                <img src="<?= base_url($cliente['logo']) ?>" alt="<?= esc($cliente['nombre_empresa']) ?> Logo" class="animate__animated animate__zoomIn">
            </div>
            <a href="<?= base_url("eqqua-formulario") ?>" class="btn btn-custom animate__animated animate__fadeInLeft">Reportar Denuncia</a>
            <a href="<?= base_url("eqqua-seguimiento") ?>" class="btn btn-custom animate__animated animate__fadeInRight">Seguimiento a mi Denuncia</a>
        </div>
    </section>

    <!-- Services Section -->
    <section>
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Nuestros Servicios</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="service-box" data-aos="fade-up">
                        <i class="fas fa-shield-alt"></i>
                        <h4>Seguridad</h4>
                        <p>Mantén tus denuncias seguras y protegidas.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-box" data-aos="fade-up" data-aos-delay="100">
                        <i class="fas fa-file-alt"></i>
                        <h4>Gestión Eficiente</h4>
                        <p>Gestiona y revisa tus denuncias con facilidad.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-box" data-aos="fade-up" data-aos-delay="200">
                        <i class="fas fa-phone-alt"></i>
                        <h4>Soporte Continuo</h4>
                        <p>Estamos disponibles 24/7 para apoyarte.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="contact-item" data-aos="fade-up">
            <i class="fas fa-phone"></i>
            <p>Línea telefónica</p>
            <a href="tel:<?= esc($cliente['telefono_contacto']) ?>"><?= esc($cliente['telefono_contacto']) ?></a>
        </div>
        <div class="contact-item" data-aos="fade-up" data-aos-delay="100">
            <i class="fas fa-envelope"></i>
            <p>Email</p>
            <a href="mailto:<?= esc($cliente['correo_contacto']) ?>"><?= esc($cliente['correo_contacto']) ?></a>
        </div>
        <div class="contact-item" data-aos="fade-up" data-aos-delay="200">
            <i class="fas fa-comments"></i>
            <p>WhatsApp</p>
            <a href="https://wa.me/<?= esc($cliente['telefono_contacto']) ?>"><?= esc($cliente['telefono_contacto']) ?></a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <img src="<?= base_url("assets/images/eqqua logos-09.png") ?>" alt="Eqqua Logo" class="footer-logo">
            <p>&copy; 2024 Sistema de Denuncias. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 800, // Duración de la animación
            easing: 'ease-in-out', // Efecto de transición
            once: true, // La animación ocurre solo una vez mientras se desplaza
            disable: 'mobile' // Deshabilitar en dispositivos móviles para mejorar el rendimiento
        });
    </script>
</body>

</html>