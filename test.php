<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Starting test...\n";

require_once __DIR__ . '/vendor/autoload.php';

use ISPConfigMonitoring\ISPConfigClient;
use ISPConfigMonitoring\ISPConfigException;

echo "Autoload OK\n";

// Load configuration
$configFile = __DIR__ . '/config/config.php';
if (!file_exists($configFile)) {
    die("Configuration file not found: {$configFile}\n");
}

echo "Config file found\n";

$config = require $configFile;

echo "Config loaded\n";
echo "SOAP URI: " . $config['soap_uri'] . "\n";
echo "Username: " . $config['username'] . "\n";

try {
    echo "Creating ISPConfig client...\n";
    $ispconfig = new ISPConfigClient($config);
    echo "Client created\n";
    
    echo "Attempting login...\n";
    $sessionId = $ispconfig->login();
    echo "Login successful! Session ID: " . $sessionId . "\n";
    
    echo "Getting websites...\n";
    $websites = $ispconfig->getWebsites();
    echo "Found " . count($websites) . " websites\n";
    
    if (count($websites) > 0) {
        echo "First website: " . json_encode($websites[0]) . "\n";
    }
    
    echo "Test completed successfully!\n";
    
} catch (ISPConfigException $e) {
    echo "ISPConfig Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
