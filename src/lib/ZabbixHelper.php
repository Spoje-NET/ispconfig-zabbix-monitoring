<?php
declare(strict_types=1);

namespace ISPConfigMonitoring;

/**
 * Zabbix Helper
 * 
 * Provides utility functions for formatting data for Zabbix consumption
 * Includes LLD (Low-Level Discovery) JSON formatting and data sanitization
 */
class ZabbixHelper
{
    /**
     * Format data for Zabbix Low-Level Discovery (LLD)
     *
     * @param array $items Array of items to format
     * @param array $macroMap Mapping of item keys to Zabbix macro names
     *                        Example: ['domain' => '{#DOMAIN}', 'id' => '{#WEBSITE_ID}']
     * @return array Formatted discovery data
     */
    public function formatDiscovery(array $items, array $macroMap): array
    {
        $data = [];

        foreach ($items as $item) {
            $discoveryItem = [];

            foreach ($macroMap as $sourceKey => $macroName) {
                $value = $this->getNestedValue($item, $sourceKey);
                $discoveryItem[$macroName] = $this->sanitizeValue($value);
            }

            if (!empty($discoveryItem)) {
                $data[] = $discoveryItem;
            }
        }

        return ['data' => $data];
    }

    /**
     * Format websites for Zabbix LLD
     *
     * @param array $websites Array of website records from ISPConfig
     * @return array Formatted discovery data
     */
    public function formatWebsitesDiscovery(array $websites): array
    {
        $macroMap = [
            'domain_id' => '{#WEBSITE_ID}',
            'domain' => '{#DOMAIN}',
            'server_id' => '{#SERVER_ID}',
            'document_root' => '{#DOCUMENT_ROOT}',
            'php' => '{#PHP_VERSION}',
            'active' => '{#ACTIVE}',
            'ssl' => '{#SSL_ENABLED}'
        ];

        return $this->formatDiscovery($websites, $macroMap);
    }

    /**
     * Get nested value from array using dot notation
     *
     * @param array $array Source array
     * @param string $key Key in dot notation (e.g., 'parent.child')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    private function getNestedValue(array $array, string $key, $default = '')
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $default;
        }

        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Sanitize value for Zabbix
     *
     * @param mixed $value Value to sanitize
     * @return string Sanitized value
     */
    private function sanitizeValue($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        // Convert to string and remove control characters
        $value = (string) $value;
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        return trim($value);
    }

    /**
     * Format item value for Zabbix item key
     *
     * @param mixed $value Raw value
     * @param string $type Value type: 'numeric', 'string', 'boolean', 'timestamp'
     * @return string Formatted value
     */
    public function formatItemValue($value, string $type = 'string'): string
    {
        if ($value === null) {
            return '';
        }

        switch ($type) {
            case 'numeric':
                return (string) floatval($value);

            case 'boolean':
                return $this->parseBooleanValue($value) ? '1' : '0';

            case 'timestamp':
                return $this->formatTimestamp($value);

            case 'bytes':
                return (string) $this->parseBytes($value);

            default:
                return $this->sanitizeValue($value);
        }
    }

    /**
     * Parse boolean value from various formats
     *
     * @param mixed $value
     * @return bool
     */
    private function parseBooleanValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return $value > 0;
        }

        $value = strtolower((string) $value);
        return in_array($value, ['1', 'yes', 'y', 'true', 'on', 'active'], true);
    }

    /**
     * Format timestamp for Zabbix
     *
     * @param mixed $value Timestamp value
     * @return string Unix timestamp
     */
    private function formatTimestamp($value): string
    {
        if (is_numeric($value)) {
            return (string) intval($value);
        }

        $timestamp = strtotime((string) $value);
        return $timestamp !== false ? (string) $timestamp : '0';
    }

    /**
     * Convert a numeric value or human-readable size string into an integer number of bytes.
     *
     * Accepts plain numbers (interpreted as bytes) or strings with an optional unit suffix
     * `B`, `K`, `M`, `G`, `T` (case-insensitive) and optional decimals (e.g., "1.5G", "512M").
     *
     * @param mixed $value The value to parse (number or size string).
     * @return int Integer number of bytes; returns 0 if the value cannot be parsed.
     */
    private function parseBytes($value): int
    {
        if (is_numeric($value)) {
            return intval($value);
        }

        $value = trim((string) $value);
        $units = ['B' => 1, 'K' => 1024, 'M' => 1048576, 'G' => 1073741824, 'T' => 1099511627776];

        if (preg_match('/^(\d+(?:\.\d+)?)\s*([BKMGT])?$/i', $value, $matches)) {
            $number = floatval($matches[1]);
            $unit = strtoupper($matches[2] ?? 'B');
            return intval($number * ($units[$unit] ?? 1));
        }

        return 0;
    }

    /**
     * Builds Zabbix low-level discovery data for email accounts.
     *
     * @param array $emails List of email records (ISPConfig format) to convert into discovery items.
     * @return array An array with a 'data' key containing discovery items where each item maps Zabbix macros (e.g. `{#EMAIL}`, `{#QUOTA}`) to their values.
     */
    public function formatEmailsDiscovery(array $emails): array
    {
        $macroMap = [
            'mailuser_id' => '{#MAIL_USER_ID}',
            'email' => '{#EMAIL}',
            'mail_domain_id' => '{#MAIL_DOMAIN_ID}',
            'quota' => '{#QUOTA}',
            'used' => '{#USED}',
            'active' => '{#ACTIVE}',
            'domain' => '{#DOMAIN}'
        ];

        return $this->formatDiscovery($emails, $macroMap);
    }

    /**
         * Builds Zabbix Low-Level Discovery (LLD) data for mail domains.
         *
         * @param array $domains Mail domain records (each entry is an associative array from ISPConfig).
         * @return array Discovery structure with a top-level `data` array of items containing macros like `{#MAIL_DOMAIN_ID}`, `{#DOMAIN}`, `{#SERVER_ID}`, `{#ACTIVE}`, and `{#CATCH_ALL}`.
         */
    public function formatMailDomainsDiscovery(array $domains): array
    {
        $macroMap = [
            'mail_domain_id' => '{#MAIL_DOMAIN_ID}',
            'domain' => '{#DOMAIN}',
            'server_id' => '{#SERVER_ID}',
            'active' => '{#ACTIVE}',
            'mail_catchall' => '{#CATCH_ALL}'
        ];

        return $this->formatDiscovery($domains, $macroMap);
    }

    /**

     * @return string Formatted Zabbix key
     */
    public function createItemKey(string $prefix, string $metric, array $params = []): string
    {
        $key = "{$prefix}.{$metric}";

        if (!empty($params)) {
            $paramString = implode(',', array_map(function ($param) {
                return $this->escapeKeyParam($param);
            }, $params));
            $key .= "[{$paramString}]";
        }

        return $key;
    }

    /**
     * Escape parameter for Zabbix item key
     *
     * @param string $param Parameter value
     * @return string Escaped parameter
     */
    private function escapeKeyParam(string $param): string
    {
        // Escape special characters for Zabbix key parameters
        $param = str_replace(['\\', '"', '[', ']', ','], ['\\\\', '\\"', '\\[', '\\]', '\\,'], $param);
        
        // Quote if contains spaces
        if (strpos($param, ' ') !== false) {
            $param = "\"{$param}\"";
        }

        return $param;
    }

    /**
     * Output JSON for Zabbix (with proper formatting)
     *
     * @param array $data Data to output
     * @return void
     */
    public function outputJSON(array $data): void
    {
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Output JSON for Zabbix LLD with pretty printing (for debugging)
     *
     * @param array $data Data to output
     * @return void
     */
    public function outputJSONPretty(array $data): void
    {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Validate Zabbix LLD JSON structure
     *
     * @param array $data LLD data to validate
     * @return bool True if valid
     */
    public function validateLLDData(array $data): bool
    {
        if (!isset($data['data']) || !is_array($data['data'])) {
            return false;
        }

        foreach ($data['data'] as $item) {
            if (!is_array($item)) {
                return false;
            }

            // Check if all keys are macros (start with {# and end with })
            foreach (array_keys($item) as $key) {
                if (!preg_match('/^\{#[A-Z0-9_]+\}$/', $key)) {
                    return false;
                }
            }
        }

        return true;
    }
}