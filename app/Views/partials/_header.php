// App/Views/partials/_header.php
<header class="app-header">
    <div class="main-header-container container-fluid">
        <div class="header-content-left">
            <div class="header-element">
                <div class="horizontal-logo">
                    <a href="<?= base_url('/') ?>" class="header-logo">
                        <img src="<?= base_url('assets/images/eqqua logos-09.png') ?>" alt="logo" class="desktop-logo">
                        <img src="<?= base_url('assets/images/eqqua logos-09.png') ?>" alt="logo" class="toggle-logo">
                    </a>
                </div>
            </div>
            <div class="header-element">
                <a aria-label="Hide Sidebar" class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle" data-bs-toggle="sidebar" href="javascript:void(0);"><span></span></a>
            </div>
        </div>
        <div class="header-content-right">
            <div class="header-element">
                <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <div class="me-sm-2 me-0">
                            <img src="<?= base_url('assets/images/bg.png') ?>" alt="img" width="32" height="32" class="rounded-circle">
                        </div>
                        <div class="d-sm-block d-none">
                            <p class="fw-semibold mb-0 lh-1"><?= session()->get('nombre_usuario') ?></p>
                            <span class="op-7 fw-normal d-block fs-12"><?= session()->get('rol_nombre') ?></span>
                        </div>
                    </div>
                </a>
                <ul class="main-header-dropdown dropdown-menu overflow-hidden header-profile-dropdown dropdown-menu-end" aria-labelledby="mainHeaderProfile">
                    <li>
                        <a class="dropdown-item d-flex" href="<?= base_url('logout') ?>">
                            <i class="fa fa-sign-out-alt fs-18 me-2 op-7"></i>Salir
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>