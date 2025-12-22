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

namespace ISPConfigMonitoring;

/**
 * Configuration Loader.
 *
 * Loads configuration from multiple possible locations
 */
class ConfigLoader
{
    /**
     * Load configuration from available locations.
     *
     * Checks the following locations in order:
     * 1. /etc/ispconfig-zabbix-monitoring/config.php (system-wide)
     * 2. Relative path from script location (development)
     *
     * @param string $relativePath Relative path from calling script (e.g., '../../config/config.php')
     *
     * @throws \RuntimeException If no configuration file is found
     *
     * @return array Configuration array
     */
    public static function load(string $relativePath = ''): array
    {
        $configLocations = [
            '/etc/ispconfig-zabbix-monitoring/config.php', // System-wide installation
        ];

        // Add relative path if provided
        if (!empty($relativePath)) {
            // Get the directory of the calling script
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            $callerDir = dirname($backtrace[0]['file']);
            $configLocations[] = $callerDir.'/'.$relativePath;
        }

        // Try each location
        foreach ($configLocations as $configFile) {
            if (file_exists($configFile)) {
                $config = require $configFile;

                if (!\is_array($config)) {
                    throw new \RuntimeException("Configuration file must return an array: {$configFile}");
                }

                return $config;
            }
        }

        // No config found
        $searchedLocations = implode(', ', $configLocations);
        throw new \RuntimeException("Configuration file not found. Searched locations: {$searchedLocations}");
    }

    /**
     * Check if a configuration file exists in any of the standard locations.
     *
     * @return bool True if a config file exists
     */
    public static function exists(): bool
    {
        $configLocations = [
            '/etc/ispconfig-zabbix-monitoring/config.php',
        ];

        foreach ($configLocations as $configFile) {
            if (file_exists($configFile)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the path to the configuration file if it exists.
     *
     * @return null|string Path to config file or null if not found
     */
    public static function getConfigPath(): ?string
    {
        $configLocations = [
            '/etc/ispconfig-zabbix-monitoring/config.php',
        ];

        foreach ($configLocations as $configFile) {
            if (file_exists($configFile)) {
                return $configFile;
            }
        }

        return null;
    }
}
