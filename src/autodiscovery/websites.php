<?php
declare(strict_types=1);

/**
 * ISPConfig Websites Autodiscovery Script
 * 
 * This script discovers all websites in ISPConfig and formats them
 * for Zabbix Low-Level Discovery (LLD).
 * 
 * Usage: php websites.php
 * Output: JSON formatted discovery data for Zabbix
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use ISPConfigMonitoring\ISPConfigClient;
use ISPConfigMonitoring\ISPConfigException;
use ISPConfigMonitoring\ZabbixHelper;

// Load configuration
$configFile = __DIR__ . '/../../config/config.php';
if (!file_exists($configFile)) {
    error_log("Configuration file not found: {$configFile}");
    error_log("Please copy config.example.php to config.php and configure it.");
    echo json_encode(['data' => []]);
    exit(1);
}

$config = require $configFile;

// Check if websites module is enabled
if (empty($config['modules']['websites'])) {
    error_log("Websites module is disabled in configuration");
    echo json_encode(['data' => []]);
    exit(0);
}

try {
    // Initialize clients
    $ispconfig = new ISPConfigClient($config);
    $zabbix = new ZabbixHelper();
    
    // Get all websites from ISPConfig
    $websites = $ispconfig->getWebsites();
    
    if (empty($websites)) {
        // No websites found - return empty discovery data
        $zabbix->outputJSON(['data' => []]);
        exit(0);
    }
    
    // Format data for Zabbix LLD
    $discoveryData = $zabbix->formatWebsitesDiscovery($websites);
    
    // Validate the discovery data
    if (!$zabbix->validateLLDData($discoveryData)) {
        throw new Exception("Invalid LLD data format");
    }
    
    // Output JSON for Zabbix
    $zabbix->outputJSON($discoveryData);
    
    // Log success
    if (!empty($config['log_enabled'])) {
        error_log(sprintf(
            "ISPConfig Websites Discovery: Found %d websites",
            count($websites)
        ));
    }
    
    exit(0);
    
} catch (ISPConfigException $e) {
    // ISPConfig API error
    error_log("ISPConfig API Error: " . $e->getMessage());
    echo json_encode(['data' => []]);
    exit(1);
    
} catch (Exception $e) {
    // Generic error
    error_log("Autodiscovery Error: " . $e->getMessage());
    echo json_encode(['data' => []]);
    exit(1);
}
