<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    public string $baseURL = 'https://eqqua.test/EqquaDenuncias/public/';

    public array $allowedHostnames = [];

    public string $indexPage = 'index.php';

    public string $uriProtocol = 'REQUEST_URI';

    public string $permittedURIChars = 'a-z 0-9~%.:_\-';

    public string $defaultLocale = 'es';

    public bool $negotiateLocale = false;

    public array $supportedLocales = ['es'];

    public string $appTimezone = 'America/Monterrey';

    public string $charset = 'UTF-8';

    public bool $forceGlobalSecureRequests = true;

    public array $proxyIPs = [];

    public bool $CSPEnabled = false;

    public string $assetVersion = '0.0.1';
}
