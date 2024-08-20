<aside class="app-sidebar sticky" id="sidebar">
    <div class="main-sidebar-header">
        <a href="<?= base_url('/') ?>" class="header-logo">
            <img src="<?= base_url('assets/images/logo.png') ?>" alt="logo" class="desktop-logo">
            <img src="<?= base_url('assets/images/favicon.png') ?>" alt="logo" class="toggle-logo">
        </a>
    </div>
    <div class="main-sidebar" id="sidebar-scroll">
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="slide-left" id="slide-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M13.293 6.293L7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path>
                </svg>
            </div>
            <ul class="main-menu">
                <li class="slide__category"><span class="category-name">General</span></li>
                <li class="slide">
                    <a href="<?= base_url('dashboard') ?>" class="side-menu__item">
                        <i class="fa-solid fa-tachometer-alt side-menu__icon"></i>
                        <span class="side-menu__label">Dashboard</span>
                    </a>
                </li>
                <li class="slide">
                    <a href="<?= base_url('usuarios') ?>" class="side-menu__item">
                        <i class="fa-solid fa-users side-menu__icon"></i>
                        <span class="side-menu__label">Usuarios</span>
                    </a>
                </li>
                <li class="slide">
                    <a href="<?= base_url('clientes') ?>" class="side-menu__item">
                        <i class="fa-solid fa-building side-menu__icon"></i>
                        <span class="side-menu__label">Clientes</span>
                    </a>
                </li>
                <li class="slide">
                    <a href="<?= base_url('sucursales') ?>" class="side-menu__item">
                        <i class="fa-solid fa-store side-menu__icon"></i>
                        <span class="side-menu__label">Sucursales</span>
                    </a>
                </li>
                <li class="slide">
                    <a href="<?= base_url('categorias') ?>" class="side-menu__item">
                        <i class="fa-solid fa-tags side-menu__icon"></i>
                        <span class="side-menu__label">Categorías</span>
                    </a>
                </li>
                <li class="slide">
                    <a href="<?= base_url('denuncias') ?>" class="side-menu__item">
                        <i class="fa-solid fa-exclamation-triangle side-menu__icon"></i>
                        <span class="side-menu__label">Denuncias</span>
                    </a>
                </li>
                <li class="slide__category"><span class="category-name">Reportes</span></li>
                <li class="slide has-sub">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="fa-solid fa-chart-bar side-menu__icon"></i>
                        <span class="side-menu__label">Reportes</span>
                        <i class="fa fa-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide">
                            <a href="<?= base_url('reportes/gasolina') ?>" class="side-menu__item">Reporte Denuncias</a>
                        </li>
                    </ul>
                </li>
            </ul>
            <div class="slide-right" id="slide-right">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M10.707 17.707L16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path>
                </svg>
            </div>
        </nav>
    </div>
</aside>