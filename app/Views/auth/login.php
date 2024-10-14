<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light" data-header-styles="light" data-menu-styles="light" data-toggled="close">

<head>
    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Eqqua - Iniciar Sesión</title>

    <link id="style" href="<?= base_url('assets/libs/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/styles.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/site.css') ?>" rel="stylesheet">
    <script src="https://kit.fontawesome.com/901438e2f4.js" crossorigin="anonymous"></script>
</head>

<body class="bg-white">

    <div class="row authentication mx-0">
        <div class="col-xxl-5 col-xl-5 col-lg-5 d-xl-block d-none px-0">
            <div class="authentication-cover">
                <div class="authentication-cover-content rounded">
                    <div class="swiper-slide">
                        <div class="text-fixed-white text-center d-flex align-items-center justify-content-center">
                            <div>
                                <div class="mb-5">
                                    <img src="<?= base_url('assets/images/login.png') ?>" class="authentication-image" alt="">
                                </div>
                                <h6 class="fw-semibold text-fixed-white">Iniciar Sesión</h6>
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
                        <img src="<?= base_url('assets/images/logo.png') ?>" alt=eqqua" class="mb-4">
                        <p class="h5 fw-semibold mb-5">Iniciar Sesión</p>
                        <?php if (session()->getFlashdata('msg')) : ?>
                            <div class="alert alert-danger"><?= session()->getFlashdata('msg') ?></div>
                        <?php endif; ?>
                        <form action="<?= base_url('auth/loginSubmit') ?>" method="post">
                            <div class="row gy-3">
                                <div class="col-xl-12 mt-0">
                                    <label for="signin-username" class="form-label text-default">Correo Electrónico</label>
                                    <input type="email" class="form-control form-control-lg" id="signin-username" name="correo_electronico" placeholder="Correo Electrónico" tabindex="1" required>
                                </div>
                                <div class="col-xl-12 mb-3">
                                    <label for="signin-password" class="form-label text-default d-block">Contraseña<a href="<?= base_url('forgot-password') ?>" class="float-end text-info">¿Olvidaste tu contraseña?</a></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-lg" id="signin-password" name="contrasena" placeholder="Contraseña" tabindex="2" required>
                                        <button class="btn btn-light" type="button" onclick="togglePassword('signin-password', this)" id="button-addon2"><i class="fas fa-eye-slash align-middle"></i></button>
                                    </div>
                                    <div class="mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="defaultCheck1">
                                            <label class="form-check-label text-muted fw-normal" for="defaultCheck1">
                                                Recordar contraseña
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-12 d-grid mt-2">
                                    <button type="submit" class="btn btn-lg btn-info" tabindex="3">Iniciar Sesión</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="<?= base_url('assets/libs/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-element-bundle.min.js"></script>
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