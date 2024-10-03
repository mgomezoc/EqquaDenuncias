<?= $this->extend('layouts/public_layout') ?>

<?= $this->section('styles') ?>
<style>
    .hero-section {
        background: url('<?= base_url($cliente['banner'] ?? 'assets/images/default-banner.jpg') ?>') no-repeat center center;
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
        z-index: 1;
    }

    .hero-section .container {
        position: relative;
        z-index: 2;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Hero Section -->
<section class="hero-section mb-5">
    <div class="container">
        <h1 class="animate__animated animate__fadeInDown">Línea de Denuncias</h1>
        <p class="animate__animated animate__fadeInUp">
            En <?= esc($cliente['nombre_empresa']) ?> es muy importante el bienestar de nuestros colaboradores, proveedores, socios de negocio y clientes.
            Por esto, ponemos a su disposición esta plataforma de recepción de denuncias.
        </p>
        <div class="logo-container">
            <img src="<?= base_url($cliente['logo']) ?>" alt="<?= esc($cliente['nombre_empresa']) ?> Logo" class="animate__animated animate__zoomIn">
        </div>
        <a href="<?= base_url("public/cliente/" . esc($cliente['slug']) . "/formulario-denuncia") ?>" class="btn btn-custom animate__animated animate__fadeInLeft">Reportar Denuncia</a>
        <a href="<?= base_url("public/cliente/" . esc($cliente['slug']) . "/seguimiento-denuncia") ?>" class="btn btn-custom animate__animated animate__fadeInRight">Seguimiento a mi Denuncia</a>
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
<?= $this->endSection() ?>