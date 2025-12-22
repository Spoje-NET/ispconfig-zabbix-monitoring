# GitHub Copilot Instructions for ISPConfig Zabbix Monitoring Project

## Project Context
This is a PHP-based monitoring ecosystem for ISPConfig using Zabbix 7.4. The project uses autodiscovery scripts, key readers, and YAML templates for monitoring various ISPConfig modules.

## Code Style & Standards

### PHP
- Use PHP 7.4+ features
- Follow PSR-12 coding standards
- Use strict types: `declare(strict_types=1);`
- Always use type hints for parameters and return types
- Use meaningful variable and function names
- Add PHPDoc comments for all classes and methods
- if there is an existing PHP library, prefer using it over custom implementations

### File Organization
- Place autodiscovery scripts in `src/autodiscovery/`
- Place key readers in `src/keys/`
- Place helper libraries in `src/lib/`
- Place Zabbix templates in `templates/{module}/`
- Use `.example.php` suffix for example configuration files

### Naming Conventions
- Classes: PascalCase (e.g., `ISPConfigClient`, `ZabbixHelper`)
- Methods: camelCase (e.g., `getWebsites()`, `formatForZabbix()`)
- Constants: UPPER_SNAKE_CASE (e.g., `API_ENDPOINT`)
- Files: snake_case for scripts (e.g., `websites.php`)
- Templates: snake_case with prefix (e.g., `template_ispconfig_websites.yaml`)

## Project-Specific Guidelines

### ISPConfig API Integration
- 3.3.0p3 or later is required for full functionality
- Always use the `ISPConfigClient` wrapper class
- Implement proper error handling for API calls
- Use session caching to minimize API calls
- Include retry logic for failed connections
- Log all API interactions

Example:
```php
$client = new ISPConfigClient($config);
try {
    $websites = $client->getWebsites();
} catch (ISPConfigException $e) {
    error_log("API Error: " . $e->getMessage());
    exit(1);
}
```

### Zabbix Data Formatting
- Use `ZabbixHelper` class for all Zabbix-related formatting
- Follow Zabbix LLD JSON format exactly
- Use macro naming: `{#MACRO_NAME}`
- Always include error handling

Example LLD format:
```json
{
    "data": [
        {
            "{#WEBSITE_ID}": "1",
            "{#DOMAIN}": "example.com",
            "{#SERVER_ID}": "1"
        }
    ]
}
```

### Configuration Management
- Never hardcode credentials
- Always use `config/config.php` for settings
- Provide `config.example.php` with all required parameters
- Validate configuration before use
- Use environment variables for sensitive data when possible

### Error Handling
- Use try-catch blocks for all external calls
- Provide meaningful error messages
- Log errors appropriately
- Return proper exit codes for scripts
- Never expose sensitive information in errors

### Security
- Sanitize all user inputs
- Validate data from ISPConfig API
- Use prepared statements if using databases
- Implement rate limiting for API calls
- Store credentials securely (never in git)
- Use HTTPS for all API communications

### Templates (YAML)
- Follow Zabbix 7.4 template format
- Include descriptive names and keys
- Add meaningful triggers with appropriate severity
- Include documentation in template description
- Use preprocessing when appropriate

Template structure:
```yaml
zabbix_export:
  version: '7.4'
  template_groups:
    - name: 'ISPConfig'
  templates:
    - template: 'ISPConfig Websites'
      name: 'ISPConfig Websites'
      description: 'Template for monitoring ISPConfig websites'
      groups:
        - name: 'ISPConfig'
      discovery_rules:
        # Discovery rules here
      items:
        # Items here
      triggers:
        # Triggers here
```

## Common Patterns

### Autodiscovery Script Pattern
```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use ISPConfigMonitoring\ISPConfigClient;
use ISPConfigMonitoring\ZabbixHelper;

$config = require __DIR__ . '/../../config/config.php';
$client = new ISPConfigClient($config);
$helper = new ZabbixHelper();

try {
    $items = $client->getItems();
    $discovery = $helper->formatDiscovery($items);
    echo json_encode($discovery, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    error_log($e->getMessage());
    exit(1);
}
```

### Key Reader Script Pattern
```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use ISPConfigMonitoring\ISPConfigClient;

$config = require __DIR__ . '/../../config/config.php';
$client = new ISPConfigClient($config);

$itemId = $argv[1] ?? null;
$key = $argv[2] ?? null;

if (!$itemId || !$key) {
    echo "Usage: php websites.php <item_id> <key>\n";
    exit(1);
}

try {
    $value = $client->getItemValue($itemId, $key);
    echo $value;
} catch (Exception $e) {
    error_log($e->getMessage());
    exit(1);
}
```

## Testing
- Write unit tests for all library classes
- Test API integration with mock data
- Validate Zabbix templates before committing
- Test installation script on clean system
- Include edge cases in tests

## Documentation
- Update README.md when adding new features
- Keep PROJECT_PLAN.md current with progress
- Document all configuration options
- Include usage examples
- Provide troubleshooting section

## Git Workflow
- Use meaningful commit messages
- Reference issues in commits
- Keep commits focused and atomic
- Don't commit sensitive data
- Update .gitignore as needed

## When Suggesting Code
- Always include error handling
- Add inline comments for complex logic
- Follow the established patterns
- Consider performance implications
- Ensure backward compatibility
- Include usage examples

## Questions to Ask Before Coding
1. Does this follow PSR-12 standards?
2. Is error handling comprehensive?
3. Are there any security concerns?
4. Is the code testable?
5. Is it consistent with existing code?
6. Does it need documentation updates?
7. Are there performance considerations?

## Priority Order for Suggestions
1. Security
2. Functionality
3. Performance
4. Code quality
5. Documentation