<?php

declare(strict_types=1);

// 1. System dependency autoloaders
require_once '/usr/share/php/Composer/InstalledVersions.php';
// Add more require_once lines for other system dependencies if needed

// 2. PSR-4 registration for the project's own classes
spl_autoload_register(function (string $class): void {
    $prefix = 'ISPConfigMonitoring\\';
    if (str_starts_with($class, $prefix)) {
        $file = '/usr/lib/ispconfig-zabbix-monitoring/lib/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// 3. InstalledVersions block (placeholders replaced at build time)
(function (): void {
    $versions = [];
    foreach (\Composer\InstalledVersions::getAllRawData() as $d) {
        $versions = array_merge($versions, $d['versions'] ?? []);
    }
    $name    = 'unknown';
    $version = '0.0.0';
    $versions[$name] = ['pretty_version' => $version, 'version' => $version,
        'reference' => null, 'type' => 'library', 'install_path' => __DIR__,
        'aliases' => [], 'dev_requirement' => false];
    \Composer\InstalledVersions::reload([
        'root' => ['name' => $name, 'pretty_version' => $version, 'version' => $version,
            'reference' => null, 'type' => 'library', 'install_path' => __DIR__,
            'aliases' => [], 'dev' => false],
        'versions' => $versions,
    ]);
})();
