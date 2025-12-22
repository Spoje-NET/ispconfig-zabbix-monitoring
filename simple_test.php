<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Simple SOAP Test\n";
echo "================\n\n";

$config = require __DIR__ . '/config/config.php';

echo "Testing connection to: " . $config['soap_uri'] . "\n";
echo "SSL Verification: " . ($config['verify_ssl'] ? 'enabled' : 'disabled') . "\n\n";

try {
    $soapOptions = [
        'location' => $config['soap_location'],
        'uri' => $config['soap_uri'],
        'trace' => true,
        'exceptions' => true,
        'connection_timeout' => 10,
    ];

    if (!$config['verify_ssl']) {
        echo "Disabling SSL verification...\n";
        $soapOptions['stream_context'] = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
    }

    echo "Creating SOAP client...\n";
    $client = new SoapClient(null, $soapOptions);
    echo "SOAP client created successfully!\n\n";
    
    echo "Attempting login...\n";
    $sessionId = $client->login($config['username'], $config['password']);
    
    if ($sessionId) {
        echo "✓ Login successful!\n";
        echo "Session ID: " . $sessionId . "\n";
        
        // Try to get websites
        echo "\nGetting websites...\n";
        $websites = $client->sites_web_domain_get($sessionId, []);
        echo "✓ Found " . count($websites) . " websites\n";
        
        // Logout
        $client->logout($sessionId);
        echo "✓ Logged out\n";
    } else {
        echo "✗ Login failed - empty session ID\n";
    }
    
} catch (SoapFault $e) {
    echo "✗ SOAP Error: " . $e->getMessage() . "\n";
    echo "Fault code: " . $e->faultcode . "\n";
    echo "Fault string: " . $e->faultstring . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
