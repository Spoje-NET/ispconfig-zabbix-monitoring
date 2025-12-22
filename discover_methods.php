<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use ISPConfigMonitoring\ISPConfigClient;

$config = require __DIR__ . '/config/config.php';
$client = new ISPConfigClient($config);

try {
    $sessionId = $client->login();
    echo "✓ Login successful\n\n";
    
    // Create a raw SOAP client for testing
    $soapOptions = [
        'location' => $config['soap_location'],
        'uri' => $config['soap_uri'],
        'trace' => true,
        'exceptions' => false, // Don't throw exceptions
    ];
    
    if (!($config['verify_ssl'] ?? true)) {
        $soapOptions['stream_context'] = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
    }
    
    $soapClient = new \SoapClient(null, $soapOptions);
    
    // List of methods to try
    $methodsToTry = [
        // Mail user methods
        'sites_mail_user_get',
        'mail_user_get',
        'mail_user_list',
        'get_mail_users',
        'getMailUsers',
        
        // Mail domain methods
        'sites_mail_domain_get',
        'mail_domain_get',
        'mail_domain_list',
        'get_mail_domains',
        'getMailDomains',
        
        // Alternative email methods
        'client_email_get',
        'email_list',
        'mailbox_list',
        'mailbox_get',
        
        // System methods that might show others
        'sys_method_list',
        'sys_functions',
        'server_method_list',
    ];
    
    echo "=== Testing available methods ===\n\n";
    
    foreach ($methodsToTry as $method) {
        echo "Testing: $method ... ";
        
        // Try with just sessionId
        $result = @$soapClient->$method($sessionId);
        
        // If that fails, try with empty array as second param
        if ($result === null || (is_soap_fault($result) && stripos($result->faultstring, 'Too few arguments') !== false)) {
            $result = @$soapClient->$method($sessionId, []);
        }
        
        if ($result === null) {
            $fault = $soapClient->__getLastResponse();
            if (stripos($fault, 'does not exist') !== false) {
                echo "✗ Does not exist\n";
            } else {
                echo "? Error\n";
            }
        } elseif (is_soap_fault($result)) {
            echo "✗ " . substr($result->faultstring, 0, 80) . "\n";
        } else {
            if (is_array($result) && !empty($result)) {
                echo "✓ SUCCESS! Found " . count($result) . " items\n";
            } else {
                echo "✓ Callable but empty/null result\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
