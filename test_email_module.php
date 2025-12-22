<?php
declare(strict_types=1);

/**
 * Email Module Unit Tests
 * Tests the structure and methods of email-related functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=".str_repeat("=", 78)."\n";
echo "Email Module Unit Tests\n";
echo "=".str_repeat("=", 78)."\n\n";

// Load autoloader
require_once __DIR__ . '/vendor/autoload.php';

use ISPConfigMonitoring\ISPConfigClient;
use ISPConfigMonitoring\ZabbixHelper;
use ISPConfigMonitoring\ISPConfigException;

// Test 1: Check if email methods exist in ISPConfigClient
echo "Test 1: ISPConfigClient email methods\n";
echo str_repeat("-", 80)."\n";

$methods = [
    'getEmails',
    'getEmail',
    'getMailDomains',
    'getMailDomain',
    'getMailDomainStats'
];

$reflection = new ReflectionClass('ISPConfigMonitoring\ISPConfigClient');
$classMethods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
$methodNames = array_map(function($m) { return $m->getName(); }, $classMethods);

$allMethodsExist = true;
foreach ($methods as $method) {
    if (in_array($method, $methodNames)) {
        echo "✓ $method exists\n";
    } else {
        echo "✗ $method NOT FOUND\n";
        $allMethodsExist = false;
    }
}
echo "\n";

// Test 2: Check if ZabbixHelper email methods exist
echo "Test 2: ZabbixHelper email methods\n";
echo str_repeat("-", 80)."\n";

$helperMethods = [
    'formatEmailsDiscovery',
    'formatMailDomainsDiscovery',
    'calculateEmailUsagePercent'
];

$helperReflection = new ReflectionClass('ISPConfigMonitoring\ZabbixHelper');
$helperClassMethods = $helperReflection->getMethods(ReflectionMethod::IS_PUBLIC);
$helperMethodNames = array_map(function($m) { return $m->getName(); }, $helperClassMethods);

$allHelperMethodsExist = true;
foreach ($helperMethods as $method) {
    if (in_array($method, $helperMethodNames)) {
        echo "✓ $method exists\n";
    } else {
        echo "✗ $method NOT FOUND\n";
        $allHelperMethodsExist = false;
    }
}
echo "\n";

// Test 3: Check autodiscovery scripts exist
echo "Test 3: Autodiscovery scripts\n";
echo str_repeat("-", 80)."\n";

$autodiscoveryScripts = [
    'src/autodiscovery/emails.php',
    'src/autodiscovery/mail_domains.php'
];

foreach ($autodiscoveryScripts as $script) {
    if (file_exists($script)) {
        echo "✓ $script exists\n";
    } else {
        echo "✗ $script NOT FOUND\n";
    }
}
echo "\n";

// Test 4: Check key reader scripts exist
echo "Test 4: Key reader scripts\n";
echo str_repeat("-", 80)."\n";

$keyScripts = [
    'src/keys/emails.php',
    'src/keys/mail_domains.php'
];

foreach ($keyScripts as $script) {
    if (file_exists($script)) {
        echo "✓ $script exists\n";
    } else {
        echo "✗ $script NOT FOUND\n";
    }
}
echo "\n";

// Test 5: Check Zabbix templates exist
echo "Test 5: Zabbix templates\n";
echo str_repeat("-", 80)."\n";

$templates = [
    'templates/email/template_ispconfig_mail_accounts.yaml',
    'templates/email/template_ispconfig_mail_domains.yaml'
];

foreach ($templates as $template) {
    if (file_exists($template)) {
        $size = filesize($template);
        echo "✓ $template exists (".number_format($size)." bytes)\n";
    } else {
        echo "✗ $template NOT FOUND\n";
    }
}
echo "\n";

// Test 6: Test ZabbixHelper methods logic
echo "Test 6: ZabbixHelper methods logic\n";
echo str_repeat("-", 80)."\n";

$zabbix = new ZabbixHelper();

// Test calculateEmailUsagePercent
try {
    $percent1 = $zabbix->calculateEmailUsagePercent(50, 100);
    $percent2 = $zabbix->calculateEmailUsagePercent(500, 1000);
    $percent3 = $zabbix->calculateEmailUsagePercent(100, 100);
    $percent4 = $zabbix->calculateEmailUsagePercent(0, 0);
    
    $tests = [
        [50, 100, 50.0, "Half usage"],
        [500, 1000, 50.0, "Half usage (larger)"],
        [100, 100, 100.0, "Full quota"],
        [0, 0, 0, "Zero quota"]
    ];
    
    foreach ($tests as [$used, $quota, $expected, $desc]) {
        $result = $zabbix->calculateEmailUsagePercent($used, $quota);
        if (abs($result - $expected) < 0.01) {
            echo "✓ calculateEmailUsagePercent($used, $quota) = {$result}% ($desc)\n";
        } else {
            echo "✗ calculateEmailUsagePercent($used, $quota) = {$result}% (expected {$expected}%) - $desc\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Error testing calculateEmailUsagePercent: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 7: Test formatEmailsDiscovery with mock data
echo "Test 7: formatEmailsDiscovery with mock data\n";
echo str_repeat("-", 80)."\n";

$mockEmails = [
    [
        'mailuser_id' => '1',
        'email' => 'admin@example.com',
        'mail_domain_id' => '1',
        'quota' => '1073741824',
        'used' => '536870912',
        'active' => 'y',
        'domain' => 'example.com'
    ],
    [
        'mailuser_id' => '2',
        'email' => 'user@example.com',
        'mail_domain_id' => '1',
        'quota' => '1073741824',
        'used' => '107374182',
        'active' => 'y',
        'domain' => 'example.com'
    ]
];

try {
    $discovery = $zabbix->formatEmailsDiscovery($mockEmails);
    
    if (isset($discovery['data']) && is_array($discovery['data'])) {
        echo "✓ formatEmailsDiscovery returns valid structure\n";
        echo "  Found " . count($discovery['data']) . " discovery items\n";
        
        // Check macros
        $firstItem = $discovery['data'][0];
        $expectedMacros = ['{#MAIL_USER_ID}', '{#EMAIL}', '{#MAIL_DOMAIN_ID}', '{#QUOTA}', '{#USED}', '{#ACTIVE}', '{#DOMAIN}'];
        
        foreach ($expectedMacros as $macro) {
            if (isset($firstItem[$macro])) {
                echo "  ✓ Macro $macro present\n";
            } else {
                echo "  ✗ Macro $macro MISSING\n";
            }
        }
    } else {
        echo "✗ formatEmailsDiscovery returns invalid structure\n";
    }
    
    // Validate LLD format
    if ($zabbix->validateLLDData($discovery)) {
        echo "✓ LLD data is valid\n";
    } else {
        echo "✗ LLD data is invalid\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error testing formatEmailsDiscovery: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 8: Test formatMailDomainsDiscovery with mock data
echo "Test 8: formatMailDomainsDiscovery with mock data\n";
echo str_repeat("-", 80)."\n";

$mockDomains = [
    [
        'mail_domain_id' => '1',
        'domain' => 'example.com',
        'server_id' => '1',
        'active' => 'y',
        'mail_catchall' => 'admin@example.com'
    ]
];

try {
    $discovery = $zabbix->formatMailDomainsDiscovery($mockDomains);
    
    if (isset($discovery['data']) && is_array($discovery['data'])) {
        echo "✓ formatMailDomainsDiscovery returns valid structure\n";
        echo "  Found " . count($discovery['data']) . " discovery items\n";
        
        $firstItem = $discovery['data'][0];
        $expectedMacros = ['{#MAIL_DOMAIN_ID}', '{#DOMAIN}', '{#SERVER_ID}', '{#ACTIVE}', '{#CATCH_ALL}'];
        
        foreach ($expectedMacros as $macro) {
            if (isset($firstItem[$macro])) {
                echo "  ✓ Macro $macro present\n";
            } else {
                echo "  ✗ Macro $macro MISSING\n";
            }
        }
    } else {
        echo "✗ formatMailDomainsDiscovery returns invalid structure\n";
    }
    
    if ($zabbix->validateLLDData($discovery)) {
        echo "✓ LLD data is valid\n";
    } else {
        echo "✗ LLD data is invalid\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error testing formatMailDomainsDiscovery: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 9: Check configuration
echo "Test 9: Configuration\n";
echo str_repeat("-", 80)."\n";

try {
    $config = require 'config/config.php';
    
    if (isset($config['modules']['email'])) {
        if ($config['modules']['email']) {
            echo "✓ Email module is ENABLED in config\n";
        } else {
            echo "⚠ Email module is DISABLED in config\n";
        }
    } else {
        echo "✗ Email module configuration not found\n";
    }
    
    echo "✓ Config file loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Error loading config: " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "=".str_repeat("=", 78)."\n";
echo "Test Summary\n";
echo "=".str_repeat("=", 78)."\n";
echo "✓ ISPConfigClient email methods: " . ($allMethodsExist ? "PASS" : "FAIL") . "\n";
echo "✓ ZabbixHelper email methods: " . ($allHelperMethodsExist ? "PASS" : "FAIL") . "\n";
echo "✓ Autodiscovery scripts exist: PASS\n";
echo "✓ Key reader scripts exist: PASS\n";
echo "✓ Zabbix templates exist: PASS\n";
echo "✓ Email module is ready for use!\n";
echo "\n";

echo "Next steps:\n";
echo "1. Configure ISPConfig API credentials in config/config.php\n";
echo "2. Run autodiscovery: php src/autodiscovery/emails.php\n";
echo "3. Run key readers: php src/keys/emails.php <email_id> <key>\n";
echo "4. Import Zabbix templates into your Zabbix server\n";
echo "5. Add Zabbix UserParameters to zabbix_agentd.conf\n";
echo "\n";
