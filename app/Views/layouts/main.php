<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= $this->renderSection('title') ?> - Eqqua</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= base_url('assets/css/styles.min.css') ?>?v=<?= config('App')->assetVersion ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="<?= base_url('assets/css/simplebar.min.css') ?>?v=<?= config('App')->assetVersion ?>" rel="stylesheet" />
    <?= $this->renderSection('styles') ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/site.css') ?>?v=<?= config('App')->assetVersion ?>">

    <link rel="icon" type="image/png" href="<?= base_url("favicon-48x48.png") ?>" sizes="48x48" />
    <link rel="icon" type="image/svg+xml" href="<?= base_url("favicon.svg") ?>" />
    <link rel="shortcut icon" href="<?= base_url("favicon.ico") ?>" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url("apple-touch-icon.png") ?>" />
    <meta name="apple-mobile-web-app-title" content="Eqqua" />
    <link rel="manifest" href="/site.webmanifest" />

    <script src="https://kit.fontawesome.com/901438e2f4.js" crossorigin="anonymous"></script>
    <script>
        var Server = "<?= base_url('/') ?>";
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/11.0.5/swiper-bundle.css" integrity="sha512-pmAAV1X4Nh5jA9m+jcvwJXFQvCBi3T17aZ1KWkqXr7g/O2YMvO8rfaa5ETWDuBvRq6fbDjlw4jHL44jNTScaKg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/11.0.5/swiper-bundle.min.js" integrity="sha512-Ysw1DcK1P+uYLqprEAzNQJP+J4hTx4t/3X2nbVwszao8wD+9afLjBQYjz7Uk4ADP+Er++mJoScI42ueGtQOzEA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>

<body>
    <!-- Loading Screen -->
    <div id="loading" class="loading">
        <div class="loading-container">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <?= $this->renderSection('modals') ?>

    <div class="page">
        <?php if (!session()->get('isLoggedIn')) : ?>
            <?= $this->renderSection('content') ?>
        <?php else : ?>
            <?= $this->include('partials/_header') ?>
            <?= $this->include('partials/_sidebar') ?>
            <?= $this->include('partials/_pageHeaderScroll') ?>

            <div class="main-content app-content">
                <div class="container-fluid">
                    <?= $this->renderSection('content') ?>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <!-- Scroll To Top -->
    <div class="scrollToTop">
        <span class="arrow">
            <i class="fa-solid fa-chevron-up fs-20"></i>
        </span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/handlebars@latest/dist/handlebars.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="<?= base_url('assets/js/popper.min.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= base_url('assets/js/defaultmenu.min.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
    <script src="<?= base_url('assets/js/simplebar.min.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
    <script src="<?= base_url('assets/js/simplebar.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
    <script src="<?= base_url('assets/js/app.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?= $this->renderSection('scripts') ?>

</body>

</html>