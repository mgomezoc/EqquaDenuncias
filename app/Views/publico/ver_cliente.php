<?= $this->extend('layouts/public_layout') ?>

<?= $this->section('styles') ?>
<style>
    .hero-section {
        background: url('<?= base_url($cliente['banner'] ?? 'assets/images/default-banner.jpg') ?>') no-repeat center center;
        background-size: cover;
        position: relative;
        padding: 50px 0;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1;
    }

    .hero-section .container {
        position: relative;
        z-index: 2;
        text-align: center;
        color: #fff;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Hero Section -->
<section class="hero-section mb-5">
    <div class="container">
        <h1 class="animate__animated animate__fadeInDown">Línea de Denuncias</h1>
        <p class="animate__animated animate__fadeInUp"><?= esc($cliente['saludo']) ?></p>
        <div class="logo-container mb-5">
            <img src="<?= base_url($cliente['logo']) ?>" alt="<?= esc($cliente['nombre_empresa']) ?> Logo" class="animate__animated animate__zoomIn">
        </div>
        <div class="canales">
            <!-- Columna de canales -->
            <div class="canales-column">
                <h3>Reporta tu denuncia a través de los siguientes canales:</h3>
                <ul class="canales-list">
                    <?php if (!empty($cliente['telefono_contacto'])): ?>
                        <li class="canales-list-item">
                            <a href="tel:<?= esc($cliente['telefono_contacto']) ?>" class="canales-link">
                                <i class="fas fa-phone"></i> Llamar <?= esc($cliente['telefono_contacto']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($cliente['whatsapp'])): ?>
                        <li class="canales-list-item">
                            <a href="https://wa.me/<?= esc($cliente['whatsapp']) ?>" target="_blank" class="canales-link">
                                <i class="fab fa-whatsapp"></i> Enviar WhatsApp
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="canales-list-item">
                        <a href="<?= base_url("c/" . esc($cliente['slug']) . "/formulario-denuncia") ?>" class="canales-link">
                            <i class="fas fa-edit"></i> Formulario en línea
                        </a>
                    </li>
                    <?php if (!empty($cliente['correo_contacto'])): ?>
                        <li class="canales-list-item">
                            <a href="mailto:<?= esc($cliente['correo_contacto']) ?>" class="canales-link">
                                <i class="fas fa-envelope"></i> <?= esc($cliente['correo_contacto']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <!-- Columna de seguimiento -->
            <div class="canales-column margin">
                <h3>Da seguimiento a tu denuncia aquí:</h3>
                <a href="<?= base_url("c/" . esc($cliente['slug']) . "/seguimiento-denuncia") ?>" class="canales-link">
                    <i class="fas fa-search"></i> Seguimiento denuncia
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Beneficios -->
<section>
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Beneficios</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="service-box" data-aos="fade-up">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Confidencialidad</h4>
                    <p>Protegemos la privacidad de tus denuncias. Tu identidad siempre estará segura.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-box" data-aos="fade-up" data-aos-delay="100">
                    <i class="fas fa-file-alt"></i>
                    <h4>Proceso Transparente</h4>
                    <p>Sigue el estado de tu denuncia de manera clara y sencilla. Estarás informado en cada paso.</p>
                </div>
            </div>
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
<?= $this->endSection() ?>