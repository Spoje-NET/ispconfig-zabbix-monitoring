<?php
declare(strict_types=1);

/**
 * ISPConfig Emails Key Reader Script
 * 
 * This script retrieves specific metrics for individual email accounts.
 * It's called by Zabbix agent for each discovered email.
 * 
 * Usage: php emails.php <email_id> <key>
 * 
 * Available keys:
 *   - active: Email account active status (0/1)
 *   - email: Full email address
 *   - domain: Domain name
 *   - quota: Hard disk quota in bytes
 *   - used: Currently used space in bytes
 *   - usage_percent: Usage percentage (0-100)
 *   - spamfilter_enabled: Spamfilter status (0/1)
 *   - antivirus_enabled: Antivirus status (0/1)
 *   - mail_domain_id: Mail domain ID
 *   - server_id: Server ID
 *   - homedir: Home directory path
 * 
 * Example: php emails.php 1 active
 * Output: 1
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use ISPConfigMonitoring\ISPConfigClient;
use ISPConfigMonitoring\ISPConfigException;
use ISPConfigMonitoring\ZabbixHelper;

/**
 * Display usage information
 */
function showUsage(): void
{
    echo "Usage: php emails.php <email_id> <key>\n\n";
    echo "Available keys:\n";
    echo "  active             - Email active status (0/1)\n";
    echo "  email              - Full email address\n";
    echo "  domain             - Domain name\n";
    echo "  quota              - Hard disk quota in bytes\n";
    echo "  used               - Currently used space in bytes\n";
    echo "  usage_percent      - Usage percentage (0-100)\n";
    echo "  spamfilter_enabled - Spamfilter status (0/1)\n";
    echo "  antivirus_enabled  - Antivirus status (0/1)\n";
    echo "  mail_domain_id     - Mail domain ID\n";
    echo "  server_id          - Server ID\n";
    echo "  homedir            - Home directory path\n";
    exit(1);
}

/**
 * Get value from email data by key
 */
function getEmailValue(array $email, string $key, ZabbixHelper $zabbix): string
{
    switch ($key) {
        case 'active':
            return $zabbix->formatItemValue($email['active'] ?? 'n', 'boolean');
            
        case 'email':
            return $zabbix->formatItemValue($email['email'] ?? '', 'string');
            
        case 'domain':
            return $zabbix->formatItemValue($email['domain'] ?? '', 'string');
            
        case 'quota':
            return $zabbix->formatItemValue($email['quota'] ?? 0, 'bytes');
            
        case 'used':
            return $zabbix->formatItemValue($email['used'] ?? 0, 'bytes');
            
        case 'usage_percent':
            // Calculate percentage
            $quota = intval($email['quota'] ?? 0);
            $used = intval($email['used'] ?? 0);
            $percent = $quota > 0 ? $zabbix->calculateEmailUsagePercent($used, $quota) : 0;
            return $zabbix->formatItemValue($percent, 'numeric');
            
        case 'spamfilter_enabled':
            return $zabbix->formatItemValue($email['spamfilter_enabled'] ?? 'n', 'boolean');
            
        case 'antivirus_enabled':
            return $zabbix->formatItemValue($email['antivirus_enabled'] ?? 'n', 'boolean');
            
        case 'mail_domain_id':
            return $zabbix->formatItemValue($email['mail_domain_id'] ?? 0, 'numeric');
            
        case 'server_id':
            return $zabbix->formatItemValue($email['server_id'] ?? 0, 'numeric');
            
        case 'homedir':
            return $zabbix->formatItemValue($email['homedir'] ?? '', 'string');
            
        default:
            throw new Exception("Unknown key: {$key}");
    }
}

// Parse command line arguments
if ($argc < 3) {
    showUsage();
}

$emailId = (int) $argv[1];
$key = $argv[2];

if ($emailId <= 0) {
    error_log("Invalid email ID: {$argv[1]}");
    exit(1);
}

// Load configuration
$configFile = __DIR__ . '/../../config/config.php';
if (!file_exists($configFile)) {
    error_log("Configuration file not found: {$configFile}");
    exit(1);
}

$config = require $configFile;

// Check if email module is enabled
if (empty($config['modules']['email'])) {
    error_log("Email module is disabled in configuration");
    exit(1);
}

try {
    // Initialize clients
    $ispconfig = new ISPConfigClient($config);
    $zabbix = new ZabbixHelper();
    
    // Get email data
    $email = $ispconfig->getEmail($emailId);
    
    if ($email === null) {
        throw new Exception("Email not found: {$emailId}");
    }
    
    // Get and output the requested value
    $value = getEmailValue($email, $key, $zabbix);
    echo $value;
    
    exit(0);
    
} catch (ISPConfigException $e) {
    error_log("ISPConfig API Error: " . $e->getMessage());
    exit(1);
    
} catch (Exception $e) {
    error_log("Key Reader Error: " . $e->getMessage());
    exit(1);
}
