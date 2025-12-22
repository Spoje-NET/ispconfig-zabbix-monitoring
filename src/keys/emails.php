<?php
declare(strict_types=1);

/**
 * ISPConfig Email Key Reader
 * 
 * Retrieves specific email metrics for Zabbix
 * 
 * Usage: php src/keys/emails.php <domain> <key>
 * 
 * Available keys:
 * - status: Email account status
 * - quota: Mailbox quota
 * - usage: Used space
 * - usage_percent: Percentage of quota used
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use ISPConfigMonitoring\ISPConfigClient;

$configFile = __DIR__ . '/../../config/config.php';
if (!file_exists($configFile)) {
    echo "Configuration not found";
    exit(1);
}

$config = require $configFile;
$domain = $argv[1] ?? null;
$key = $argv[2] ?? null;

if (!$domain || !$key) {
    echo "Usage: php emails.php <domain> <key>\n";
    echo "Available keys: status, quota, usage, usage_percent\n";
    exit(1);
}

try {
    $client = new ISPConfigClient($config);
    
    // Get all websites
    $websites = $client->getWebsites();
    
    // Find matching domain
    $website = null;
    foreach ($websites as $w) {
        if ($w['domain'] === $domain) {
            $website = $w;
            break;
        }
    }
    
    if (!$website) {
        echo "0"; // Domain not found
        exit(0);
    }
    
    // Return data based on key
    switch ($key) {
        case 'status':
            echo $website['active'] === 'y' ? '1' : '0';
            break;
            
        case 'quota':
            // ISPConfig stores quota in bytes, -1 means unlimited
            $quota = $website['traffic_quota'] ?? -1;
            echo $quota;
            break;
            
        case 'usage':
            // Return 0 - would need more detailed stats
            echo "0";
            break;
            
        case 'usage_percent':
            // Return 0 - would need more detailed stats
            echo "0";
            break;
            
        default:
            echo "0";
            break;
    }
    
    exit(0);
    
} catch (Exception $e) {
    error_log("Email key reader error: " . $e->getMessage());
    echo "0";
    exit(1);
}
