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
 * ISPConfig Mail Domains Autodiscovery Script.
 *
 * This script discovers all mail domains configured in ISPConfig
 * and outputs them in Zabbix Low-Level Discovery (LLD) JSON format.
 *
 * Usage: php mail_domains.php
 *
 * Output: JSON array with discovered mail domains and their macros:
 *   - {#MAIL_DOMAIN_ID}: Mail domain ID
 *   - {#DOMAIN}: Domain name
 *   - {#SERVER_ID}: Server ID
 *   - {#ACTIVE}: Active status (1/0)
 *   - {#CATCH_ALL}: Catch-all email address
 *
 * Example output:
 * {
 *   "data": [
 *     {
 *       "{#MAIL_DOMAIN_ID}": "1",
 *       "{#DOMAIN}": "example.com",
 *       "{#SERVER_ID}": "1",
 *       "{#ACTIVE}": "1",
 *       "{#CATCH_ALL}": "admin@example.com"
 *     }
 *   ]
 * }
 */

use ISPConfigMonitoring\ISPConfigClient;
use ISPConfigMonitoring\ISPConfigException;
use ISPConfigMonitoring\ZabbixHelper;

/**
 * Ensure each mail domain record includes a `mail_catchall` field.
 *
 * If a record does not contain `mail_catchall`, the field is added with an empty string value.
 *
 * @param array $domains array of mail domain records (associative arrays)
 *
 * @return array the input records with `mail_catchall` guaranteed to exist on each entry
 */
function enrichMailDomainData(array $domains): array
{
    $enriched = [];

    foreach ($domains as $domain) {
        // Set default catch-all if not present
        if (!isset($domain['mail_catchall'])) {
            $domain['mail_catchall'] = '';
        }

        $enriched[] = $domain;
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

    // Get mail domains
    $domains = $ispconfig->getMailDomains();

    if (empty($domains)) {
        // No domains found, return empty LLD
        $discovery = ['data' => []];
    } else {
        // Enrich data with calculated fields
        $domains = enrichMailDomainData($domains);

        // Format for Zabbix LLD
        $discovery = $zabbix->formatMailDomainsDiscovery($domains);
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
