<?php
declare(strict_types=1);

/**
 * ISPConfig Zabbix Monitoring Configuration
 * 
 * Copy this file to config.php and fill in your credentials
 * DO NOT commit config.php to version control!
 */

return [
    // ISPConfig SOAP API Configuration
    'soap_uri' => 'https://your-ispconfig-server.com:8080/remote/',
    'soap_location' => 'https://your-ispconfig-server.com:8080/remote/index.php',
    
    // ISPConfig API Credentials
    'username' => 'remote_user',
    'password' => 'your_secure_password',
    
    // SSL Verification
    // Set to false only if using self-signed certificates (not recommended for production)
    'verify_ssl' => true,
    
    // Logging Configuration
    'log_enabled' => true,
    'log_file' => __DIR__ . '/../logs/ispconfig-monitoring.log',
    'log_level' => 'info', // debug, info, warning, error
    
    // Cache Configuration (for session management)
    'cache_enabled' => true,
    'cache_ttl' => 300, // Session cache TTL in seconds (5 minutes)
    
    // Monitoring Settings
    'monitoring' => [
        // Interval for autodiscovery (in seconds)
        'discovery_interval' => 3600, // 1 hour
        
        // Interval for key updates (in seconds)
        'update_interval' => 300, // 5 minutes
        
        // Maximum retry attempts for API calls
        'max_retries' => 3,
        
        // Delay between retries (in seconds)
        'retry_delay' => 2,
    ],
    
    // Modules to monitor (enable/disable specific modules)
    'modules' => [
        'websites' => true,
        'databases' => false, // To be implemented
        'email' => false,     // To be implemented
        'dns' => false,       // To be implemented
        'ftp' => false,       // To be implemented
    ],
];
