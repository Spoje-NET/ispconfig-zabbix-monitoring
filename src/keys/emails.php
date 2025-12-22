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
 * Print command-line usage and the list of supported keys for the emails key reader.
 *
 * Outputs usage instructions and available keys to stdout, then terminates the process
 * with exit status code 1.
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
 * Retrieve a formatted value for an email record by key.
 *
 * @param array        $email  Email data array. Expected keys (when present):
 *                             'active' (string 'y'|'n'), 'email' (string), 'domain' (string),
 *                             'quota' (int bytes), 'used' (int bytes),
 *                             'spamfilter_enabled' (string 'y'|'n'), 'antivirus_enabled' (string 'y'|'n'),
 *                             'mail_domain_id' (int), 'server_id' (int), 'homedir' (string).
 * @param string       $key    The metric key to retrieve. Supported values:
 *                             'active', 'email', 'domain', 'quota', 'used', 'usage_percent',
 *                             'spamfilter_enabled', 'antivirus_enabled', 'mail_domain_id',
 *                             'server_id', 'homedir'.
 * @param ZabbixHelper $zabbix helper used to format returned values
 *
 * @throws Exception if an unknown key is provided
 *
 * @return string the value formatted for Zabbix corresponding to the requested key
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
            $quota = (int) ($email['quota'] ?? 0);
            $used = (int) ($email['used'] ?? 0);
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
$configFile = __DIR__.'/../../config/config.php';

if (!file_exists($configFile)) {
    error_log("Configuration file not found: {$configFile}");

    exit(1);
}

$config = require $configFile;

// Check if email module is enabled
if (empty($config['modules']['email'])) {
    error_log('Email module is disabled in configuration');

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
    error_log('ISPConfig API Error: '.$e->getMessage());

    exit(1);
} catch (Exception $e) {
    error_log('Key Reader Error: '.$e->getMessage());

    exit(1);
}
