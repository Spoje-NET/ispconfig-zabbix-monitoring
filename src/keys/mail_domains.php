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

/**
 * ISPConfig Mail Domains Key Reader Script
 * 
 * This script retrieves specific metrics for individual mail domains.
 * It's called by Zabbix agent for each discovered mail domain.
 * 
 * Usage: php mail_domains.php <domain_id> <key>
 * 
 * Available keys:
 *   - active: Mail domain active status (0/1)
 *   - domain: Domain name
 *   - server_id: Server ID
 *   - mail_catchall: Catch-all email address
 *   - account_count: Number of email accounts in domain
 *   - total_quota: Total quota for all accounts
 *   - total_used: Total used space for all accounts
 * 
 * Example: php mail_domains.php 1 active
 * Output: 1
 */


use ISPConfigMonitoring\ISPConfigClient;
use ISPConfigMonitoring\ISPConfigException;
use ISPConfigMonitoring\ZabbixHelper;

/**
 * Prints usage instructions for mail_domains.php and the list of available keys.
 *
 * Outputs the usage message to standard output and terminates the process with exit status 1.
 */
function showUsage(): void
{
    echo "Usage: php mail_domains.php <domain_id> <key>\n\n";
    echo "Available keys:\n";
    echo "  active        - Mail domain active status (0/1)\n";
    echo "  domain        - Domain name\n";
    echo "  server_id     - Server ID\n";
    echo "  mail_catchall - Catch-all email address\n";
    echo "  account_count - Number of email accounts in domain\n";
    echo "  total_quota   - Total quota for all accounts (bytes)\n";
    echo "  total_used    - Total used space for all accounts (bytes)\n";

    exit(1);
}

/**
 * Compute mail account count and aggregated quota/usage for a mail domain.
 *
 * Queries ISPConfig for all mail accounts belonging to the given domain and
 * returns counts and totals. If the API response is invalid or an error
 * occurs, returns zeroed statistics.
 *
 * @param ISPConfigClient $ispconfig the ISPConfig API client used to fetch mail accounts
 * @param int             $domainId  the ID of the mail domain to inspect
 *
 * @return array{
 *     account_count: int,
 *     total_quota: int,
 *     total_used: int
 * } Associative array containing:
 *   - `account_count`: number of mail accounts for the domain.
 *   - `total_quota`: sum of the `quota` values across accounts (as integer).
 *   - `total_used`: sum of the `used` values across accounts (as integer).
 */
function getMailDomainStats(ISPConfigClient $ispconfig, int $domainId): array
{
    try {
        // Get all emails for this domain
        $emails = $ispconfig->call('sites_mail_user_get', [['mail_domain_id' => $domainId]]);

        if (!\is_array($emails)) {
            return ['account_count' => 0, 'total_quota' => 0, 'total_used' => 0];
        }

        $accountCount = \count($emails);
        $totalQuota = 0;
        $totalUsed = 0;

        foreach ($emails as $email) {
            $totalQuota += (int) ($email['quota'] ?? 0);
            $totalUsed += (int) ($email['used'] ?? 0);
        }

        return [
            'account_count' => $accountCount,
            'total_quota' => $totalQuota,
            'total_used' => $totalUsed,
        ];
    } catch (Exception $e) {
        error_log('Failed to get domain stats: '.$e->getMessage());

        return ['account_count' => 0, 'total_quota' => 0, 'total_used' => 0];
    }
}

/**
 * Map a mail domain attribute or metric key to a formatted value for Zabbix.
 *
 * @param array  $domain   mail domain record (fields used: 'active', 'domain', 'server_id', 'mail_catchall')
 * @param string $key      the requested key (supported: 'active', 'domain', 'server_id', 'mail_catchall', 'account_count', 'total_quota', 'total_used')
 * @param int    $domainId mail domain ID used when retrieving aggregated mail statistics
 *
 * @throws Exception if an unknown key is requested
 *
 * @return string the value formatted for Zabbix according to the key
 */
function getMailDomainValue(
    array $domain,
    string $key,
    ISPConfigClient $ispconfig,
    int $domainId,
    ZabbixHelper $zabbix,
): string {
    switch ($key) {
        case 'active':
            return $zabbix->formatItemValue($domain['active'] ?? 'n', 'boolean');
        case 'domain':
            return $zabbix->formatItemValue($domain['domain'] ?? '', 'string');
        case 'server_id':
            return $zabbix->formatItemValue($domain['server_id'] ?? 0, 'numeric');
        case 'mail_catchall':
            return $zabbix->formatItemValue($domain['mail_catchall'] ?? '', 'string');
        case 'account_count':
            $stats = getMailDomainStats($ispconfig, $domainId);

            return $zabbix->formatItemValue($stats['account_count'], 'numeric');
        case 'total_quota':
            $stats = getMailDomainStats($ispconfig, $domainId);

            return $zabbix->formatItemValue($stats['total_quota'], 'bytes');
        case 'total_used':
            $stats = getMailDomainStats($ispconfig, $domainId);

            return $zabbix->formatItemValue($stats['total_used'], 'bytes');

        default:
            throw new Exception("Unknown key: {$key}");
    }
}

// Parse command line arguments
if ($argc < 3) {
    showUsage();
}

$domainId = (int) $argv[1];
$key = $argv[2];

if ($domainId <= 0) {
    error_log("Invalid domain ID: {$argv[1]}");

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

    // Get mail domain data
    $domain = $ispconfig->getMailDomain($domainId);

    if ($domain === null) {
        throw new Exception("Mail domain not found: {$domainId}");
    }

    // Get and output the requested value
    $value = getMailDomainValue($domain, $key, $ispconfig, $domainId, $zabbix);
    echo $value;

    exit(0);
} catch (ISPConfigException $e) {
    error_log('ISPConfig API Error: '.$e->getMessage());

    exit(1);
} catch (Exception $e) {
    error_log('Key Reader Error: '.$e->getMessage());

    exit(1);
}
