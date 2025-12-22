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
 * ISPConfig Emails Autodiscovery Script.
 *
 * This script discovers all email accounts configured in ISPConfig
 * and outputs them in Zabbix Low-Level Discovery (LLD) JSON format.
 *
 * Usage: php emails.php
 *
 * Output: JSON array with discovered emails and their macros:
 *   - {#MAIL_USER_ID}: Email account ID
 *   - {#EMAIL}: Full email address
 *   - {#MAIL_DOMAIN_ID}: Mail domain ID
 *   - {#DOMAIN}: Domain name
 *   - {#QUOTA}: Mailbox quota in bytes
 *   - {#USED}: Currently used space in bytes
 *   - {#ACTIVE}: Active status (1/0)
 *
 * Example output:
 * {
 *   "data": [
 *     {
 *       "{#MAIL_USER_ID}": "1",
 *       "{#EMAIL}": "admin@example.com",
 *       "{#MAIL_DOMAIN_ID}": "1",
 *       "{#DOMAIN}": "example.com",
 *       "{#QUOTA}": "1073741824",
 *       "{#USED}": "536870912",
 *       "{#ACTIVE}": "1"
 *     }
 *   ]
 * }
 */

use ISPConfigMonitoring\ISPConfigClient;
use ISPConfigMonitoring\ISPConfigException;
use ISPConfigMonitoring\ZabbixHelper;

/**
 * Add an integer `usage_percent` field to each email entry based on `used` and `quota`.
 *
 * Each input entry is preserved and augmented with `usage_percent` set to the integer
 * percentage of `used` divided by `quota` when `quota` is greater than zero; otherwise
 * `usage_percent` is set to 0.
 *
 * @param array $emails List of email records. Individual records may include `quota` and `used` keys.
 *
 * @return array the list of email records with an added `usage_percent` integer field for each record
 */
function enrichEmailData(array $emails): array
{
    $helper = new ZabbixHelper();
    $enriched = [];

    foreach ($emails as $email) {
        // Calculate usage percentage if quota exists
        if (isset($email['quota'], $email['used'])) {
            $quota = (int) ($email['quota'] ?? 0);
            $used = (int) ($email['used'] ?? 0);
            $email['usage_percent'] = $quota > 0 ?
                (int) $helper->calculateEmailUsagePercent($used, $quota) : 0;
        } else {
            $email['usage_percent'] = 0;
        }

        $enriched[] = $email;
    }

    return $enriched;
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

    // Get email accounts
    $emails = $ispconfig->getEmails();

    if (empty($emails)) {
        // No emails found, return empty LLD
        $discovery = ['data' => []];
    } else {
        // Enrich data with calculated fields
        $emails = enrichEmailData($emails);

        // Format for Zabbix LLD
        $discovery = $zabbix->formatEmailsDiscovery($emails);
    }

    // Validate and output
    if ($zabbix->validateLLDData($discovery)) {
        $zabbix->outputJSON($discovery);

        exit(0);
    }

    throw new Exception('Invalid LLD data format');
} catch (ISPConfigException $e) {
    error_log('ISPConfig API Error: '.$e->getMessage());

    exit(1);
} catch (Exception $e) {
    error_log('Autodiscovery Error: '.$e->getMessage());

    exit(1);
}
