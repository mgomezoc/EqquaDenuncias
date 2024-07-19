<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light" data-header-styles="light" data-menu-styles="light" data-toggled="close">

<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Eqqua - Registro</title>

    <link id="style" href="<?= base_url('assets/libs/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/styles.min.css') ?>" rel="stylesheet">
    <script src="https://kit.fontawesome.com/901438e2f4.js" crossorigin="anonymous"></script>
</head>

<body class="bg-white">

    <div class="row authentication mx-0">
        <div class="col-xxl-5 col-xl-5 col-lg-5 d-xl-block d-none px-0">
            <div class="authentication-cover">
                <div class="aunthentication-cover-content rounded">
                    <div class="swiper-slide">
                        <div class="text-fixed-white text-center d-flex align-items-center justify-content-center">
                            <div>
                                <div class="mb-5">
                                    <img src="<?= base_url('assets/images/eqqua logos-09.png') ?>" class="authentication-image" alt="">
                                </div>
                                <h6 class="fw-semibold text-fixed-white">Registro</h6>
                                <p class="fw-normal fs-14 op-7">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ipsa eligendi expedita aliquam quaerat nulla voluptas facilis.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-7 col-xl-7 col-lg-12">
            <div class="row justify-content-center align-items-center h-100">
                <div class="col-xxl-6 col-xl-7 col-lg-7 col-md-7 col-sm-8 col-12">
                    <div class="p-5">
                        <div class="mb-3">
                            <a href="<?= base_url('/') ?>">
                                <img src="<?= base_url('assets/images/eqqua logos-09.png') ?>" alt="" class="authentication-brand desktop-logo">
                                <img src="<?= base_url('assets/images/eqqua logos-09.png') ?>" alt="" class="authentication-brand desktop-dark">
                            </a>
                        </div>
                        <p class="h5 fw-semibold mb-2">Registro</p>
                        <p class="mb-3 text-muted op-7 fw-normal">Bienvenido, ¡únete creando una cuenta gratuita!</p>
                        <?php if (session()->getFlashdata('msg')) : ?>
                            <div class="alert alert-danger"><?= session()->getFlashdata('msg') ?></div>
                        <?php endif; ?>
                        <form action="<?= base_url('auth/registerSubmit') ?>" method="post">
                            <div class="row gy-3">
                                <div class="col-xl-12 mt-0">
                                    <label for="signup-username" class="form-label text-default">Nombre de Usuario</label>
                                    <input type="text" class="form-control form-control-lg" id="signup-username" name="nombre_usuario" placeholder="Nombre de Usuario" required>
                                </div>
                                <div class="col-xl-12">
                                    <label for="signup-email" class="form-label text-default">Correo Electrónico</label>
                                    <input type="email" class="form-control form-control-lg" id="signup-email" name="correo_electronico" placeholder="Correo Electrónico" required>
                                </div>
                                <div class="col-xl-12">
                                    <label for="signup-password" class="form-label text-default">Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-lg" id="signup-password" name="contrasena" placeholder="Contraseña" required>
                                        <button class="btn btn-light" onclick="togglePassword('signup-password',this)" type="button" id="button-addon2"><i class="fas fa-eye-slash align-middle"></i></button>
                                    </div>
                                </div>
                                <div class="col-xl-12 mb-3">
                                    <label for="signup-confirmpassword" class="form-label text-default">Confirmar Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-lg" id="signup-confirmpassword" name="confirmar_contrasena" placeholder="Confirmar Contraseña" required>
                                        <button class="btn btn-light" onclick="togglePassword('signup-confirmpassword',this)" type="button" id="button-addon21"><i class="fas fa-eye-slash align-middle"></i></button>
                                    </div>
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" value="" id="defaultCheck1">
                                        <label class="form-check-label text-muted fw-normal" for="defaultCheck1">
                                            Al crear una cuenta, aceptas nuestros <a href="<?= base_url('terms_conditions') ?>" class="text-success"><u>Términos y Condiciones</u></a> y nuestra <a href="<?= base_url('privacy_policy') ?>" class="text-success"><u>Política de Privacidad</u></a>.
                                        </label>
                                    </div>
                                </div>
                                <div class="col-xl-12 d-grid mt-2">
                                    <button type="submit" class="btn btn-lg btn-primary">Crear Cuenta</button>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="fs-12 text-muted mt-4">¿Ya tienes una cuenta? <a href="<?= base_url('login') ?>" class="text-primary">Iniciar Sesión</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?= base_url('assets/libs/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        "use strict";

        function togglePassword(id, button) {
            var passwordField = document.getElementById(id);
            var type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            button.innerHTML = type === 'password' ? '<i class="fas fa-eye-slash align-middle"></i>' : '<i class="fas fa-eye align-middle"></i>';
        }
    </script>

</body>

</html>