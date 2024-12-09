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
            <?= esc($cliente['saludo']) ?>
        </p>
        <div class="logo-container">
            <img src="<?= base_url($cliente['logo']) ?>" alt="<?= esc($cliente['nombre_empresa']) ?> Logo" class="animate__animated animate__zoomIn">
        </div>
        <a href="<?= base_url("c/" . esc($cliente['slug']) . "/formulario-denuncia") ?>" class="btn btn-custom animate__animated animate__fadeInLeft">Reportar Denuncia</a>
        <a href="<?= base_url("c/" . esc($cliente['slug']) . "/seguimiento-denuncia") ?>" class="btn btn-custom animate__animated animate__fadeInRight">Seguimiento a mi Denuncia</a>
    </div>
</section>

<!-- Services Section -->
<section>
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Beneficios</h2>
        <div class="row">
            <!-- Seguridad -->
            <div class="col-md-4">
                <div class="service-box" data-aos="fade-up">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Confidencialidad</h4>
                    <p>Protegemos la privacidad de tus denuncias. Tu identidad siempre estará segura.</p>
                </div>
            </div>
            <!-- Gestión Eficiente -->
            <div class="col-md-4">
                <div class="service-box" data-aos="fade-up" data-aos-delay="100">
                    <i class="fas fa-file-alt"></i>
                    <h4>Proceso Transparente</h4>
                    <p>Sigue el estado de tu denuncia de manera clara y sencilla. Estarás informado en cada paso.</p>
                </div>
            </div>
            <!-- Soporte Continuo -->
            <div class="col-md-4">
                <div class="service-box" data-aos="fade-up" data-aos-delay="200">
                    <i class="fas fa-phone-alt"></i>
                    <h4>Soporte 24/7</h4>
                    <p>Estamos aquí para ayudarte en cualquier momento. Respuesta rápida y asistencia continua.</p>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Contact Section -->
<section class="contact-section mt-5">
    <!-- Línea telefónica -->
    <div class="contact-item">
        <i class="fas fa-phone"></i>
        <p>Línea telefónica</p>
        <!-- Validación del número de teléfono antes de mostrar el enlace -->
        <?php if (!empty($cliente['telefono_contacto'])): ?>
            <a href="tel:<?= esc($cliente['telefono_contacto']) ?>"><?= esc($cliente['telefono_contacto']) ?></a>
        <?php else: ?>
            <p>No disponible</p>
        <?php endif; ?>
    </div>

    <!-- Email -->
    <div class="contact-item">
        <i class="fas fa-envelope"></i>
        <p>Email</p>
        <!-- Validación del correo electrónico antes de mostrar el enlace -->
        <?php if (!empty($cliente['correo_contacto'])): ?>
            <a href="mailto:<?= esc($cliente['correo_contacto']) ?>"><?= esc($cliente['correo_contacto']) ?></a>
        <?php else: ?>
            <p>No disponible</p>
        <?php endif; ?>
    </div>

    <!-- WhatsApp -->
    <div class="contact-item">
        <i class="fa-brands fa-whatsapp"></i>
        <p>WhatsApp</p>
        <!-- Validación de número de WhatsApp antes de mostrar el enlace -->
        <?php if (!empty($cliente['whatsapp'])): ?>
            <a href="https://wa.me/<?= esc($cliente['whatsapp']) ?>" target="_blank">Iniciar chat</a>
        <?php else: ?>
            <p>No disponible</p>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>