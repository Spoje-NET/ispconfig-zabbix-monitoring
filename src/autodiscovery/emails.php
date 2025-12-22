<?php
declare(strict_types=1);

/**
 * ISPConfig Email Monitoring Autodiscovery
 * 
 * Since sites_mail_user_get is restricted or not available,
 * this script uses web domain data and mail server info to discover emails.
 * 
 * Usage: php src/autodiscovery/emails.php
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
    echo json_encode(['data' => []]);
    exit(1);
}

$config = require $configFile;

// Check if email module is enabled
if (empty($config['modules']['email'])) {
    error_log("Email module is disabled in configuration");
    echo json_encode(['data' => []]);
    exit(0);
}

try {
    $ispconfig = new ISPConfigClient($config);
    $zabbix = new ZabbixHelper();
    
    // Get websites to extract email information
    $websites = $ispconfig->getWebsites();
    
    if (empty($websites)) {
        $discovery = ['data' => []];
    } else {
        // Create virtual email entries from website data
        // In a real scenario, you would query the mail server or database
        $emails = [];
        
        foreach ($websites as $website) {
            // Extract domain from website record
            if (isset($website['domain'])) {
                $emails[] = [
                    'domain' => $website['domain'],
                    'server_id' => $website['server_id'] ?? 'unknown',
                    'admin_email' => 'admin@' . $website['domain'],
                    'website_id' => $website['domain_id'] ?? 0,
                    'active' => $website['active'] ?? 'y',
                ];
            }
        }
        
        // Format for Zabbix LLD
        $discovery = [
            'data' => array_map(function($email) {
                return [
                    '{#EMAIL}' => $email['admin_email'],
                    '{#DOMAIN}' => $email['domain'],
                    '{#WEBSITE_ID}' => (string)$email['website_id'],
                    '{#SERVER_ID}' => (string)$email['server_id'],
                    '{#EMAIL_TYPE}' => 'admin',
                    '{#ACTIVE}' => $email['active'],
                ];
            }, $emails)
        ];
    }
    
    // Validate and output
    if ($zabbix->validateLLDData($discovery)) {
        $zabbix->outputJSON($discovery);
        exit(0);
    } else {
        throw new Exception("Invalid LLD data format");
    }
    
} catch (ISPConfigException $e) {
    error_log("ISPConfig API Error: " . $e->getMessage());
    echo json_encode(['data' => []]);
    exit(1);
    
} catch (Exception $e) {
    error_log("Autodiscovery Error: " . $e->getMessage());
    echo json_encode(['data' => []]);
    exit(1);
}
