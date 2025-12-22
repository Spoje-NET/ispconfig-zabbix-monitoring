<?php

declare(strict_types=1);

/**
 * This file is part of the ISPConfig Zabbix Monitoring package.
 *
 * (c) Spoje-NET <info@spoje.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use ISPConfigMonitoring\ISPConfigClient;
use ISPConfigMonitoring\ISPConfigException;
use ISPConfigMonitoring\ZabbixHelper;

/**
 * Display usage information.
 */
function showUsage(): void
{
    echo "Usage: php websites.php <website_id> <key>\n\n";
    echo "Available keys:\n";
    echo "  active         - Website active status (0/1)\n";
    echo "  domain         - Domain name\n";
    echo "  server_id      - Server ID\n";
    echo "  document_root  - Document root path\n";
    echo "  php_version    - PHP version\n";
    echo "  ssl_enabled    - SSL status (0/1)\n";
    echo "  traffic        - Website traffic statistics\n";
    echo "  disk_usage     - Disk space usage in bytes\n";
    echo "  hd_quota       - Hard disk quota in bytes\n";

    exit(1);
}

/**
 * Get value from website data by key.
 */
function getWebsiteValue(array $website, string $key, ZabbixHelper $zabbix): string
{
    switch ($key) {
        case 'active':
            return $zabbix->formatItemValue($website['active'] ?? 'n', 'boolean');
        case 'domain':
            return $zabbix->formatItemValue($website['domain'] ?? '', 'string');
        case 'server_id':
            return $zabbix->formatItemValue($website['server_id'] ?? 0, 'numeric');
        case 'document_root':
            return $zabbix->formatItemValue($website['document_root'] ?? '', 'string');
        case 'php_version':
            return $zabbix->formatItemValue($website['php'] ?? 'default', 'string');
        case 'ssl_enabled':
            return $zabbix->formatItemValue($website['ssl'] ?? 'n', 'boolean');
        case 'traffic':
            // Get traffic from stats if available
            return $zabbix->formatItemValue($website['traffic_quota'] ?? 0, 'numeric');
        case 'disk_usage':
            // Parse disk usage from stats
            $usage = $website['hd_usage'] ?? 0;

            return $zabbix->formatItemValue($usage, 'bytes');
        case 'hd_quota':
            // Parse hard disk quota
            $quota = $website['hd_quota'] ?? 0;

            return $zabbix->formatItemValue($quota, 'bytes');
        case 'backup_interval':
            return $zabbix->formatItemValue($website['backup_interval'] ?? '', 'string');
        case 'backup_copies':
            return $zabbix->formatItemValue($website['backup_copies'] ?? 1, 'numeric');
        case 'type':
            return $zabbix->formatItemValue($website['type'] ?? 'vhost', 'string');
        case 'ipv4':
            return $zabbix->formatItemValue($website['ip_address'] ?? '', 'string');
        case 'ipv6':
            return $zabbix->formatItemValue($website['ipv6_address'] ?? '', 'string');

        default:
            throw new Exception("Unknown key: {$key}");
    }
}

// Parse command line arguments
if ($argc < 3) {
    showUsage();
}

$websiteId = (int) $argv[1];
$key = $argv[2];

if ($websiteId <= 0) {
    error_log("Invalid website ID: {$argv[1]}");

    exit(1);
}

// Load configuration
$configFile = __DIR__.'/../../config/config.php';

if (!file_exists($configFile)) {
    error_log("Configuration file not found: {$configFile}");

    exit(1);
}

$config = require $configFile;

// Check if websites module is enabled
if (empty($config['modules']['websites'])) {
    error_log('Websites module is disabled in configuration');

    exit(1);
}

try {
    // Initialize clients
    $ispconfig = new ISPConfigClient($config);
    $zabbix = new ZabbixHelper();

    // Get website data
    $website = $ispconfig->getWebsite($websiteId);

    if ($website === null) {
        throw new Exception("Website not found: {$websiteId}");
    }

    // Get and output the requested value
    $value = getWebsiteValue($website, $key, $zabbix);
    echo $value;

    exit(0);
} catch (ISPConfigException $e) {
    error_log('ISPConfig API Error: '.$e->getMessage());

    exit(1);
} catch (Exception $e) {
    error_log('Key Reader Error: '.$e->getMessage());

    exit(1);
}
